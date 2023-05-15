<?php

namespace Bayfront\Bones\Services\Api\Schemas\Resources;

use Bayfront\ArraySchema\SchemaInterface;

class TenantInvitationsResource implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        return [
            'data' => TenantInvitationsObject::create($array, $config)
        ];

    }

}