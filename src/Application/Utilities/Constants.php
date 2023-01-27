<?php

namespace Bayfront\Bones\Application\Utilities;

use Bayfront\Bones\Exceptions\ConstantAlreadyDefinedException;
use Bayfront\Bones\Exceptions\UndefinedConstantException;

class Constants
{

    protected static array $constants = [];

    /**
     * Is constant already defined?
     *
     * @param string $key
     * @return bool
     */

    public static function isDefined(string $key): bool
    {
        return isset(self::$constants[$key]);
    }

    /**
     * Define constant.
     *
     * @param string $key
     * @param $value
     * @return void
     * @throws ConstantAlreadyDefinedException
     */

    public static function define(string $key, $value): void
    {

        if (self::isDefined($key)) {
            throw new ConstantAlreadyDefinedException('Unable to define constant (' . $key . '): Constant already defined');
        }

        self::$constants[$key] = $value;

    }

    /**
     * Get constant.
     *
     * @param string $key
     * @return mixed
     * @throws UndefinedConstantException
     */

    public static function get(string $key): mixed
    {

        if (!self::isDefined($key)) {
            throw new UndefinedConstantException('Unable to get constant (' . $key . '): Constant undefined');
        }

        return self::$constants[$key];

    }

    /**
     * Return all defined constants.
     *
     * @return array
     */

    public static function getAll(): array
    {
        return self::$constants;
    }

    /**
     * Remove constant definition.
     *
     * @param string $key
     * @return void
     */

    public static function remove(string $key): void
    {
        if (self::isDefined($key)) {
            unset(self::$constants[$key]);
        }
    }

}