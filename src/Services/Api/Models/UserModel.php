<?php

namespace Bayfront\Bones\Services\Api\Models;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Relationships\TenantUsersModel;
use Bayfront\Bones\Services\Api\Models\Resources\UserMetaModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\MultiLogger\Log;
use Bayfront\PDO\Db;

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
     * @param Log $log
     * @param UsersModel $usersModel
     * @param UserMetaModel $userMetaModel
     * @param TenantUsersModel $tenantUsersModel
     * @param string $user_id
     * @param bool $skip_log (Skip api.user.read logs and events - used when a user authenticates)
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function __construct(EventService $events, Db $db, Log $log, UsersModel $usersModel, UserMetaModel $userMetaModel, TenantUsersModel $tenantUsersModel, string $user_id, bool $skip_log = false)
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

    // ------------------------- Tenant -------------------------

    /**
     * Is user in tenant?
     *
     * @param string $tenant_id
     * @return bool
     */
    public function inTenant(string $tenant_id): bool
    {
        return $this->tenantUsersModel->has($tenant_id, $this->getId());
    }

    /**
     * Does user own tenant?
     *
     * @param string $tenant_id
     * @return bool
     */
    public function ownsTenant(string $tenant_id): bool
    {
        return $this->tenantUsersModel->isOwner($tenant_id, $this->getId());
    }

    // ------------------------- Tenant permissions -------------------------

    private static array $global_permissions = [];
    private static array $permissions = [];

    /**
     * Return array of all global and tenant user permission names.
     *
     * @param string $tenant_id (Leaving blank will return only global permissions)
     * @return array
     */
    public function getPermissions(string $tenant_id = ''): array
    {

        // Global permissions

        if (empty(self::$global_permissions)) {

            $gp = $this->getMetaValue('00-global-permissions');

            if (!$gp) {
                self::$global_permissions = [];
            } else {
                self::$global_permissions = json_decode($gp, true);
            }

        }

        if ($tenant_id == '') {
            return self::$global_permissions;
        }

        // Tenant permissions

        if (!isset(self::$permissions[$tenant_id])) {
            self::$permissions[$tenant_id] = $this->tenantUsersModel->getPermissionNames($tenant_id, $this->getId());
        }

        return array_merge(self::$permissions[$tenant_id], self::$global_permissions);

    }

    /**
     * Does user have all global and tenant permissions?
     *
     * @param array $permissions
     * @param string $tenant_id
     * @return bool
     */
    public function hasAllPermissions(array $permissions, string $tenant_id = ''): bool
    {
        return Arr::hasAllValues($this->getPermissions($tenant_id), $permissions);
    }

    /**
     * Does user have any global and tenant permissions?
     *
     * @param array $permissions
     * @param string $tenant_id
     * @return bool
     */
    public function hasAnyPermissions(array $permissions, string $tenant_id = ''): bool
    {
        return Arr::hasAnyValues($this->getPermissions($tenant_id), $permissions);
    }

}