<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces;

interface ApiSchemaInterface
{

    /**
     * Return an array of data conforming to the schema.
     *
     * @param array $data
     * @return array
     */
    public static function create(array $data): array;

}