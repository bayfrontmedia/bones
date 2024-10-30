<?php

namespace Bayfront\Bones\Application\Utilities;

class Helpers
{

    /**
     * Recursively return the traits used by the given class and all of its parent classes.
     *
     * See: https://laravel.com/docs/11.x/helpers#method-class-uses-recursive
     *
     * @param object|string $class
     * @return array
     */
    public static function classUses(object|string $class): array
    {

        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class) ?: []) + [$class => $class] as $class) {
            $results += self::traitUses($class);
        }

        return array_unique($results);

    }

    /**
     * Recursively return all traits used by a trait and its traits.
     *
     * See: https://laravel.com/docs/11.x/helpers#method-trait-uses-recursive
     *
     * @param object|string $trait
     * @return array
     */
    public static function traitUses(object|string $trait): array
    {

        $traits = class_uses($trait) ?: [];

        foreach ($traits as $trait) {
            $traits += self::traitUses($trait);
        }

        return $traits;

    }

}