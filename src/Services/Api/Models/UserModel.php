<?php

namespace Bayfront\Bones\Services\Api\Models;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Relationships\TenantUsersModel;
use Bayfront\Bones\Services\Api\Models\Resources\UserMetaModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\PDO\Db;
use Monolog\Logger;

/**
 * User is verified to exist in constructor.
 */
class UserModel extends ApiModel
{

    protected UsersModel $usersModel;
    protected UserMetaModel $userMetaModel;
    protected TenantUsersModel $tenantUsersModel;

    private string $user_id;
    private array $user;

    /**
     * @param EventService $events
     * @param Db $db
     * @param Logger $log
     * @param UsersModel $usersModel
     * @param UserMetaModel $userMetaModel
     * @param TenantUsersModel $tenantUsersModel
     * @param string $user_id
     * @param bool $skip_log (Skip api.user.read logs and events - used when a user authenticates)
     * @throws NotFoundException
     */
    public function __construct(EventService $events, Db $db, Logger $log, UsersModel $usersModel, UserMetaModel $userMetaModel, TenantUsersModel $tenantUsersModel, string $user_id, bool $skip_log = false)
    {
        $this->usersModel = $usersModel;
        $this->userMetaModel = $userMetaModel;
        $this->tenantUsersModel = $tenantUsersModel;

        $this->user_id = $user_id;

        $this->user = Arr::except($this->usersModel->getEntire($this->getId(), $skip_log), [
            'password'
        ]);

        parent::__construct($events, $db, $log);
    }

    // ------------------------- User -------------------------

    public function getId(): string
    {
        return $this->user_id;
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function get(string $key, $default = null): mixed
    {
        return Arr::get($this->getUser(), $key, $default);
    }

    public function isEnabled(): bool
    {
        return $this->get('enabled', false);
    }
    
    // -------------------------User meta -------------------------

    private static array $meta = [];

    /**
     * Get value of single user meta or false if not existing.
     * This method includes protected meta.
     *
     * @param string $id
     * @return mixed
     */
    public function getMetaValue(string $id): mixed
    {

        if (isset(self::$meta[$id])) {
            return self::$meta[$id];
        }

        self::$meta[$id] = $this->userMetaModel->getValue($this->getId(), $id, true);

        return self::$meta[$id];

    }

    // ------------------------- Tenant permissions -------------------------

    private static array $permissions = [];

    /**
     * Return array of all global and tenant user permission names.
     *
     * @param string $tenant_id
     * @return array
     */
    public function getPermissions(string $tenant_id): array
    {

        if (!isset(self::$permissions[$tenant_id])) {

            $global = $this->getMetaValue('00-global-permissions');

            if (!$global) {
                $global = [];
            } else {
                $global = json_decode($global, true);
            }

            self::$permissions[$tenant_id] = array_merge($this->tenantUsersModel->getPermissionNames($tenant_id, $this->getId()), $global);

        }

        return self::$permissions[$tenant_id];

    }

    /**
     * Does user have all global and tenant permissions?
     *
     * @param string $tenant_id
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(string $tenant_id, array $permissions): bool
    {
        return Arr::hasAllValues($this->getPermissions($tenant_id), $permissions);
    }

    /**
     * Does user have any global and tenant permissions?
     *
     * @param string $tenant_id
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermissions(string $tenant_id, array $permissions): bool
    {
        return Arr::hasAnyValues($this->getPermissions($tenant_id), $permissions);
    }

}