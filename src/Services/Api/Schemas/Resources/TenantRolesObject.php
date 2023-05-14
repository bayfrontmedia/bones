<?php

namespace Bayfront\Bones\Services\Api\Schemas\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\ArraySchema\SchemaInterface;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\HttpRequest\Request;

class TenantRolesObject implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        if (Arr::isMissing($array, [
            'id',
        ]) || Arr::isMissing($config, [
            'tenant_id'
            ])) {
            throw new InvalidSchemaException('Unable to create TenantRolesObject schema: missing required keys');
        }

        $return = [
            'type' => 'tenantRoles',
            'id' => $array['id']
        ];

        $order = [
            'name',
            'description',
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

        $return['links']['self'] = '/tenants/' . $config['tenant_id'] . '/roles/' . $array['id'] . $query;

        if (App::getConfig('api.response.absolute_uri')) {
            $return['links']['self'] = App::getConfig('api.response.base_url') . $return['links']['self'];
        }

        return $return;

    }

}