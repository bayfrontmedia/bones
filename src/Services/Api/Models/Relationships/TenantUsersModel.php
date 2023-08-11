<?php

namespace Bayfront\Bones\Services\Api\Models\Relationships;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Interfaces\RelationshipInterface;
use Bayfront\Bones\Services\Api\Models\Resources\TenantMetaModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantPermissionsModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantsModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\MultiLogger\MultiLogger;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\InvalidDatabaseException;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\Validator\Validate;
use PDOException;

class TenantUsersModel extends ApiModel implements RelationshipInterface
{

    protected TenantsModel $tenantsModel;
    protected UsersModel $usersModel;
    protected TenantMetaModel $tenantMetaModel;
    protected TenantPermissionsModel $tenantPermissionsModel;

    public function __construct(EventService $events, Db $db, MultiLogger $multiLogger, TenantsModel $tenantsModel, UsersModel $usersModel, TenantMetaModel $tenantMetaModel, TenantPermissionsModel $tenantPermissionsModel)
    {
        $this->tenantsModel = $tenantsModel;
        $this->usersModel = $usersModel;
        $this->tenantMetaModel = $tenantMetaModel;
        $this->tenantPermissionsModel = $tenantPermissionsModel;

        parent::__construct($events, $db, $multiLogger);
    }

    /**
     * @inheritDoc
     */
    public function getSelectableCols(): array
    {
        return $this->usersModel->getSelectableCols();
    }

    /**
     * @inheritDoc
     */
    public function getJsonCols(): array
    {
        return $this->usersModel->getJsonCols();
    }

    /**
     * Does user own tenant?
     *
     * @param string $tenant_id
     * @param string $user_id
     * @return bool
     */
    public function isOwner(string $tenant_id, string $user_id): bool
    {
        return $this->getOwnerId($tenant_id) == $user_id;
    }

