<?php

namespace Bayfront\Bones;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Kernel\Bridge\RouterDispatcher;
use Bayfront\Bones\Application\Kernel\Console\Commands\AboutApp;
use Bayfront\Bones\Application\Kernel\Console\Commands\AliasList;
use Bayfront\Bones\Application\Kernel\Console\Commands\ContainerList;
use Bayfront\Bones\Application\Kernel\Console\Commands\EventList;
use Bayfront\Bones\Application\Kernel\Console\Commands\FilterList;
use Bayfront\Bones\Application\Kernel\Console\Commands\InstallBare;
use Bayfront\Bones\Application\Kernel\Console\Commands\InstallService;
use Bayfront\Bones\Application\Kernel\Console\Commands\KeyCreate;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeCommand;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeController;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeEvent;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeException;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeFilter;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeModel;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeService;
use Bayfront\Bones\Application\Kernel\Console\Commands\RouteList;
use Bayfront\Bones\Application\Kernel\Console\Commands\ScheduleList;
use Bayfront\Bones\Application\Kernel\Console\Commands\ScheduleRun;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Application\Utilities\Constants;
use Bayfront\Bones\Exceptions\ConstantAlreadyDefinedException;
use Bayfront\Bones\Exceptions\ErrorException;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Exceptions\InvalidConfigurationException;
use Bayfront\Bones\Exceptions\ServiceException;
use Bayfront\Bones\Exceptions\UndefinedConstantException;
use Bayfront\Bones\Interfaces\BonesConstructorInterface;
use Bayfront\Container\Container;
use Bayfront\Container\ContainerException;
use Bayfront\Container\NotFoundException;
use Bayfront\CronScheduler\Cron;
use Bayfront\Filesystem\Filesystem;
use Bayfront\Hooks\Hooks;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
use Bayfront\MonologFactory\LoggerFactory;
use Bayfront\PDO\DbFactory;
use Bayfront\RouteIt\DispatchException;
use Bayfront\RouteIt\Router;
use Bayfront\TimeHelpers\Time;
use Bayfront\Veil\Veil;
use DirectoryIterator;
use Dotenv\Dotenv;
use Exception;
use Symfony\Component\Console\Application;

class Bones
{

    /** @var Container */

    public static $container;

    /**
     * @param BonesConstructorInterface $constructor
     * @throws ConstantAlreadyDefinedException
     */

    public function __construct(BonesConstructorInterface $constructor)
    {

        Constants::define('BONES_START', microtime(true));
        Constants::define('APP_BASE_PATH', rtrim($constructor->getBasePath(), '/'));
        Constants::define('APP_PUBLIC_PATH', rtrim($constructor->getPublicPath(), '/'));

        // ------------------------- Create service container -------------------------

        self::$container = new Container();

    }

    /**
     * Include a file without exposing variables.
     *
     * @param string $file
     * @param Container $container
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */

    protected function safeInclude(string $file, Container $container)
    {
        include($file);
    }

    /**
     * Services to pass to the proper method based on interface.
     * startHttp, startCli
     *
     * @var array
     */

    protected $interface_services = [];

    /**
     * Start app.
     *
     * @return void
     * @throws ConstantAlreadyDefinedException
     * @throws UndefinedConstantException
     * @throws InvalidConfigurationException
     * @throws ContainerException
     * @throws Exception
     */

