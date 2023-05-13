<?php

namespace Bayfront\Bones\Services\Api\Schemas\Resources;

use Bayfront\ArraySchema\SchemaInterface;

class UserKeysResource implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        return [
            'data' => UserKeysObject::create($array, $config)
        ];

    }

}