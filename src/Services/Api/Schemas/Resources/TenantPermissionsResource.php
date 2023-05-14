<?php

namespace Bayfront\Bones\Services\Api\Schemas\Resources;

use Bayfront\ArraySchema\SchemaInterface;

class TenantPermissionsResource implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        return [
            'data' => TenantPermissionsObject::create($array, $config)
        ];

    }

}