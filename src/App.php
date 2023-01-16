<?php

namespace Bayfront\Bones;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Console\Commands\About;
use Bayfront\Bones\Console\Commands\AliasList;
use Bayfront\Bones\Console\Commands\CacheClear;
use Bayfront\Bones\Console\Commands\ContainerList;
use Bayfront\Bones\Console\Commands\ActionList;
use Bayfront\Bones\Console\Commands\FilterList;
use Bayfront\Bones\Console\Commands\InstallBare;
use Bayfront\Bones\Console\Commands\KeyCreate;
use Bayfront\Bones\Console\Commands\MakeAction;
use Bayfront\Bones\Console\Commands\MakeCommand;
use Bayfront\Bones\Console\Commands\MakeController;
use Bayfront\Bones\Console\Commands\MakeException;
use Bayfront\Bones\Console\Commands\MakeFilter;
use Bayfront\Bones\Console\Commands\MakeModel;
use Bayfront\Bones\Console\Commands\MakeService;
use Bayfront\Bones\Console\Commands\RouteList;
use Bayfront\Bones\Console\Commands\ScheduleList;
use Bayfront\Bones\Console\Commands\ScheduleRun;
use Bayfront\Bones\Exceptions\ActionException;
use Bayfront\Bones\Exceptions\ErrorException;
use Bayfront\Bones\Exceptions\FileNotFoundException;
use Bayfront\Bones\Exceptions\FilterException;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Exceptions\InvalidConfigurationException;
use Bayfront\Bones\Exceptions\ModelException;
use Bayfront\Bones\Exceptions\ServiceException;
use Bayfront\Bones\Interfaces\ActionInterface;
use Bayfront\Bones\Interfaces\FilterInterface;
use Bayfront\Container\Container;
use Bayfront\Container\ContainerException;
use Bayfront\Container\NotFoundException;
use Bayfront\Filesystem\Exceptions\ConfigurationException;
use Bayfront\Hooks\Hooks;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
use Bayfront\MonologFactory\Exceptions\FormatterException;
use Bayfront\MonologFactory\Exceptions\HandlerException;
use Bayfront\MonologFactory\Exceptions\ProcessorException;
use Bayfront\PDO\DbFactory;
use Bayfront\PDO\Exceptions\ConfigurationException as PDOConfigurationException;
use Bayfront\PDO\Exceptions\InvalidDatabaseException;
use Bayfront\PDO\Exceptions\UnableToConnectException;
use Bayfront\RouteIt\DispatchException;
use Bayfront\RouteIt\Router;
use Bayfront\TimeHelpers\Time;
use Bayfront\Translation\AdapterException;
use DirectoryIterator;
use Dotenv\Dotenv;
use Exception;
use ReflectionException;
use Symfony\Component\Console\Application;

class App
{

    /** @var string */

    private static $interface; // App interface

    /** @var array */

    private static $config = []; // Arrays from loaded config files

    /** @var Container $container */

    private static $container; // Container instance

    /**
     * Load valid and active actions as a hooked event.
     *
     * @param Hooks $hooks
     * @param $class
     * @return void
     * @throws ActionException
     * @throws ContainerException
     */

    private static function loadAction(Hooks $hooks, $class)
    {

        $action = self::$container->create($class);

        if ($action instanceof ActionInterface) {

            if ($action->isActive()) {

                $events = $action->getEvents();

                foreach ($events as $event => $priority) {
                    $hooks->addEvent($event, [$action, 'action'], (int)$priority);
                }

            } else {
                unset($event);
            }

        } else {

            throw new ActionException('Unable to start: Invalid action (' . $class . ')');

        }

    }

    /**
     * Load valid and active filters as a hooked filter.
     *
     * @param Hooks $hooks
     * @param $class
     * @return void
     * @throws ContainerException
     * @throws FilterException
     */

