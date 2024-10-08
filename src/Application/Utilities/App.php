<?php /** @noinspection PhpUnused */

namespace Bayfront\Bones\Application\Utilities;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Bones;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Exceptions\InvalidArgumentException;
use Bayfront\Bones\Exceptions\UndefinedConstantException;
use Bayfront\Container\Container;
use Bayfront\Container\ContainerException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
use Exception;

class App
{

    /**
     * These constants can be used to check against the environment().
     */

    public const ENV_DEV = 'dev';
    public const ENV_STAGING = 'staging';
    public const ENV_QA = 'qa';
    public const ENV_PROD = 'prod';

    /**
     * Return app environment value.
     *
     * @return string
     */

    public static function environment(): string
    {
        return self::getConfig('app.environment', '');
    }

    /**
     * Is app in debug mode?
     *
     * @return bool
     */

    public static function isDebug(): bool
    {
        return self::getConfig('app.debug', true);
    }

    /**
     * These constants can be used to check against the interface.
     */

    public const INTERFACE_CLI = 'CLI';
    public const INTERFACE_HTTP = 'HTTP';

    /**
     * Return app interface.
     *
     * @return string
     */

    public static function getInterface(): string
    {
        try {
            return Constants::get('APP_INTERFACE');
        } catch (UndefinedConstantException) {
            return '';
        }
    }

    /**
     * Return value of environment variable, or default value if not existing.
     *
     * Strings "true", "false" and "null" will be cast to their respective types.
     *
     * NOTE: This method should rarely be used outside a config file.
     *
     * @param string $key
     * @param $default (Default value to return if not existing)
     * @return mixed
     */

    public static function getEnv(string $key, $default = null): mixed
    {

        if (isset($_ENV[$key])) {

            // Convert string to type

            return match ($_ENV[$key]) {
                'true' => true,
                'false' => false,
                'null' => NULL,
                default => $_ENV[$key],
            };

        }

        return $default;

    }

    /**
     * Does environment variable exist?
     *
     * @param string $key
     * @return bool
     */

    public static function envHas(string $key): bool
    {
        return isset($_ENV[$key]);
    }

    protected static bool $config_cached = false; // Are config values from cache?
    protected static array $config = []; // Config values

    /**
     * Returns value from a configuration array key using dot notation,
     * with the first segment being the filename. (e.g.: filename.key)
     *
     * These values may be cached.
     *
     * @param $key string (Key to retrieve in dot notation)
     * @param $default mixed|null (Default value to return if not existing)
     *
     * @return mixed
     */

    public static function getConfig(string $key, mixed $default = null): mixed
    {

        if (self::$config_cached === false
            && empty(self::$config)
            && is_file(self::storagePath('/bones/cache/config'))) { // Check for cache

            try {

                $encryptor = self::get('Bayfront\Encryptor\Encryptor');

                self::$config_cached = true;
                self::$config = json_decode($encryptor->decryptString(file_get_contents(self::storagePath('/bones/cache/config'))), true);

            } catch (Exception) { // Fail silently

                self::$config_cached = true;
                self::$config = [];

            }

        }

        if (self::$config_cached) {
            return Arr::get(self::$config, $key, $default);
        }

        // Config not cached...

        if (!Arr::has(self::$config, $key)) { // If value does not exist on config array

            $exp = explode('.', $key, 2); // $exp[0] = filename

            if (Arr::has(self::$config, $exp[0])) { // File has been loaded, but value does not exist
                return $default;
            }

            if (!file_exists(self::configPath('/' . $exp[0] . '.php'))) {

                self::$config[$exp[0]] = []; // Save empty array so file is not searched for again

                return $default;

            }

            $config = require(self::configPath('/' . $exp[0] . '.php'));

            if (!is_array($config)) { // Invalid format

                self::$config[$exp[0]] = []; // Save empty array so file is not required again

                return $default;

            }

            self::$config[$exp[0]] = $config;

        }

        return Arr::get(self::$config, $key, $default);

    }

    /**
     * Return base path.
     *
     * @param string $path
     * @return string
     */

    public static function basePath(string $path = ''): string
    {
        try {
            return Constants::get('APP_BASE_PATH') . '/' . ltrim($path, '/');
        } catch (UndefinedConstantException) {
            return '';
        }
    }

    /**
     * Return public path.
     *
     * @param string $path
     * @return string
     */

    public static function publicPath(string $path = ''): string
    {
        try {
            return Constants::get('APP_PUBLIC_PATH') . '/' . ltrim($path, '/');
        } catch (UndefinedConstantException) {
            return '';
        }
    }

    /**
     * Return config path.
     *
     * @param string $path
     * @return string
     */

    public static function configPath(string $path = ''): string
    {
        try {
            return Constants::get('APP_CONFIG_PATH') . '/' . ltrim($path, '/');
        } catch (UndefinedConstantException) {
            return '';
        }
    }

    /**
     * Return resources path.
     *
     * @param string $path
     * @return string
     */

    public static function resourcesPath(string $path = ''): string
    {
        try {
            return Constants::get('APP_RESOURCES_PATH') . '/' . ltrim($path, '/');
        } catch (UndefinedConstantException) {
            return '';
        }
    }

    /**
     * Return storage path.
     *
     * @param string $path
     * @return string
     */

