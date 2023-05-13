<?php

namespace Bayfront\Bones\Services\Api\Schemas\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\ArraySchema\SchemaInterface;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\HttpRequest\Request;

class UserKeysObject implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        if (Arr::isMissing($array, [
                'id',
            ]) || Arr::isMissing($config, [
                'user_id'
            ])) {
            throw new InvalidSchemaException('Unable to create UserKeysObject schema: missing required keys');
        }

        $return = [
            'type' => 'userKeys',
            'id' => $array['id']
        ];

        $order = [
            'keyValue', // Returned only when created
            'description',
            'allowedDomains',
            'allowedIps',
            'expiresAt',
            'lastUsed',
            'createdAt',
            'updatedAt'
        ];

        $attributes = Arr::only(Arr::order($array, $order), $order);

        if (!empty($attributes)) {
            $return['attributes'] = $attributes;
        }

        // Get query string

        $query = '';

        if (!empty(Request::getQuery())) {
            $query = '?' . Arr::query(Request::getQuery());
        }

        $return['links']['self'] = '/users/' . $config['user_id'] . '/keys/' . $array['id'] . $query;

        if (App::getConfig('api.response.absolute_uri')) {
            $return['links']['self'] = App::getConfig('api.response.base_url') . $return['links']['self'];
        }

        return $return;

    }

}