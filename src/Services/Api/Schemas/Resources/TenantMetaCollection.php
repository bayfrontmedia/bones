<?php

namespace Bayfront\Bones\Services\Api\Schemas\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\ArraySchema\SchemaInterface;
use Bayfront\Bones\Services\Api\Schemas\ResourceCollectionMetaResults;
use Bayfront\Bones\Services\Api\Schemas\ResourceCollectionPagination;

class TenantMetaCollection implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        if (Arr::isMissing($array, [
            'data',
            'meta'
        ])) {
            throw new InvalidSchemaException('Unable to create TenantMetaCollection schema: missing required keys');
        }

        $data = [];

        foreach ($array['data'] as $v) {
            $data[] = TenantMetaObject::create($v, $config);
        }

        return [
            'data' => $data,
            'meta' => [
                'results' => ResourceCollectionMetaResults::create($array['meta'])
            ],
            'links' => ResourceCollectionPagination::create($array['meta'], $config)
        ];

    }

}