    private static function loadFilter(Hooks $hooks, $class)
    {

        $filter = self::$container->create($class);

        if ($filter instanceof FilterInterface) {

            if ($filter->isActive()) {

                $filters = $filter->getFilters();

                foreach ($filters as $filter_name => $priority) {
                    $hooks->addFilter($filter_name, [$filter, 'action'], (int)$priority);
                }

            } else {
                unset($filter);
            }

        } else {

            throw new FilterException('Unable to start: Invalid filter (' . $filter . ')');

        }

    }

    /**
     * Include routes file without exposing the entire start() method.
     *
     * @param Container $container
     * @param Router $router
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */

    private static function loadRoutes(Container $container, Router $router): void
    {
        require(resources_path('/routes.php'));
    }

    /**
     * Include bootstrap file without exposing the entire start() method.
     *
     * @param Container $container
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */

    private static function loadBootstrap(Container $container): void
    {
        require(resources_path('/bootstrap.php'));
    }

    /**
     * Starts the app.
     *
     * @param string $base_path (Base path to app)
     * @param string $public_path (Path to /public)
     * @param string $interface
     *
     * @return void
     *
     * @throws AdapterException
     * @throws ConfigurationException
     * @throws ContainerException
     * @throws DispatchException
     * @throws ErrorException
     * @throws FileNotFoundException
     * @throws InvalidConfigurationException
     * @throws InvalidDatabaseException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws PDOConfigurationException
     * @throws ServiceException
     * @throws UnableToConnectException
     * @throws FormatterException
     * @throws HandlerException
     * @throws \Bayfront\MonologFactory\Exceptions\InvalidConfigurationException
     * @throws ProcessorException
     * @throws ReflectionException
     * @throws Exception
     */

