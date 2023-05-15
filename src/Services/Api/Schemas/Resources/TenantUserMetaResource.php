<?php

namespace Bayfront\Bones\Services\Api\Schemas\Resources;

use Bayfront\ArraySchema\SchemaInterface;

class TenantUserMetaResource implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        return [
            'data' => TenantUserMetaObject::create($array, $config)
        ];

    }

}