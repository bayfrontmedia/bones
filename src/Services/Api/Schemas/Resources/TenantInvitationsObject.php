<?php

namespace Bayfront\Bones\Services\Api\Schemas\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\ArraySchema\SchemaInterface;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\HttpRequest\Request;

class TenantInvitationsObject implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        if (Arr::isMissing($array, [
            'email',
        ]) || Arr::isMissing($config, [
            'tenant_id'
            ])) {
            throw new InvalidSchemaException('Unable to create TenantInvitationsObject schema: missing required keys');
        }

        $return = [
            'type' => 'tenantInvitations',
            'id' => $array['email']
        ];

        $order = [
            'roleId',
            'expiresAt',
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

        $return['links']['self'] = '/tenants/' . $config['tenant_id'] . '/invitations/' . $array['email'] . $query;

        $return['links']['related'] = '/tenants/' . $config['tenant_id'] . '/verify/' . $array['email'];

        if (App::getConfig('api.response.absolute_uri')) {
            $return['links']['self'] = App::getConfig('api.response.base_url') . $return['links']['self'];
            $return['links']['related'] = App::getConfig('api.response.base_url') . $return['links']['related'];
        }

        return $return;

    }

}