    public static function start(string $base_path, string $public_path, string $interface = self::INTERFACE_HTTP): void
    {

        // ------------------------- Define constants -------------------------

        define('BONES_START', microtime(true));
        define('APP_BASE_PATH', rtrim($base_path, '/')); // Remove trailing slash
        define('APP_PUBLIC_PATH', rtrim($public_path, '/')); // Remove trailing slash
        define('BONES_BASE_PATH', rtrim(dirname(__FILE__, 2), '/')); // Base path to the Bones directory

        require(BONES_BASE_PATH . '/resources/constants.php');

        // ------------------------- Define interface -------------------------

        self::$interface = $interface;

        // ------------------------- Create container -------------------------

        self::$container = new Container();

        // ------------------------- Load environment variables -------------------------

        if (file_exists(APP_BASE_PATH . '/.env')) {
            Dotenv::createImmutable(APP_BASE_PATH)->load();
        }

        // ------------------------- Load app helpers -------------------------

        require(BONES_RESOURCES_PATH . '/helpers/app-helpers.php');

        // ------------------------- Set timezone -------------------------

        if (Time::isTimezone(get_config('app.timezone'))) {
            date_default_timezone_set(get_config('app.timezone'));
        } else {
            date_default_timezone_set('UTC');
        }

        // ------------------------- Debug mode errors -------------------------

        if (true === get_config('app.debug_mode')) { // Show all errors

            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');

        }

        // ------------------------- Set exception handler -------------------------

        set_exception_handler(function ($e) {

            /*
             * Get Response class
             *
             * If an HttpException, the Response class in the container has
             * already been reset and modified via the App::abort() method.
             */

            if ($e instanceof HttpException) {

                /** @var Response $response */

                $response = App::getFromContainer('Bayfront\HttpResponse\Response');

            } else {

                $response = new Response();

                $response->setStatusCode(500); // Default status code

            }

            /*
             * Do bones.exception event
             *
             * Pass the exception and response as arguments to the event.
             */

            if (App::getContainer()->has('Bayfront\Hooks\Hooks')) {

                App::getFromContainer('Bayfront\Hooks\Hooks')->doEvent('bones.exception', $e, $response);

            }

            /*
             * Search for the first available handler.
             *
             * This allows for an app-specific handler to override Bones,
             * and also ensures a handler will exist.
             */

            $handler_classes = [
                App::getConfig('app.namespace', 'App') . 'Exceptions\Handler\Handler',
                'Bayfront\Bones\Exceptions\Handler\Handler'
            ];

            foreach ($handler_classes as $class) {

                if (class_exists($class)) {

                    $handler = new $class();

                    // Report exception

                    if (!in_array(get_class($e), $handler->getExcludedClasses())) {

                        $handler->report($e);

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

        // ------------------------- Check for required app config -------------------------

        if (Arr::isMissing(get_config('app', []), [
            'namespace',
            'key',
            'debug_mode',
            'environment',
            'timezone',
            'actions',
            'filters'
        ])) {
            throw new InvalidConfigurationException('Unable to start: invalid app configuration');
        }

        // ------------------------- Check for required app resource files -------------------------

        if (!file_exists(APP_RESOURCES_PATH . '/bootstrap.php') ||
            !file_exists(APP_RESOURCES_PATH . '/routes.php')) {

            throw new FileNotFoundException('Unable to start: missing required app resource files');

        }

        /*
         * ############################################################
         * Add services to container
         * ############################################################
         */

        // ------------------------- Response (required) -------------------------

        /*
         * @throws Bayfront\Container\ContainerException
         */

        $response = self::$container->set('Bayfront\HttpResponse\Response',
            'Bayfront\HttpResponse\Response');

        // ------------------------- Hooks (required) -------------------------

        /*
         * @throws Bayfront\Container\ContainerException
         */

        $hooks = self::$container->set('Bayfront\Hooks\Hooks',
            'Bayfront\Hooks\Hooks');

        require(BONES_RESOURCES_PATH . '/helpers/services/hooks-helpers.php');

        // ------------------------- Cron scheduler (required) -------------------------

        $scheduler_config = [
            'lock_file_path' => storage_path('/app/temp'),
            'output_file' => storage_path('/app/cron/cron-' . date('Y-m-d') . '.txt')
        ];

        /*
         * Merge app config with default config, and ensure only valid keys are returned
         */

        if (is_array(get_config('scheduler'))) {

            $scheduler_config = Arr::only(array_merge($scheduler_config, get_config('scheduler')), [
                'lock_file_path',
                'output_file'
            ]);

        }

        /*
         * @throws Bayfront\Container\ContainerException
         */

        $schedule = self::$container->set('Bayfront\CronScheduler\Cron',
            'Bayfront\CronScheduler\Cron',
            $scheduler_config);

        // ------------------------- Router (required) -------------------------

        /*
         * @throws Bayfront\Container\ContainerException
         */

        $router = self::$container->set('Bayfront\RouteIt\Router',
            'Bayfront\RouteIt\Router', [
                'options' => get_config('router', [])
            ]);

        require(BONES_RESOURCES_PATH . '/helpers/services/router-helpers.php');

        // Include routes

        self::loadRoutes(self::getContainer(), $router);

        // ------------------------- Database (optional) -------------------------

        if (is_array(get_config('database'))) {

            /*
             * @throws Bayfront\PDO\Exceptions\ConfigurationException as PDOConfigurationException
             * @throws Bayfront\PDO\Exceptions\InvalidDatabaseException
             * @throws Bayfront\PDO\Exceptions\UnableToConnectException
             */

            $db = self::$container->set('Bayfront\PDO\DbFactory',
                'Bayfront\PDO\DbFactory',
                get_config('database'));

        }

        // ------------------------- Filesystem (optional) -------------------------

        if (is_array(get_config('filesystem'))) {

            /*
             * @throws ConfigurationException
             */

            $filesystem = self::$container->set('Bayfront\Filesystem\Filesystem',
                'Bayfront\Filesystem\Filesystem');

        }

        // ------------------------- Logs (optional) -------------------------

        if (is_array(get_config('logs'))) {

            /*
             * @throws Bayfront\LoggerFactory\Exceptions\LoggerException
             */

            $logs = self::$container->set('Bayfront\MonologFactory\LoggerFactory',
                'Bayfront\MonologFactory\LoggerFactory', [
                    'config' => get_config('logs')
                ]);

            require(BONES_RESOURCES_PATH . '/helpers/services/logs-helpers.php');

        }

        // ------------------------- Translate (optional) -------------------------

        if (true === get_config('translation.enabled')) {

            $interface = get_config('translation.adapter', '');

            if (class_exists('\\Bayfront\\Translation\\Adapters\\' . $interface)) {

                if ($interface == 'DefinedArray') {

                    /** @noinspection PhpFullyQualifiedNameUsageInspection */

                    $adapter = new \Bayfront\Translation\Adapters\DefinedArray(get_config('translation.translations', []));

                } else if ($interface == 'Local') {

                    /** @noinspection PhpFullyQualifiedNameUsageInspection */

                    $adapter = new \Bayfront\Translation\Adapters\Local(get_config('translation.root_path', resources_path('/translations')));

                } else if ($interface == 'PDO' && isset($db)) {

                    if ($db->isConnected(get_config('translation.db_id', ''))) { // If connected to db

                        /** @noinspection PhpFullyQualifiedNameUsageInspection */

                        $adapter = new \Bayfront\Translation\Adapters\PDO(
                            $db->get(get_config('translation.db_id', '')),
                            get_config('translation.table_name', 'translations')
                        );

                    }

                }

                if (isset($adapter)) {

                    $translate = self::$container->set('Bayfront\Translation\Translate',
                        'Bayfront\Translation\Translate', [
                            'storage' => $adapter,
                            'locale' => get_config('translation.locale', '')
                        ]);

                    require(BONES_RESOURCES_PATH . '/helpers/services/translate-helpers.php');

                } else {

                    throw new ServiceException('Unable to start service: translate- unable to create adapter');

                }

            } else {

                throw new ServiceException('Unable to start service: translate- adapter does not exist');

            }

        }

        // ------------------------- Veil (optional) -------------------------

        if (is_array(get_config('veil'))) {

            $veil = self::$container->set('Bayfront\Veil\Veil',
                'Bayfront\Veil\Veil', [
                    'options' => get_config('veil')
                ]);

            require(BONES_RESOURCES_PATH . '/helpers/services/veil-helpers.php');

        }

        // ------------------------- Load actions -------------------------

        if (get_config('app.actions.cache', false)
            && file_exists(storage_path('/app/cache/actions.ser'))) {

            $unserialized_actions = unserialize(file_get_contents(storage_path('/app/cache/actions.ser')));

            foreach ($unserialized_actions as $name => $actions) {

                foreach ($actions as $action) {

                    $hooks->addEvent($name, Arr::get($action, 'function'), Arr::get($action, 'priority'));

                }

            }

            unset($unserialized_actions);

        } else { // No cache exists

            $dir = base_path('/app/Actions');

            if (get_config('app.actions.autoload', false) && is_dir($dir)) {

                $list = new DirectoryIterator($dir);

                foreach ($list as $item) {

                    if ($item->isFile()) {

                        $class = get_config('app.namespace', '') . 'Actions\\' . basename($item->getFileName(), '.php');

                        self::loadAction($hooks, $class);

                    }
                }

            } else {

                $list = get_config('app.actions.load', []);

                if (!empty($list)) {

                    foreach ($list as $item) {
                        self::loadAction($hooks, $item);
                    }

                }

            }

            // Cache

            if (get_config('app.actions.cache', false)) {

                if (!is_dir(storage_path('/app/cache'))) {
                    mkdir(storage_path('/app/cache'), 0755, true);
                }

                $serialized_actions = serialize($hooks->getEvents());

                if (!file_put_contents(storage_path('/app/cache/actions.ser'), $serialized_actions)) {

                    throw new ActionException('Unable to cache actions');

                }

                unset($serialized_actions);

            }

        }

        // ------------------------- Load filters -------------------------

        if (get_config('app.filters.cache', false)
            && file_exists(storage_path('/app/cache/filters.ser'))) {

            $unserialized_filters = unserialize(file_get_contents(storage_path('/app/cache/filters.ser')));

            foreach ($unserialized_filters as $name => $actions) {

                foreach ($actions as $action) {

                    $hooks->addFilter($name, Arr::get($action, 'function'), Arr::get($action, 'priority'));

                }

            }

            unset($unserialized_filters);

        } else { // No cache exists

            $dir = base_path('/app/Filters');

            if (get_config('app.filters.autoload', false) && is_dir($dir)) {

                $list = new DirectoryIterator($dir);

                foreach ($list as $item) {

                    if ($item->isFile()) {

                        $class = get_config('app.namespace', '') . 'Filters\\' . basename($item->getFileName(), '.php');

                        self::loadFilter($hooks, $class);

                    }
                }

            } else {

                $list = get_config('app.filters.load', []);

                if (!empty($list)) {

                    foreach ($list as $item) {
                        self::loadFilter($hooks, $item);
                    }

                }

            }

            // Cache

            if (get_config('app.filters.cache', false)) {

                if (!is_dir(storage_path('/app/cache'))) {
                    mkdir(storage_path('/app/cache'), 0755, true);
                }

                $serialized_filters = serialize($hooks->getFilters());

                if (!file_put_contents(storage_path('/app/cache/filters.ser'), $serialized_filters)) {

                    throw new ActionException('Unable to cache filters');

                }

                unset($serialized_filters);

            }

        }

        // ------------------------- First event -------------------------

        /*
         * Now that all Bones services exist in the container,
         * trigger the first event.
         */

        /*
         * @throws Bayfront\Hooks\ActionException
         */

        $hooks->doEvent('bones.init');

        // ------------------------- Bootstrap app / event -------------------------

        self::loadBootstrap(self::getContainer());

        /*
         * @throws Bayfront\Hooks\ActionException
         */

        $hooks->doEvent('app.bootstrap');

        /*
         * From here, respond depending on the interface
         */

        if ($interface == self::INTERFACE_CLI) {

            $console = new Application();

            self::$container->put('Symfony\Component\Console\Application',
                $console);

            $hooks->doEvent('app.cli', $console);

            $console->add(new About());
            $console->add(new ContainerList(self::$container));
            $console->add(new ActionList($hooks));
            $console->add(new AliasList(self::$container));
            $console->add(new CacheClear());
            $console->add(new FilterList($hooks));
            $console->add(new InstallBare());
            $console->add(new KeyCreate());
            $console->add(new MakeAction());
            $console->add(new MakeCommand());
            $console->add(new MakeController());
            $console->add(new MakeException());
            $console->add(new MakeFilter());
            $console->add(new MakeModel());
            $console->add(new MakeService());
            $console->add(new RouteList($router));
            $console->add(new ScheduleList($schedule));
            $console->add(new ScheduleRun($schedule));

            $console->setAutoExit(false);
            $console->run();

        } else { // HTTP

            /*
             * @throws Bayfront\Hooks\ActionException
             */

            $hooks->doEvent('app.http');

            // ------------------------- Router dispatch -------------------------

            /*
             * @throws Bayfront\RouteIt\DispatchException
             */

            $router->dispatch($hooks->doFilter('router.parameters', []));

        }

        // ------------------------- Last event -------------------------

        define('BONES_END', microtime(true));

        /*
         * @throws Bayfront\Hooks\ActionException
         */

        $hooks->doEvent('bones.shutdown');

    }

    /**
     * App interfaces.
     */

    public const INTERFACE_CLI = 'CLI';
    public const INTERFACE_HTTP = 'HTTP';

    /**
     * Return App interface.
     *
     * @return string
     */

    public static function getInterface(): string
    {
        return self::$interface;
    }

    /**
     * Returns value from a configuration array key using dot notation,
     * with the first segment being the filename. (e.g.: filename.key)
     *
     * @param $key string (Key to retrieve in dot notation)
     * @param $default mixed (Default value to return)
     *
     * @return mixed
     */

    public static function getConfig(string $key, $default = NULL)
    {

        if (!Arr::has(self::$config, $key)) { // If value does not exist on config array

            $exp = explode('.', $key, 2); // $exp[0] = filename

            if (Arr::has(self::$config, $exp[0])) { // File has been loaded, but value does not exist
                return $default;
            }

            if (!file_exists(APP_CONFIG_PATH . '/' . $exp[0] . '.php')) {

                self::$config[$exp[0]] = []; // Save empty array so file is not searched for again

                return $default;

            }

            $config = require(APP_CONFIG_PATH . '/' . $exp[0] . '.php');

            if (!is_array($config)) { // Invalid format

                self::$config[$exp[0]] = []; // Save empty array so file is not required again

                return $default;

            }

            self::$config[$exp[0]] = $config;

        }

        return Arr::get(self::$config, $key, $default);

    }

    /**
     * Returns instance of the service container.
     *
     * @return Container
     */

    public static function getContainer(): Container
    {
        return self::$container;
    }

    protected static $bones_aliases = [ // Aliases for classes in the container
        'response' => 'Bayfront\HttpResponse\Response',
        'hooks' => 'Bayfront\Hooks\Hooks',
        'schedule' => 'Bayfront\CronScheduler\Cron',
        'router' => 'Bayfront\RouteIt\Router',
        'db' => 'Bayfront\PDO\DbFactory',
        'files' => 'Bayfront\Filesystem\Filesystem',
        'logs' => 'Bayfront\MonologFactory\LoggerFactory',
        'translate' => 'Bayfront\Translation\Translate',
        'veil' => 'Bayfront\Veil\Veil',
        'console' => 'Symfony\Component\Console\Application'
    ];

    /**
     * Return all known aliases, giving priority to $bones_aliases.
     *
     * @return array
     */

    public static function getAliases(): array
    {
        return array_merge(get_config('app.aliases', []), self::$bones_aliases);
    }

    /**
     * Does container have an instance with ID or alias.
     *
     * @param string $id
     *
     * @return bool
     */

    public static function inContainer(string $id): bool
    {

        // Check alias

        if (isset(self::getAliases()[$id]) && self::$container->has(self::getAliases()[$id])) {
            return true;
        }

        // Class

        return self::$container->has($id);

    }

    /**
     * Returns instance from the service container by ID or alias.
     *
     * @param string $id
     *
     * @return mixed
     *
     * @throws NotFoundException
     */

    public static function getFromContainer(string $id)
    {

        // Check alias

        if (isset(self::getAliases()[$id]) && self::$container->has(self::getAliases()[$id])) {
            return self::$container->get(self::getAliases()[$id]);
        }

        // Class

        return self::$container->get($id);

    }

    /**
     * Saves a preexisting class instance into the container identified by `$id`.
     *
     * If another entry exists in the container with the same $id, it will be overwritten.
     *
     * Saving a class instance to the container using its namespaced name as the $id
     * will allow it to be used by the container whenever another class requires it as a dependency.
     *
     * @param string $id
     * @param object $object
     *
     * @return void
     */

    public static function putInContainer(string $id, object $object): void
    {
        self::$container->put($id, $object);
    }

    /**
     * Creates a class instance using create(), and saves it into the container identified by $id.
     * An instance of the class will be returned.
     *
     * If another entry exists in the container with the same $id, it will be overwritten.
     *
     * Saving a class instance to the container using its namespaced name as the $id
     * will allow it to be used by the container whenever another class requires it as a dependency.
     *
     * @param string $id
     * @param string $class
     * @param array $params
     *
     * @return mixed
     *
     * @throws ContainerException
     */

    public static function setInContainer(string $id, string $class, array $params = [])
    {
        return self::$container->set($id, $class, $params);
    }

    /**
     * Returns a class instance using dependency injection.
     *
     * If $force_unique = false, the instance will be saved in the container if not already existing,
     * or will be retrieved from the container if it already exists.
     *
     * @param string $class
     * @param array $params (Parameters to pass to the class constructor)
     * @param bool $force_unique (Force return a new class instance by ignoring if it already exists in the container)
     *
     * @return object
     *
     * @throws ContainerException
     * @throws NotFoundException
     * @throws FileNotFoundException
     */

    private static function _getClass(string $class, array $params = [], bool $force_unique = false): object
    {

        $namespaces = [
            self::getConfig('app.namespace', '') . $class,
            'Bayfront\Bones\\' . $class
        ];

        foreach ($namespaces as $namespace) {

            if (class_exists($namespace)) {

                if (true === $force_unique) { // Force unique instance?

                    /*
                     * @throws Bayfront\Container\ContainerException
                     */

                    return self::$container->create($namespace, $params, true);

                }

                // Set/get a saved instance using the container

                if (self::$container->has($namespace)) {

                    /*
                     * @throws Bayfront\Container\NotFoundException
                     */

                    return self::$container->get($namespace);

                }

                // Set into container

                /*
                 * @throws Bayfront\Container\ContainerException
                 */

                return self::$container->set($namespace, $namespace, $params);

            }

        }

        // Class does not exist in either namespace

        throw new FileNotFoundException('Unable to get class: ' . $class);

    }

    /**
     * Returns a class instance in the models namespace as defined in the app config array.
     *
     * @param string $model (Class name in the models namespace)
     * @param array $params (Key/value pairs to be injected into the model's constructor)
     * @param bool $force_unique
     *
     * @return object
     *
     * @throws ModelException
     */

    public static function getModel(string $model, array $params = [], bool $force_unique = false): object
    {

        // Get full namespaced class name

        $model = 'Models\\' . $model;

        try {

            return self::_getClass($model, $params, $force_unique);

        } catch (ContainerException|NotFoundException|FileNotFoundException $e) {

            throw new ModelException('Unable to get model: ' . $model, 0, $e);

        }

    }

    /**
     * Returns a class instance in the services namespace as defined in the app config array.
     *
     * @param string $service (Class name in the services namespace)
     * @param array $params (Key/value pairs to be injected into the service's constructor)
     * @param bool $force_unique
     *
     * @return object
     *
     * @throws ServiceException
     */

    public static function getService(string $service, array $params = [], bool $force_unique = false): object
    {

        // Get full namespaced class name

        $service = 'Services\\' . $service;

        try {

            return self::_getClass($service, $params, $force_unique);

        } catch (ContainerException|NotFoundException|FileNotFoundException $e) {

            throw new ServiceException('Unable to get service: ' . $service, 0, $e);

        }

    }

    /**
     * Include helper file(s) located in the /resources/helpers directory.
     *
     * @param string|array $helpers (Helper file(s) to include)
     *
     * @return void
     *
     * @throws FileNotFoundException
     */

    public static function useHelper($helpers): void
    {

        foreach ((array)$helpers as $helper) {

            $helper = trim($helper, '/'); // Sanitize string

            if (!is_file(APP_RESOURCES_PATH . '/helpers/' . $helper . '.php')) {

                throw new FileNotFoundException('Helper file not found: ' . $helper);

            }

            require_once(APP_RESOURCES_PATH . '/helpers/' . $helper . '.php');

        }

    }

    /**
     * Create a cryptographically secure key of random bytes.
     *
     * @param int $characters (Number of characters of binary data)
     *
     * @return string
     *
     * @throws Exception
     */

    public static function createKey(int $characters = 32): string
    {
        return bin2hex(random_bytes($characters));
    }

    /**
     * Abort script execution by throwing a Bayfront\Bones\Exceptions\HttpException and send response message.
     *
     * If no message is provided, the phrase for the HTTP status code will be used.
     *
     * @param int $code (HTTP status code for response)
     * @param string $message (Message to be sent with response)
     * @param array $headers (Key/value pairs of headers to be sent with the response)
     * @param bool $reset_response (Reset the HTTP response after fetching it from the services container)
     *
     * @return void
     *
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */

    public static function abort(int $code, string $message = '', array $headers = [], bool $reset_response = false): void
    {

        /** @var Response $response */

        $response = self::$container->get('Bayfront\HttpResponse\Response');

        if (true === $reset_response) {

            $response->reset();

        }

        $response->setStatusCode($code)->setHeaders($headers);

        if ($message == '') {

            $message = $response->getStatusCode()['phrase'];

        }

        throw new HttpException($message);

    }

}