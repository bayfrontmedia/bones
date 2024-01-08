<?php

namespace Bayfront\Bones;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Kernel\Bridge\RouterDispatcher;
use Bayfront\Bones\Application\Kernel\Console\Commands\AboutBones;
use Bayfront\Bones\Application\Kernel\Console\Commands\AliasList;
use Bayfront\Bones\Application\Kernel\Console\Commands\CacheClear;
use Bayfront\Bones\Application\Kernel\Console\Commands\CacheList;
use Bayfront\Bones\Application\Kernel\Console\Commands\CacheSave;
use Bayfront\Bones\Application\Kernel\Console\Commands\ContainerList;
use Bayfront\Bones\Application\Kernel\Console\Commands\EventList;
use Bayfront\Bones\Application\Kernel\Console\Commands\FilterList;
use Bayfront\Bones\Application\Kernel\Console\Commands\InstallKey;
use Bayfront\Bones\Application\Kernel\Console\Commands\InstallService;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeKey;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeCommand;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeController;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeEvent;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeException;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeFilter;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeMigration;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeModel;
use Bayfront\Bones\Application\Kernel\Console\Commands\MakeService;
use Bayfront\Bones\Application\Kernel\Console\Commands\MigrateDown;
use Bayfront\Bones\Application\Kernel\Console\Commands\MigrateUp;
use Bayfront\Bones\Application\Kernel\Console\Commands\MigrationList;
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
use Bayfront\CronScheduler\FilesystemException;
use Bayfront\Encryptor\Encryptor;
use Bayfront\Hooks\Hooks;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
use Bayfront\PDO\DbFactory;
use Bayfront\PDO\Exceptions\ConfigurationException;
use Bayfront\PDO\Exceptions\InvalidDatabaseException;
use Bayfront\PDO\Exceptions\UnableToConnectException;
use Bayfront\RouteIt\DispatchException;
use Bayfront\RouteIt\Router;
use Bayfront\TimeHelpers\Time;
use Bayfront\Veil\Veil;
use Dotenv\Dotenv;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Application;

class Bones
{

    /** @var Container */

    public static Container $container;

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

    protected function safeInclude(string $file, Container $container): void
    {
        include($file);
    }

    /**
     * Services to pass to the proper method based on interface.
     * startHttp, startCli
     *
     * @var array
     */

    protected array $interface_services = [];

    /**
     * Start app.
     *
     * @param string $interface
     * @return void
     * @throws ConstantAlreadyDefinedException
     * @throws ContainerException
     * @throws DispatchException
     * @throws ErrorException
     * @throws InvalidConfigurationException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws ServiceException
     * @throws UndefinedConstantException
     * @throws FilesystemException
     * @throws ConfigurationException
     * @throws InvalidDatabaseException
     * @throws UnableToConnectException
     * @throws Exception
     */

    public function start(string $interface): void
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
        Constants::define('BONES_VERSION', '4.0.0');

        // ------------------------- Load environment variables -------------------------

        if (file_exists(App::basePath('/.env'))) {
            Dotenv::createImmutable(App::basePath())->load();
        }

        // ------------------------- Encryptor -------------------------

        /*
         * NOTE:
         *
         * This must be created and added to the container before the first
         * time App::getConfig() is used, as it may be needed to decrypt
         * cached config values.
         */

        $encryptor = new Encryptor(App::getEnv('APP_KEY'));

        self::$container->set(get_class($encryptor), $encryptor);
        self::$container->setAlias('encryptor', get_class($encryptor));

        // ------------------------- Set timezone -------------------------

        if (Time::isTimezone(App::getConfig('app.timezone', ''))) {
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

            if (self::$container->has('Bayfront\HttpResponse\Response')) {

                /** @var Response $response */

                $response = self::$container->get('Bayfront\HttpResponse\Response');

            } else {

                $response = new Response();

                self::$container->set('Bayfront\HttpResponse\Response', $response);

            }

            /*
             * If an HttpException, the status code has already been set by App::abort().
             * Otherwise, set status code to 500.
             */

            if (!$e instanceof HttpException) {
                $response->setStatusCode(500); // Default status code
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

                    if (isset($events)) {
                        $events->doEvent('bones.end');
                    }

                    return; // Stop iteration

                }

            }

            /*
             * No handler existed.
             * This should never happen, but accounted for out of the
             * abundance of caution.
             */

            echo '<h1>Error: ' . $e->getMessage() . '</h1>';

