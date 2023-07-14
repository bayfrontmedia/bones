<?php /** @noinspection PhpUnused */

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

    /*
     * Default permissions used by the Api service
     */
    public const DEFAULT_PERMISSIONS = [
        'tenant.groups.create' => 'Create groups',
        'tenant.groups.read' => 'Read groups',
        'tenant.groups.update' => 'Update groups',
        'tenant.groups.delete' => 'Delete groups',
        'tenant.invitations.create' => 'Create invitations',
        'tenant.invitations.read' => 'Read invitations',
        'tenant.invitations.update' => 'Update invitations',
        'tenant.invitations.delete' => 'Delete invitations',
        'tenant.meta.create' => 'Create tenant meta',
        'tenant.meta.read' => 'Read tenant meta',
        'tenant.meta.update' => 'Update tenant meta',
        'tenant.meta.delete' => 'Delete tenant meta',
        'tenant.permissions.create' => 'Create tenant permissions',
        'tenant.permissions.read' => 'Read tenant permissions',
        'tenant.permissions.update' => 'Update tenant permissions',
        'tenant.permissions.delete' => 'Delete tenant permissions',
        'tenant.roles.create' => 'Create roles',
        'tenant.roles.read' => 'Read roles',
        'tenant.roles.update' => 'Update roles',
        'tenant.roles.delete' => 'Delete roles',
        'tenant.update' => 'Update tenant',
        'tenant.delete' => 'Delete tenant',
        'tenant.user.meta.create' => 'Create tenant user meta',
        'tenant.user.meta.read' => 'Read tenant user meta',
        'tenant.user.meta.update' => 'Update tenant user meta',
        'tenant.user.meta.delete' => 'Delete tenant user meta',
        'tenant.group.users.add' => 'Add users to groups',
        'tenant.group.users.read' => 'Read users in groups',
        'tenant.group.users.remove' => 'Remove users from groups',
        'tenant.role.permissions.add' => 'Add permissions to roles',
        'tenant.role.permissions.read' => 'Read role permissions',
        'tenant.role.permissions.remove' => 'Remove permissions from roles',
        'tenant.user.roles.add' => 'Add roles to users',
        'tenant.user.roles.read' => 'Read user roles',
        'tenant.user.roles.remove' => 'Remove roles from users',
        'tenant.users.add' => 'Add users to tenant',
        'tenant.users.read' => 'Read tenant users',
        'tenant.users.remove' => 'Remove users from tenant',
        'tenant.user.permissions.read' => 'Read tenant user permissions'
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

    /**
     * Delete all expired password reset tokens.
     *
     * @return int (Number of deleted tokens)
     */
    public function deleteExpiredPasswordTokens(): int
    {
        $this->db->query("DELETE FROM api_user_meta WHERE id = :id AND updatedAt <= DATE_SUB(NOW(), INTERVAL :max_mins MINUTE)", [
            'id' => '00-password-token',
            'max_mins' => (int)max(array_values(App::getConfig('api.duration.password_token')))
        ]);
        return $this->db->rowCount();
    }

}