<?php

namespace Bayfront\Bones\Services\Api\Schemas;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\ArraySchema\SchemaInterface;
use Bayfront\Bones\Application\Utilities\App;

class ResourceCollectionPagination implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        if (Arr::isMissing($array, [
                'count',
                'total',
                'pages',
                'pageSize',
                'pageNumber'
            ]) || Arr::isMissing($config, [
                'query_string'
            ])) {
            throw new InvalidSchemaException('Unable to create ResourceCollectionPagination schema: missing required keys');
        }

        /*
         * All $array keys should already be integers
         *
         * By defining $query_string, existing parameters are retained in the links
         */

        $query_string = $config['query_string'];

        // Self

        $links['self'] = Arr::get($config, 'collection_prefix', '') . '?' . Arr::query(array_replace($query_string, [
                'page' => [
                    'size' => Arr::get($query_string, 'page.size', $array['pageSize']),
                    'number' => $array['pageNumber']
                ]
            ]));

        // First

        $links['first'] = Arr::get($config, 'collection_prefix', '') . '?' . Arr::query(array_replace($query_string, [
                'page' => [
                    'size' => Arr::get($query_string, 'page.size', $array['pageSize']),
                    'number' => 1
                ]
            ]));

        // Prev

        if ($array['pageNumber'] > 1) {

            $links['prev'] = Arr::get($config, 'collection_prefix', '') . '?' . Arr::query(array_replace($query_string, [
                    'page' => [
                        'size' => Arr::get($query_string, 'page.size', $array['pageSize']),
                        'number' => $array['pageNumber'] - 1
                    ]
                ]));

        } else {

            $links['prev'] = NULL;

        }

        // Next

        if ($array['pages'] > 1 && $array['pages'] > $array['pageNumber']) {

            $links['next'] = Arr::get($config, 'collection_prefix', '') . '?' . Arr::query(array_replace($query_string, [
                    'page' => [
                        'size' => Arr::get($query_string, 'page.size', $array['pageSize']),
                        'number' => $array['pageNumber'] + 1
                    ]
                ]));

        } else {

            $links['next'] = NULL;

        }

        // Last

        $links['last'] = Arr::get($config, 'collection_prefix', '') . '?' . Arr::query(array_replace($query_string, [
                'page' => [
                    'size' => Arr::get($query_string, 'page.size', $array['pageSize']),
                    'number' => $array['pages']
                ]
            ]));

        if (App::getConfig('api.response.absolute_uri')) {

            foreach ($links as $k => $v) {
                if ($v !== null) {
                    $links[$k] = App::getConfig('api.response.base_url') . $v;
                }
            }

        }

        return $links;

    }

}