            if (isset($events)) {
                $events->doEvent('bones.end');
            }

        });

        // ------------------------- Set error handler -------------------------

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {

            $ename = 'Unknown error';

            // Get name of error from its number

            $constants = get_defined_constants(1);

            foreach ($constants['Core'] as $key => $value) {

                if (str_starts_with($key, 'E_') && $errno == $value) {

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
            'timezone'
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

            $this->interface_services['db'] = DbFactory::create(App::getConfig('database'));

            self::$container->set(get_class($this->interface_services['db']), $this->interface_services['db']);
            self::$container->setAlias('db', get_class($this->interface_services['db']));

        }

        // ------------------------- Router (optional) -------------------------

        if (is_array(App::getConfig('router'))) {

            $this->interface_services['router'] = new Router(App::getConfig('router'));

            self::$container->set(get_class($this->interface_services['router']), $this->interface_services['router']);
            self::$container->setAlias('router', get_class($this->interface_services['router']));

        }

        // ------------------------- Veil (optional) -------------------------

        if (is_array(App::getConfig('veil'))) {

            self::$container->set('Bayfront\Veil\Veil', function () {
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
            $this->startCli($encryptor, $events, $filters);
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
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws ServiceException
     */

    protected function startHttp(Response $response, EventService $events, FilterService $filters): void
    {

        $events->doEvent('app.http');

        // Check maintenance mode

        if (App::isDown()) {

            $down = json_decode(file_get_contents(App::storagePath('/bones/down.json')), true);

            if (!in_array(Request::getIp(), Arr::get($down, 'allow', []))) {
                App::abort(503, Arr::get($down, 'message', ''));
            }

        }

        // Dispatch route

        if (isset($this->interface_services['router'])) {

            $dispatcher = new RouterDispatcher(self::$container, $events, $filters, $response, $this->interface_services['router']->resolve());
            $dispatcher->dispatchRoute();

        }

    }

    /**
     * @param Encryptor $encryptor
     * @param EventService $events
     * @param FilterService $filters
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws Exception
     */

    protected function startCli(Encryptor $encryptor, EventService $events, FilterService $filters): void
    {

        $console = new Application();

        /*
         * No need to add Application to container unless the app needs to interact with it
         * outside the app.cli event (see below).
         */

        //self::$container->put('Symfony\Component\Console\Application', $console);

        // ------------------------- Load Bones commands -------------------------

        $console->add(new AboutBones($filters));
        $console->add(new AliasList(self::$container));
        $console->add(new CacheClear());
        $console->add(new CacheList($encryptor));
        $console->add(new CacheSave($encryptor));
        $console->add(new ContainerList(self::$container));
        $console->add(App::make('Bayfront\Bones\Application\Kernel\Console\Commands\Down'));
        $console->add(new EventList($events));
        $console->add(new FilterList($filters));
        $console->add(new InstallKey());
        $console->add(new InstallService());
        $console->add(new MakeCommand());
        $console->add(new MakeController());
        $console->add(new MakeEvent());
        $console->add(new MakeException());
        $console->add(new MakeFilter());
        $console->add(new MakeKey());
        $console->add(new MakeModel());
        $console->add(new MakeService());
        $console->add(App::make('Bayfront\Bones\Application\Kernel\Console\Commands\Up'));

        // Optional services

        if (isset($this->interface_services['scheduler'])) {

            $console->add(new ScheduleList($this->interface_services['scheduler']));
            $console->add(new ScheduleRun($this->interface_services['scheduler'], $events));
        }

        if (isset($this->interface_services['db'])) {

            $console->add(new MakeMigration());
            $console->add(new MigrateDown(self::$container, $this->interface_services['db']));
            $console->add(new MigrateUp(self::$container, $this->interface_services['db']));
            $console->add(new MigrationList($this->interface_services['db']));

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

    protected function loadEventSubscribers(EventService $events): void
    {

        $dir = App::basePath('/app/Events');

        if (is_dir($dir)) {

            if (is_file(App::storagePath('/bones/cache/events.json'))) {

                $cache = json_decode(file_get_contents(App::storagePath('/bones/cache/events.json')), true);

                foreach ($cache as $class) {
                    $events->addSubscriber(self::$container->make($class));
                }

            } else {

                $list = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

                foreach ($list as $item) {

                    if ($item->isFile() && $item->getExtension() == 'php') {

                        $namespace = ltrim(str_replace([
                            '.php',
                            '/'
                        ], [
                            '',
                            '\\'
                        ], str_replace($dir, '', $item->getPathName())), '\\');

                        $class = App::getConfig('app.namespace', '') . 'Events\\' . $namespace;

                        $events->addSubscriber(self::$container->make($class));

                    }

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

    protected function loadFilterSubscribers(FilterService $filters): void
    {

        $dir = App::basePath('/app/Filters');

        if (is_dir($dir)) {

            if (is_file(App::storagePath('/bones/cache/filters.json'))) {

                $cache = json_decode(file_get_contents(App::storagePath('/bones/cache/filters.json')), true);

                foreach ($cache as $class) {
                    $filters->addSubscriber(self::$container->make($class));
                }

            } else {

                $list = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

                foreach ($list as $item) {

                    if ($item->isFile() && $item->getExtension() == 'php') {

                        $namespace = ltrim(str_replace([
                            '.php',
                            '/'
                        ], [
                            '',
                            '\\'
                        ], str_replace($dir, '', $item->getPathName())), '\\');

                        $class = App::getConfig('app.namespace', '') . 'Filters\\' . $namespace;

                        $filters->addSubscriber(self::$container->make($class));

                    }

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

    protected function loadAppCommands(Application $console): void
    {

        $dir = App::basePath('/app/Console/Commands');

        if (is_dir($dir)) {

            if (is_file(App::storagePath('/bones/cache/commands.json'))) {

                $cache = json_decode(file_get_contents(App::storagePath('/bones/cache/commands.json')), true);

                foreach ($cache as $class) {

                    $command = self::$container->make($class);
                    $console->add($command);

                }

            } else {

                $list = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

                foreach ($list as $item) {

                    if ($item->isFile() && $item->getExtension() == 'php') {

                        $namespace = ltrim(str_replace([
                            '.php',
                            '/'
                        ], [
                            '',
                            '\\'
                        ], str_replace($dir, '', $item->getPathName())), '\\');

                        $class = App::getConfig('app.namespace', '') . 'Console\Commands\\' . $namespace;

                        $command = self::$container->make($class);
                        $console->add($command);

                    }

                }

            }

        }

    }

}