    public function start(string $interface)
    {

        if ($interface !== App::INTERFACE_CLI && $interface !== App::INTERFACE_HTTP) {
            throw new InvalidConfigurationException('Unable to start: Invalid interface (' . $interface . ')');
        }

        // ------------------------- Define constants -------------------------

        Constants::define('APP_INTERFACE', $interface);
        Constants::define('APP_CONFIG_PATH', Constants::get('APP_BASE_PATH') . '/config');
        Constants::define('APP_RESOURCES_PATH', Constants::get('APP_BASE_PATH') . '/resources');
        Constants::define('APP_STORAGE_PATH', Constants::get('APP_BASE_PATH') . '/storage');
        Constants::define('BONES_BASE_PATH', rtrim(dirname(__FILE__, 2), '/'));
        Constants::define('BONES_RESOURCES_PATH', Constants::get('BONES_BASE_PATH') . '/resources');
        Constants::define('BONES_VERSION', '2.0.0');

        // ------------------------- Load environment variables -------------------------

        if (file_exists(App::basePath('/.env'))) {
            Dotenv::createImmutable(App::basePath())->load();
        }

        // ------------------------- Set timezone -------------------------

        if (Time::isTimezone(App::getConfig('app.timezone'))) {
            date_default_timezone_set(App::getConfig('app.timezone'));
            date_default_timezone_set(App::getConfig('app.timezone'));
        } else {
            date_default_timezone_set('UTC');
        }

        // ------------------------- Debug mode errors -------------------------

        if (true === App::getConfig('app.debug')) { // Show all errors

            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');

        }

        // ------------------------- Set exception handler -------------------------

        set_exception_handler(function ($e) {

            if ($e instanceof HttpException && self::$container->has('Bayfront\HttpResponse\Response')) {

                // The status code has already been set by App::abort()

                /** @var Response $response */

                $response = self::$container->get('Bayfront\HttpResponse\Response');

            } else {

                $response = new Response();

                $response->setStatusCode(500); // Default status code

                self::$container->set('Bayfront\HttpResponse\Response', $response, true);

            }

            /*
             * Do bones.exception event
             *
             * Pass the exception and response as arguments to the event.
             */

            if (self::$container->has('Bayfront\Bones\Application\Services\EventService')) {

                /** @var EventService $events */
                $events = self::$container->get('Bayfront\Bones\Application\Services\EventService');

                $events->doEvent('bones.exception', $response, $e);

            }

            /*
             * Search for the first available handler.
             *
             * This allows for an app-specific handler to override Bones,
             * and also ensures a handler will exist.
             */

            $handler_classes = [
                App::getConfig('app.namespace', 'App\\') . 'Exceptions\Handler',
                'Bayfront\Bones\Exceptions\Handler'
            ];

            foreach ($handler_classes as $class) {

                if (class_exists($class)) {

                    $handler = new $class();

                    // Report exception

                    if (!in_array(get_class($e), $handler->getExcludedClasses())) {

                        $handler->report($response, $e);

                    }

                    // Respond to exception

                    $handler->respond($response, $e);

                    return; // Stop iteration

                }

            }

            /*
             * No handler existed.
             * This should never happen, but accounted for out of the
             * abundance of caution.
             */

            echo '<h1>Error: ' . $e->getMessage() . '</h1>';

        });

        // ------------------------- Set error handler -------------------------

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {

            $ename = 'Unknown error';

            // Get name of error from its number

            $constants = get_defined_constants(1);

            foreach ($constants['Core'] as $key => $value) {

                if (substr($key, 0, 2) == 'E_' && $errno == $value) {

                    $ename = ltrim($key, 'E_');
                    break;

                }

            }

            $message = $ename . ': ' . $errstr . ' in ' . $errfile . ' (line ' . $errline . ')';

            throw new ErrorException($message, $errno);

        }, E_ALL);

        // ------------------------- Check for required files -------------------------

        if (!file_exists(App::resourcesPath('/bootstrap.php'))) {
            throw new InvalidConfigurationException('Unable to start app: missing required file');
        }

        // ------------------------- Check for required app config -------------------------

        if (Arr::isMissing(App::getConfig('app', []), [
            'namespace',
            'key',
            'debug',
            'environment',
            'timezone',
            'events',
            'filters',
            'commands'
        ])) {
            throw new InvalidConfigurationException('Unable to start app: invalid configuration');
        }

        // ------------------------- Add services to the container -------------------------

        // ------------------------- Response (required) -------------------------

        $response = new Response();

        self::$container->set(get_class($response), $response);
        self::$container->setAlias('response', get_class($response));

        // ------------------------- Events and filters (required) -------------------------

        $hooks = new Hooks();

        $events = new EventService($hooks);
        self::$container->set(get_class($events), $events);
        self::$container->setAlias('events', get_class($events));

