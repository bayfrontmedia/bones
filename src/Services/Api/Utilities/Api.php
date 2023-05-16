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

    // Validation methods

    public const AUTH_TOKEN = 'token';
    public const AUTH_KEY = 'key';

    // ------------------------- Functions -------------------------

    /**
     * Delete all expired refresh tokens.
     *
     * @return int (Number of deleted tokens)
     */
    public function deleteExpiredRefreshTokens(): int
    {

        $this->db->query("DELETE FROM api_user_meta WHERE id = '00-refresh-token' AND updatedAt <= DATE_SUB(NOW(), INTERVAL :max_mins MINUTE)", [
            'max_mins' => (int)max(array_values(App::getConfig('api.token_duration.refresh')))
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