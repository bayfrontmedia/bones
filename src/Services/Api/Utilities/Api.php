<?php

namespace Bayfront\Bones\Services\Api\Utilities;

use Bayfront\Bones\Application\Utilities\App;
use Bayfront\PDO\Db;

class Api
{

    protected Db $db;

    /**
     * @param Db $db
     */
    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    // ------------------------- Constants -------------------------

    // CRUD actions

    public const ACTION_CREATE = 'create';
    public const ACTION_READ = 'read';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    // Authentication methods

    public const AUTH_PASSWORD = 'password';
    public const AUTH_REFRESH_TOKEN = 'refresh-token';
    public const AUTH_ACCESS_TOKEN = 'token';
    public const AUTH_KEY = 'key';

    // Permissions

    public const PROTECTED_PERMISSIONS = [ // TODO
        'tenant.groups.create',
        'tenant.groups.read',
        'tenant.groups.update',
        'tenant.groups.delete',
        'tenant.invitations.create',
        'tenant.invitations.read',
        'tenant.invitations.update',
        'tenant.invitations.delete',
        'tenant.meta.create',
        'tenant.meta.read',
        'tenant.meta.update',
        'tenant.meta.delete',
        'tenant.permissions.create',
        'tenant.permissions.read',
        'tenant.permissions.update',
        'tenant.permissions.delete',
        'tenant.roles.create',
        'tenant.roles.read',
        'tenant.roles.update',
        'tenant.roles.delete',
        'tenant.read',
        'tenant.update',
        'tenant.user.meta.create',
        'tenant.user.meta.read',
        'tenant.user.meta.update',
        'tenant.user.meta.delete',
        'tenant.group.users.add',
        'tenant.group.users.read',
        'tenant.group.users.remove',
        'tenant.role.permissions.add',
        'tenant.role.permissions.read',
        'tenant.role.permissions.remove',
        'tenant.user.roles.add',
        'tenant.user.roles.read',
        'tenant.user.roles.remove',
        'tenant.users.add',
        'tenant.users.read',
        'tenant.users.remove',
        'tenant.user.permissions.read'
    ];

    // ------------------------- Functions -------------------------

    /**
     * Delete all expired refresh tokens.
     *
     * @return int (Number of deleted tokens)
     */
    public function deleteExpiredRefreshTokens(): int
    {

        $this->db->query("DELETE FROM api_user_meta WHERE id = '00-refresh-token' AND updatedAt <= DATE_SUB(NOW(), INTERVAL :max_mins MINUTE)", [
            'max_mins' => (int)max(array_values(App::getConfig('api.duration.refresh_token')))
        ]);

        return $this->db->rowCount();

    }

    /**
     * Delete all expired buckets.
     *
     * @return int (Number of deleted buckets)
     */
    public function deleteExpiredBuckets(): int
    {

        $this->db->query("DELETE FROM api_buckets WHERE updatedAt <= DATE_SUB(NOW(), INTERVAL :max_mins MINUTE)", [
            'max_mins' => (int)max(array_values(App::getConfig('api.rate_limit')))
        ]);

        return $this->db->rowCount();

    }

    /**
     * Delete all expired user keys.
     *
     * @return int (Number of deleted keys)
     */
    public function deleteExpiredUserKeys(): int
    {
        $this->db->query("DELETE FROM api_user_keys WHERE expiresAt <= NOW()");
        return $this->db->rowCount();
    }

    /**
     * Delete all expired tenant invitations.
     *
     * @return int (Number of deleted invitations)
     */
    public function deleteExpiredTenantInvitations(): int
    {
        $this->db->query("DELETE FROM api_tenant_invitations WHERE expiresAt <= NOW()");
        return $this->db->rowCount();
    }

}