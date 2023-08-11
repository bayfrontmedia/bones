<?php

namespace Bayfront\Bones\Services\Api\Models\Relationships;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Interfaces\ScopedRelationshipInterface;
use Bayfront\Bones\Services\Api\Models\Resources\TenantPermissionsModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantRolesModel;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\InvalidDatabaseException;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\Validator\Validate;
use Monolog\Logger;
use PDOException;

class TenantRolePermissionsModel extends ApiModel implements ScopedRelationshipInterface
{

    protected TenantPermissionsModel $tenantPermissionsModel;
    protected TenantRolesModel $tenantRolesModel;

    public function __construct(EventService $events, Db $db, Logger $log, TenantPermissionsModel $tenantPermissionsModel, TenantRolesModel $tenantRolesModel)
    {
        $this->tenantPermissionsModel = $tenantPermissionsModel;
        $this->tenantRolesModel = $tenantRolesModel;

        parent::__construct($events, $db, $log);
    }

    /**
     * @inheritDoc
     */
    public function getSelectableCols(): array
    {
        return $this->tenantPermissionsModel->getSelectableCols();
    }

    /**
     * @inheritDoc
     */
    public function getJsonCols(): array
    {
        return $this->tenantPermissionsModel->getJsonCols();
    }

    /**
     * Get count of role permissions.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @return int
     */
    public function getCount(string $scoped_id, string $resource_id): int
    {

        if (!Validate::uuid($scoped_id) || !Validate::uuid($resource_id)) {
            return 0;
        }

        return $this->db->single("SELECT COUNT(*) FROM api_tenant_role_permissions WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND roleId = UUID_TO_BIN(:role_id, 1)", [
            'tenant_id' => $scoped_id,
            'role_id' => $resource_id
        ]);

    }

