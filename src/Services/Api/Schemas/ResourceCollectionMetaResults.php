<?php

namespace Bayfront\Bones\Services\Api\Schemas;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\ArraySchema\SchemaInterface;

class ResourceCollectionMetaResults implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        $required_keys = [
            'count',
            'total',
            'pages',
            'pageSize',
            'pageNumber'
        ];

        if (Arr::isMissing($array, $required_keys)) {
            throw new InvalidSchemaException('Unable to create ResourceCollectionMetaResults schema: missing required keys');
        }

        /*
         * All $array keys should already be integers
         */

        return Arr::only($array, $required_keys);

    }

}