        $filters = new FilterService($hooks);
        self::$container->set(get_class($filters), $filters);
        self::$container->setAlias('filters', get_class($filters));

        // ------------------------- Cron scheduler (optional) -------------------------

        if (is_array(App::getConfig('scheduler'))) {

            // Populate config array

            $scheduler_config = [
                'lock_file_path' => App::getConfig('scheduler.lock_file_path', App::storagePath('/app/temp')),
                'output_file' => App::getConfig('scheduler.output_file', App::storagePath('/app/cron/cron-' . date('Y-m-d') . '.txt'))
            ];

            if (!is_dir($scheduler_config['lock_file_path'])) { // Prevent Cron constructor from throwing an exception
                mkdir($scheduler_config['lock_file_path'], 0755, true);
            }

            $this->interface_services['scheduler'] = new Cron($scheduler_config['lock_file_path'], $scheduler_config['output_file']);

            self::$container->set(get_class($this->interface_services['scheduler']), $this->interface_services['scheduler']);
            self::$container->setAlias('scheduler', get_class($this->interface_services['scheduler']));

        }

        // ------------------------- Database (optional) -------------------------

        if (is_array(App::getConfig('database'))) {

            $db = DbFactory::create(App::getConfig('database'));

            self::$container->set(get_class($db), $db);
            self::$container->setAlias('db', get_class($db));

        }

        // ------------------------- Filesystem (optional) -------------------------

        if (is_array(App::getConfig('filesystem'))) {

            self::$container->set('Bayfront\Filesystem\Filesystem', function () {
                return new Filesystem(App::getConfig('filesystem'));
            });
            self::$container->setAlias('filesystem', 'Bayfront\Filesystem\Filesystem');

        }

        // ------------------------- Logs (optional) -------------------------

        if (is_array(App::getConfig('logs'))) {

            self::$container->set('Bayfront\MonologFactory\LoggerFactory', function () {
                return new LoggerFactory(App::getConfig('logs'));
            });
            self::$container->setAlias('logs', 'Bayfront\MonologFactory\LoggerFactory');

        }

        // ------------------------- Router (optional) -------------------------

        if (is_array(App::getConfig('router'))) {

            $this->interface_services['router'] = new Router(App::getConfig('router'));

            self::$container->set(get_class($this->interface_services['router']), $this->interface_services['router']);
            self::$container->setAlias('router', get_class($this->interface_services['router']));

        }

        // ------------------------- Veil (optional) -------------------------

        if (is_array(App::getConfig('veil'))) {

            self::$container->set('Bayfront\Veil\Veil', function() {
                return new Veil(App::getConfig('veil'));
            });

            self::$container->setAlias('veil', 'Bayfront\Veil\Veil');

        }

        // ------------------------- First event -------------------------

        $events->doEvent('bones.start'); // Not accessible by app

        // ------------------------- Include bootstrap -------------------------

        $this->safeInclude(App::resourcesPath('/bootstrap.php'), self::$container);

        // ------------------------- Load event and filter subscribers -------------------------

        $this->loadEventSubscribers($events);

        $this->loadFilterSubscribers($filters);

        // ------------------------- Bootstrap app -------------------------

        $events->doEvent('app.bootstrap', self::$container);

        if (App::getInterface() == App::INTERFACE_HTTP) {
            $this->startHttp($response, $events, $filters);
        } else if (App::getInterface() == App::INTERFACE_CLI) {
            $this->startCli($events, $filters);
        }

        // ------------------------- Shutdown -------------------------

