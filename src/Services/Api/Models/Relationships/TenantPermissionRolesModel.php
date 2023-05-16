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

class TenantPermissionRolesModel extends ApiModel implements ScopedRelationshipInterface
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
        return $this->tenantRolesModel->getSelectableCols();
    }

    /**
     * @inheritDoc
     */
    public function getJsonCols(): array
    {
        return $this->tenantRolesModel->getJsonCols();
    }

    /**
     * Get count of roles with permission.
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

        return $this->db->single("SELECT COUNT(*) FROM api_tenant_role_permissions WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND permissionId = UUID_TO_BIN(:permission_id, 1)", [
            'tenant_id' => $scoped_id,
            'permission_id' => $resource_id
        ]);

    }

    /**
     * Is permission granted to role?
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
            'role_id' => $relationship_id,
            'permission_id' => $resource_id
        ]);

    }

    /**
     * Add permission to tenant roles.
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

        // Tenant permission exists

        if (!$this->tenantPermissionsModel->idExists($scoped_id, $resource_id)) {

            $msg = 'Unable to add permission to tenant roles';
            $reason = 'Permission ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'permission_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Add

        $role_ids = $this->tenantRolesModel->getAllIds($scoped_id);

        try {
            $pdo = $this->db->get();
        } catch (InvalidDatabaseException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO api_tenant_role_permissions SET tenantId = UUID_TO_BIN(:tenant_id, 1), roleId = UUID_TO_BIN(:role_id, 1), permissionId = UUID_TO_BIN(:permission_id, 1) 
                                  ON DUPLICATE KEY UPDATE tenantId = VALUES(tenantId), roleId = VALUES(roleId), permissionId = VALUES(permissionId)");

            foreach ($relationship_ids as $role) {

                if (!in_array($role, $role_ids)) {

                    $pdo->rollBack();

                    $msg = 'Unable to add permission to tenant roles';
                    $reason = 'Role ID (' . $role . ') is invalid or does not exist';

                    $this->log->notice($msg, [
                        'reason' => $reason,
                        'tenant_id' => $scoped_id,
                        'permission_id' => $resource_id,
                        'role_id' => $role
                    ]);

                    throw new BadRequestException($msg . ': ' . $reason);

                }

                $stmt->execute([
                    'tenant_id' => $scoped_id,
                    'permission_id' => $resource_id,
                    'role_id' => $role
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log_actions'))) {

            $this->log->info('Permission added to tenant roles', [
                'tenant_id' => $scoped_id,
                'permission_id' => $resource_id,
                'role_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.permission.roles.add', $scoped_id, $resource_id, $relationship_ids);

    }

    /**
     * Get permission roles collection.
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

        // Tenant permission exists

        if (!$this->tenantPermissionsModel->idExists($scoped_id, $resource_id)) {

            $msg = 'Unable to add permission to tenant roles';
            $reason = 'Permission ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'permission_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery();

        try {

            $query->table('api_tenant_roles')
                ->leftJoin('api_tenant_role_permissions', 'api_tenant_roles.id', 'api_tenant_role_permissions.roleId')
                ->where('api_tenant_role_permissions.tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)")
                ->where('api_tenant_role_permissions.permissionId', 'eq', "UUID_TO_BIN('" . $resource_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'name', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant group users collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id,
                'permission_id' => $resource_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant permission roles read', [
                'tenant_id' => $scoped_id,
                'permission_id' => $resource_id,
                'role_ids' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.permission.roles.read', $scoped_id, $resource_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Remove permission from tenant roles.
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

        // Tenant permission exists

        if (!$this->tenantPermissionsModel->idExists($scoped_id, $resource_id)) {

            $msg = 'Unable to remove permission from tenant roles';
            $reason = 'Permission ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'permission_id' => $resource_id
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

            foreach ($relationship_ids as $role) {

                if (!Validate::uuid($role)) {
                    continue;
                }

                $stmt->execute([
                    'tenant_id' => $scoped_id,
                    'role_id' => $role,
                    'permission_id' => $resource_id
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log_actions'))) {

            $this->log->info('Permission removed from tenant roles', [
                'tenant_id' => $scoped_id,
                'permission_id' => $resource_id,
                'role_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.permission.roles.remove', $scoped_id, $resource_id, $relationship_ids);

    }

}

















