    /**
     * Get owner ID.
     *
     * @param string $tenant_id
     * @return string
     */
    public function getOwnerId(string $tenant_id): string
    {

        if (!Validate::uuid($tenant_id)) {
            return '';
        }

        return $this->db->single("SELECT BIN_TO_UUID(owner, 1) FROM api_tenants WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $tenant_id
        ]);

    }

    /**
     * Get all ID's of users who own or belong to tenant.
     *
     * @param string $tenant_id
     * @return array
     */
    public function getAllIds(string $tenant_id): array
    {

        if (!Validate::uuid($tenant_id)) {
            return [];
        }

        return Arr::pluck($this->db->select("SELECT BIN_TO_UUID(userId, 1) as userId FROM api_tenant_users WHERE tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'tenant_id' => $tenant_id
        ]), 'userId');

    }

    /**
     * Get maximum number of users allowed in tenant.
     *
     * @param string $tenant_id
     * @return int
     */
    public function getTotalAllowed(string $tenant_id): int
    {

        $plan = $this->tenantMetaModel->getValue($tenant_id, '00-plan', true);

        if ($plan) {
            return Arr::get(json_decode($plan, true), 'max_users', App::getConfig('api.tenants.max_users'));
        }

        return App::getConfig('api.tenants.max_users');

    }

    /**
     * Get tenant user permission collection.
     *
     * If either the tenant or user is disabled, no permissions are returned.
     *
     * @param string $tenant_id
     * @param string $user_id
     * @param array $args
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function getPermissionCollection(string $tenant_id, string $user_id, array $args = []): array
    {

        if (empty($args['select'])) {
            $args['select'][] = '*';
        } else {
            $args['select'] = array_merge($args['select'], ['id']); // Force return ID
        }

        // Check tenant has user

        if (!$this->has($tenant_id, $user_id)) {

            $msg = 'Unable to get tenant user permission collection';
            $reason = 'Tenant and / or user does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $tenant_id,
                'user_id' => $user_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Check tenant and user are enabled

        if (!$this->tenantsModel->isEnabled($tenant_id)
            || !$this->usersModel->isEnabled($user_id)) {

            return $this->returnEmptyCollection($args, $args['limit']);

        }

        if ($this->isOwner($tenant_id, $user_id)) { // Owner inherits all tenant permissions

            return $this->tenantPermissionsModel->getCollection($tenant_id, $args);

        }

        /*
         * Get all user roles
         *
         * Mimic tenantUserRolesModel->getAllIds()
         */

        $roles = Arr::pluck($this->db->select("SELECT BIN_TO_UUID(roleId, 1) as roleId FROM api_tenant_user_roles WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)", [
            'tenant_id' => $tenant_id,
            'user_id' => $user_id
        ]), 'roleId');

        if (empty($roles)) { // No roles
            return $this->returnEmptyCollection($args, $args['limit']);
        }

        // Query

        $query = $this->startNewQuery();

        try {

            $query->table('api_tenant_permissions')
                ->leftJoin('api_tenant_role_permissions', 'api_tenant_permissions.id', 'api_tenant_role_permissions.permissionId')
                ->where('api_tenant_role_permissions.tenantId', 'eq', "UUID_TO_BIN('" . $tenant_id . "', 1)")
                ->where('BIN_TO_UUID(api_tenant_role_permissions.roleId, 1)', 'in', implode(',', $roles));

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->tenantPermissionsModel->getSelectableCols(), 'name', $args['limit'], $this->tenantPermissionsModel->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant user permission collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $tenant_id,
                'user_id' => $user_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

            $this->log->info('Tenant user permissions read', [
                'tenant_id' => $tenant_id,
                'user_id' => $user_id,
                'permission_ids' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.permissions.read', $tenant_id, $user_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Return array of all tenant user permission names.
     *
     * @param string $tenant_id
     * @param string $user_id
     * @return array
     */
    public function getPermissionNames(string $tenant_id, string $user_id): array
    {

        if (!$this->has($tenant_id, $user_id)
            || !$this->tenantsModel->isEnabled($tenant_id)
            || !$this->usersModel->isEnabled($user_id)) {
            return [];
        }

        if ($this->isOwner($tenant_id, $user_id)) { // Owner inherits all tenant permissions

            return $this->tenantPermissionsModel->getAllNames($tenant_id);

        }

        $roles = Arr::pluck($this->db->select("SELECT BIN_TO_UUID(roleId, 1) as roleId FROM api_tenant_user_roles WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)", [
            'tenant_id' => $tenant_id,
            'user_id' => $user_id
        ]), 'roleId');

        if (empty($roles)) { // No roles
            return [];
        }

        return Arr::pluck($this->db->select("SELECT atp.name FROM api_tenant_permissions AS atp LEFT JOIN api_tenant_role_permissions AS atrp ON atp.id = atrp.permissionId 
                WHERE atrp.tenantId = UUID_TO_BIN(:tenant_id, 1) AND BIN_TO_UUID(atrp.roleId, 1) IN (:role_ids) ORDER BY atp.name", [
            'tenant_id' => $tenant_id,
            'role_ids' => implode(',', $roles)
        ]), 'name');

    }

    /**
     * Get tenant users count.
     *
     * @param string $resource_id
     * @return int
     */
    public function getCount(string $resource_id): int
    {

        if (!Validate::uuid($resource_id)) {
            return 0;
        }

        return $this->db->single("SELECT COUNT(*) FROM api_tenant_users WHERE tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'tenant_id' => $resource_id
        ]);
    }

    /**
     * Does tenant have user?
     *
     * @param string $resource_id
     * @param string $relationship_id
     * @return bool
     */
    public function has(string $resource_id, string $relationship_id): bool
    {

        if (!Validate::uuid($resource_id) || !Validate::uuid($relationship_id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_tenant_users WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)", [
            'tenant_id' => $resource_id,
            'user_id' => $relationship_id
        ]);

    }

    /**
     * Does tenant have user with email?
     *
     * @param string $resource_id
     * @param string $relationship_id (Email)
     * @return bool
     */
    public function hasEmail(string $resource_id, string $relationship_id): bool
    {

        if (!Validate::uuid($resource_id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_users AS au LEFT JOIN api_tenant_users as atu ON au.id = atu.userId
         WHERE atu.tenantId = UUID_TO_BIN(:tenant_id, 1) AND au.email = :email", [
            'tenant_id' => $resource_id,
            'email' => $relationship_id
        ]);

    }

    /**
     * Add users to tenant.
     *
     * @param string $resource_id
     * @param array $relationship_ids
     * @return void
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function add(string $resource_id, array $relationship_ids): void
    {

        // Exists

        if (!$this->tenantsModel->idExists($resource_id)) {

            $msg = 'Unable to add users to tenant';
            $reason = 'Tenant ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Check max allowed values

        $total_allowed = $this->getTotalAllowed($resource_id);

        if ($this->getCount($resource_id) + count($relationship_ids) > $total_allowed) {

            $msg = 'Unable to add users to tenant';
            $reason = 'Maximum number of allowed users (' . $total_allowed . ') exceeded';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $resource_id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Add

        try {
            $pdo = $this->db->get();
        } catch (InvalidDatabaseException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO api_tenant_users SET tenantId = UUID_TO_BIN(:tenant_id, 1), userId = UUID_TO_BIN(:user_id, 1) ON DUPLICATE KEY UPDATE tenantId = VALUES(tenantId), userId = VALUES(userId)");

            foreach ($relationship_ids as $user) {

                if (!$this->usersModel->idExists($user)) {

                    $pdo->rollBack();

                    $msg = 'Unable to add users to tenant';
                    $reason = 'User ID (' . $user . ') is invalid or does not exist';

                    $this->log->notice($msg, [
                        'reason' => $reason,
                        'tenant_id' => $resource_id,
                        'user_id' => $user
                    ]);

                    throw new BadRequestException($msg . ': ' . $reason);

                }

                $stmt->execute([
                    'tenant_id' => $resource_id,
                    'user_id' => $user
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->log->info('Users added to tenant', [
                'tenant_id' => $resource_id,
                'user_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.users.add', $resource_id, $relationship_ids);

    }

    /**
     * Get tenant users collection.
     * Users who own or belong to tenant.
     *
     * @param string $resource_id
     * @param array $args
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function getCollection(string $resource_id, array $args = []): array
    {

        if (empty($args['select'])) {
            $args['select'][] = '*';
        } else {
            $args['select'] = array_merge($args['select'], ['id']); // Force return ID
        }

        // Exists

        if (!$this->tenantsModel->idExists($resource_id)) {

            $msg = 'Unable to get tenant users';
            $reason = 'Tenant ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery();

        try {

            $query->table('api_users')
                ->leftJoin('api_tenant_users', 'api_users.id', 'api_tenant_users.userId')
                ->where('api_tenant_users.tenantId', 'eq', "UUID_TO_BIN('" . $resource_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'id', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant users collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $resource_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

            $this->log->info('Tenant users read', [
                'tenant_id' => $resource_id,
                'user_ids' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.users.read', $resource_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Remove users from tenant.
     * Owner cannot be removed.
     *
     * @param string $resource_id
     * @param array $relationship_ids
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function remove(string $resource_id, array $relationship_ids): void
    {

        // Exists

        if (!$this->tenantsModel->idExists($resource_id)) {

            $msg = 'Unable to remove users from tenant';
            $reason = 'Tenant ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        $owner = $this->getOwnerId($resource_id);

        // Remove

        try {
            $pdo = $this->db->get();
        } catch (InvalidDatabaseException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM api_tenant_users WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)");

            foreach ($relationship_ids as $user) {

                if (!Validate::uuid($user)) {
                    continue;
                }

                if ($owner == $user) {

                    $pdo->rollBack();

                    $msg = 'Unable to remove users from tenant';
                    $reason = 'User ID (' . $user . ') is owner of tenant';

                    $this->log->notice($msg, [
                        'reason' => $reason,
                        'tenant_id' => $resource_id,
                        'user_id' => $user
                    ]);

                    throw new ForbiddenException($msg . ': ' . $reason);

                }

                $stmt->execute([
                    'tenant_id' => $resource_id,
                    'user_id' => $user
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->log->info('Users removed from tenant', [
                'tenant_id' => $resource_id,
                'user_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.users.remove', $resource_id, $relationship_ids);

    }

}