    public static function storagePath(string $path = ''): string
    {
        try {
            return Constants::get('APP_STORAGE_PATH') . '/' . ltrim($path, '/');
        } catch (UndefinedConstantException) {
            return '';
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
     * Create a reproducible keyed hash value.
     *
     * @param string $data (Data to be hashed)
     * @param string $key (Secret key)
     * @param string $algo (Any valid hash algorithm)
     * @param bool $binary
     * @return string
     */
    public static function createHash(string $data, string $key, string $algo = 'sha256', bool $binary = false): string
    {
        return hash_hmac($algo, $data, $key, $binary);
    }

    /**
     * Return a one-way password hash using plaintext data and user-specific key.
     *
     * The hash created from this method utilizes the Bones app key
     * to essentially create a password hash using data supplied by
     * the user, database and server.
     *
     * @param string $data (Plaintext input/password)
     * @param string $key (User-specific secret/salt)
     * @param string $algo (Any valid hash algorithm)
     * @param string $password_algo (Any valid password algorithm constant)
     * @param array $options (Any valid algorithm constant options)
     *
     * @return string (Hashed password)
     */
    public static function createPasswordHash(string $data, string $key, string $algo = 'sha256', string $password_algo = PASSWORD_DEFAULT, array $options = []): string
    {

        $key = hash_hmac($algo, $key, App::getConfig('app.key', '')); // Database & server supplied

        $key = hash_hmac($algo, $key, $data); // User supplied

        return password_hash($key . $data, $password_algo, $options); // Create a one-way hash, verified using password_verify

    }

    /**
     * Verify a one-way password hash using plaintext data and user-specific key.
     *
     * @param string $data (Plaintext input/password)
     * @param string $key (User-specific secret/salt)
     * @param string $hashed_password
     * @param string $algo (Any valid hash algorithm)
     * @return bool
     */
    public static function isPasswordHashValid(string $data, string $key, string $hashed_password, string $algo = 'sha256'): bool
    {

        $key = hash_hmac($algo, $key, App::getConfig('app.key', '')); // Database & server supplied

        $key = hash_hmac($algo, $key, $data); // User supplied

        return password_verify($key . $data, $hashed_password); // Verify one-way hash

    }

    /**
     * Return elapsed time in seconds since Bones was instantiated.
     *
     * @param float $timestamp (Uses current time if 0)
     * @param int $decimals
     * @return string
     */

    public static function getElapsedTime(float $timestamp = 0, int $decimals = 3): string
    {
        if ($timestamp == 0) {
            $timestamp = microtime(true);
        }

        try {
            $start = Constants::get('BONES_START');
        } catch (UndefinedConstantException) {
            $start = microtime(true);
        }

        return number_format($timestamp - $start, $decimals);
    }

    /**
     * Return Bones version.
     *
     * @return string
     */

    public static function getBonesVersion(): string
    {
        try {
            return Constants::get('BONES_VERSION');
        } catch (UndefinedConstantException) {
            return '';
        }
    }

    /**
     * Get container instance.
     *
     * @return Container
     */

    public static function getContainer(): Container
    {
        return Bones::$container;
    }

    /**
     * Set an entry into the container.
     *
     * Anonymous functions (closures) are called on the first get().
     *
     * @param string $id
     * @param mixed $value
     * @param bool $overwrite
     * @return void
     * @throws ContainerException
     */

    public static function set(string $id, mixed $value, bool $overwrite = false): void
    {
        self::getContainer()->set($id, $value, $overwrite);
    }

    /**
     * Get an entry from the container by its ID or alias.
     *
     * @param string $id
     * @return mixed
     * @throws NotFoundException
     */

    public static function get(string $id): mixed
    {
        return self::getContainer()->get($id);
    }

    /**
     * Use the container to make and return a new class instance,
     * automatically injecting dependencies which exist in the container.
     *
     * @param string $class (Fully namespaced class name)
     * @param array $params (Additional parameters to pass to the class constructor)
     * @return mixed
     * @throws ContainerException
     * @throws NotFoundException
     */

    public static function make(string $class, array $params = []): mixed
    {
        return self::getContainer()->make($class, $params);
    }

    /**
     * Does entry or alias exist in the container?
     * (ie: Can an entry be resolved using get() with this ID?)
     *
     * @param string $id
     * @return bool
     */

    public static function has(string $id): bool
    {
        return self::getContainer()->has($id);
    }

    /**
     * Abort script execution by throwing an HttpException and send response message.
     *
     * If no message is provided, the phrase for the HTTP status code will be used.
     *
     * @param int $status_code (HTTP status code for response)
     * @param string $message (Response message)
     * @param array $headers (Key/value pairs of headers to be sent with the response)
     * @param int $code (Code to send with the thrown exception)
     * @return void
     * @return never-return
     * @throws HttpException
     * @throws InvalidArgumentException
     */

    public static function abort(int $status_code, string $message = '', array $headers = [], int $code = 0): void
    {

        /** @var Response $response */

        try {
            $response = self::getContainer()->get('Bayfront\HttpResponse\Response');
            $response->setStatusCode($status_code)->setHeaders($headers);
        } catch (NotFoundException|InvalidStatusCodeException) {
            throw new InvalidArgumentException('Unable to abort: invalid status code or response not found');
        }

        if ($message == '') {
            $message = $response->getStatusCode()['phrase'];
        }

        throw new HttpException($message, $code);

    }

    /**
     * Is Bones down?
     *
     * @return bool
     */
    public static function isDown(): bool
    {
        return file_exists(App::storagePath('/bones/down.json'));
    }

}