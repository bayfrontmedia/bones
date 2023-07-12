<?php

namespace Bayfront\Bones\Services\Api\Schemas\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\ArraySchema\SchemaInterface;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\HttpRequest\Request;

class UsersObject implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        if (Arr::isMissing($array, [
            'id',
        ])) {
            throw new InvalidSchemaException('Unable to create UsersObject schema: missing required keys');
        }

        $return = [
            'type' => 'users',
            'id' => $array['id']
        ];

        $order = [
            'email',
            'meta',
            'enabled',
            'createdAt',
            'updatedAt'
        ];

        $attributes = Arr::only(Arr::order($array, $order), $order);

        if (!empty($attributes)) {

            // Sanitize

            if (isset($attributes['enabled'])) {
                $attributes['enabled'] = (bool)$attributes['enabled'];
            }

            $return['attributes'] = $attributes;

        }

        // Get query string

        $query = '';

        if (!empty(Request::getQuery())) {
            $query = '?' . Arr::query(Request::getQuery());
        }

        $return['links']['self'] = '/users/' . $array['id'] . $query;

        if (App::getConfig('api.response.absolute_uri')) {

            $return['links']['self'] = App::getConfig('api.response.base_url') . $return['links']['self'];

        }

        return $return;

    }

}