    /**
     * Get all permission ID's for tenant role.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @return array
     */
    public function getAllIds(string $scoped_id, string $resource_id): array
    {

        if (!Validate::uuid($scoped_id) || !Validate::uuid($resource_id)) {
            return [];
        }

        return Arr::pluck($this->db->select("SELECT BIN_TO_UUID(id, 1) as id FROM api_tenant_permissions WHERE tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'tenant_id' => $scoped_id
        ]), 'name');

    }

    /**
     * Get all permission names for tenant role.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @return array
     */
    public function getAllNames(string $scoped_id, string $resource_id): array
    {

        if (!Validate::uuid($scoped_id) || !Validate::uuid($resource_id)) {
            return [];
        }

        return Arr::pluck($this->db->select("SELECT name FROM api_tenant_permissions WHERE tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'tenant_id' => $scoped_id
        ]), 'name');

    }

    /**
     * Does role have permission?
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @param string $relationship_id
     * @return bool
     */
    public function has(string $scoped_id, string $resource_id, string $relationship_id): bool
    {

        if (!Validate::uuid($scoped_id) || !Validate::uuid($resource_id) || !Validate::uuid($relationship_id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_tenant_role_permissions WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND roleId = UUID_TO_BIN(:role_id, 1) AND permissionId = UUID_TO_BIN(:permission_id, 1)", [
            'tenant_id' => $scoped_id,
            'role_id' => $resource_id,
            'permission_id' => $relationship_id
        ]);

    }

    /**
     * Add permissions to tenant role.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @param array $relationship_ids
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function add(string $scoped_id, string $resource_id, array $relationship_ids): void
    {

        // Tenant role exists

        if (!$this->tenantRolesModel->idExists($scoped_id, $resource_id)) {

            $msg = 'Unable to add permissions to tenant role';
            $reason = 'Role ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Add

        $permission_ids = $this->tenantPermissionsModel->getAllIds($scoped_id);

        try {
            $pdo = $this->db->get();
        } catch (InvalidDatabaseException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO api_tenant_role_permissions SET tenantId = UUID_TO_BIN(:tenant_id, 1), roleId = UUID_TO_BIN(:role_id, 1), permissionId = UUID_TO_BIN(:permission_id, 1) 
                                  ON DUPLICATE KEY UPDATE tenantId = VALUES(tenantId), roleId = VALUES(roleId), permissionId = VALUES(permissionId)");

            foreach ($relationship_ids as $permission) {

                if (!in_array($permission, $permission_ids)) {

                    $pdo->rollBack();

                    $msg = 'Unable to add permissions to tenant role';
                    $reason = 'Permission ID (' . $permission . ') is invalid or does not exist';

                    $this->log->notice($msg, [
                        'reason' => $reason,
                        'tenant_id' => $scoped_id,
                        'role_id' => $resource_id,
                        'permission_id' => $permission
                    ]);

                    throw new BadRequestException($msg . ': ' . $reason);

                }

                $stmt->execute([
                    'tenant_id' => $scoped_id,
                    'role_id' => $resource_id,
                    'permission_id' => $permission
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->log->info('Permissions added to tenant role', [
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id,
                'permission_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.role.permissions.add', $scoped_id, $resource_id, $relationship_ids);

    }

    /**
     * Get tenant role permissions collection.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @param array $args
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function getCollection(string $scoped_id, string $resource_id, array $args = []): array
    {

        if (empty($args['select'])) {
            $args['select'][] = '*';
        } else {
            $args['select'] = array_merge($args['select'], ['id']); // Force return ID
        }

        // Tenant role exists

        if (!$this->tenantRolesModel->idExists($scoped_id, $resource_id)) {

            $msg = 'Unable to get tenant role permissions collection';
            $reason = 'Role ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery();

        try {

            $query->table('api_tenant_permissions')
                ->leftJoin('api_tenant_role_permissions', 'api_tenant_permissions.id', 'api_tenant_role_permissions.permissionId')
                ->where('api_tenant_role_permissions.tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)")
                ->where('api_tenant_role_permissions.roleId', 'eq', "UUID_TO_BIN('" . $resource_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'name', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant role permissions collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

            $this->log->info('Tenant role permissions read', [
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id,
                'permission_ids' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.role.permissions.read', $scoped_id, $resource_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Remove permissions from tenant role.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @param array $relationship_ids
     * @return void
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function remove(string $scoped_id, string $resource_id, array $relationship_ids): void
    {

        // Tenant role exists

        if (!$this->tenantRolesModel->idExists($scoped_id, $resource_id)) {

            $msg = 'Unable to remove permissions from tenant role';
            $reason = 'Role ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Remove

        try {
            $pdo = $this->db->get();
        } catch (InvalidDatabaseException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM api_tenant_role_permissions WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND roleId = UUID_TO_BIN(:role_id, 1) AND permissionId = UUID_TO_BIN(:permission_id, 1)");

            foreach ($relationship_ids as $permission) {

                if (!Validate::uuid($permission)) {
                    continue;
                }

                $stmt->execute([
                    'tenant_id' => $scoped_id,
                    'role_id' => $resource_id,
                    'permission_id' => $permission
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->log->info('Permissions removed from tenant role', [
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id,
                'permission_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.role.permissions.remove', $scoped_id, $resource_id, $relationship_ids);

    }

    /**
     * Remove all permissions from tenant role.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @return void
     * @throws NotFoundException
     */
    public function removeAll(string $scoped_id, string $resource_id): void
    {

        // Tenant role exists

        if (!$this->tenantRolesModel->idExists($scoped_id, $resource_id)) {

            $msg = 'Unable to remove permissions from tenant role';
            $reason = 'Role ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Get all ID's for log and event

        $ids = $this->getAllIds($scoped_id, $resource_id);

        // Delete

        $this->db->query("DELETE FROM api_tenant_role_permissions WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND roleId = UUID_TO_BIN(:role_id, 1)", [
           'tenant_id' => $scoped_id,
           'role_id' => $resource_id
        ]);

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->log->info('Permissions removed from tenant role', [
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id,
                'permission_ids' => $ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.role.permissions.remove', $scoped_id, $resource_id, $ids);

    }

}