        $events->doEvent('bones.end');

    }

    /**
     * @param Response $response
     * @param EventService $events
     * @param FilterService $filters
     * @return void
     * @throws ContainerException
     * @throws DispatchException
     * @throws InvalidStatusCodeException
     * @throws ServiceException
     * @throws NotFoundException
     */

    protected function startHttp(Response $response, EventService $events, FilterService $filters)
    {

        $events->doEvent('app.http');

        if (isset($this->interface_services['router'])) {

            $dispatcher = new RouterDispatcher(self::$container, $filters, $response, $this->interface_services['router']->resolve());
            $dispatcher->dispatchRoute();

        }

    }

    /**
     * @param EventService $events
     * @param FilterService $filters
     * @return void
     * @throws Exception
     */

    protected function startCli(EventService $events, FilterService $filters)
    {

        $console = new Application();

        /*
         * No need to add Application to container unless the app needs to interact with it
         * outside the app.cli event (see below).
         */

        //self::$container->put('Symfony\Component\Console\Application', $console);

        // ------------------------- Load Bones commands -------------------------

        $console->add(new AboutApp());
        $console->add(new AliasList(self::$container));
        $console->add(new ContainerList(self::$container));
        $console->add(new EventList($events));
        $console->add(new FilterList($filters));
        $console->add(new InstallBare());
        $console->add(new InstallService());
        $console->add(new KeyCreate());
        $console->add(new MakeCommand());
        $console->add(new MakeController());
        $console->add(new MakeEvent());
        $console->add(new MakeException());
        $console->add(new MakeFilter());
        $console->add(new MakeModel());
        $console->add(new MakeService());

        // Optional services

        if (isset($this->interface_services['scheduler'])) {

            $console->add(new ScheduleList($this->interface_services['scheduler']));
            $console->add(new ScheduleRun($this->interface_services['scheduler'], $events));
        }

        if (isset($this->interface_services['router'])) {

            $console->add(new RouteList($this->interface_services['router']));

        }

        // ------------------------- Load app commands -------------------------

        $this->loadAppCommands($console);

        // ------------------------- Run console -------------------------

        $console->setAutoExit(false);

        $events->doEvent('app.cli', $console);

        $console->run();

    }

    /**
     * Load event subscribers from the app config array.
     *
     * @param EventService $events
     * @return void
     * @throws ContainerException
     * @throws ServiceException
     * @throws NotFoundException
     */

    protected function loadEventSubscribers(EventService $events)
    {

        $dir = App::basePath('/app/Events');

        if (App::getConfig('app.events.autoload', false) && is_dir($dir)) {

            $list = new DirectoryIterator($dir);

            foreach ($list as $item) {

                if ($item->isFile()) {

                    $class = App::getConfig('app.namespace', '') . 'Events\\' . basename($item->getFileName(), '.php');

                    $events->addSubscriber(self::$container->make($class));

                }
            }

        } else {

            $list = App::getConfig('app.events.load', []);

            if (!empty($list)) {

                foreach ($list as $item) {
                    $events->addSubscriber(self::$container->make($item));
                }

            }

        }

    }

    /**
     * Load filter subscribers from the app config array.
     *
     * @param FilterService $filters
     * @return void
     * @throws ContainerException
     * @throws ServiceException
     * @throws NotFoundException
     */

    protected function loadFilterSubscribers(FilterService $filters)
    {

        $dir = App::basePath('/app/Filters');

        if (App::getConfig('app.filters.autoload', false) && is_dir($dir)) {

            $list = new DirectoryIterator($dir);

            foreach ($list as $item) {

                if ($item->isFile()) {

                    $class = App::getConfig('app.namespace', '') . 'Filters\\' . basename($item->getFileName(), '.php');

                    $filters->addSubscriber(self::$container->make($class));

                }
            }

        } else {

            $list = App::getConfig('app.filters.load', []);

            if (!empty($list)) {

                foreach ($list as $item) {
                    $filters->addSubscriber(self::$container->make($item));
                }

            }

        }

    }

    /**
     * Load app commands from the app config array.
     *
     * @param Application $console
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     */

    protected function loadAppCommands(Application $console)
    {

        $dir = App::basePath('/app/Console/Commands');

        if (App::getConfig('app.commands.autoload', false) && is_dir($dir)) {

            $list = new DirectoryIterator($dir);

            foreach ($list as $item) {

                if ($item->isFile()) {

                    $class = App::getConfig('app.namespace', '') . 'Console\Commands\\' . basename($item->getFileName(), '.php');

                    $command = self::$container->make($class);
                    $console->add($command);

                }
            }

        } else {

            $list = App::getConfig('app.commands.load', []);

            if (!empty($list)) {

                foreach ($list as $item) {
                    $console->add($item);
                }

            }

        }

    }

}