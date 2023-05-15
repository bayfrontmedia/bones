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
use Bayfront\Bones\Services\Api\Models\Resources\TenantRolesModel;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\InvalidDatabaseException;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\Validator\Validate;
use Monolog\Logger;
use PDOException;

class TenantUserRolesModel extends ApiModel implements ScopedRelationshipInterface
{

    protected TenantRolesModel $tenantRolesModel;
    protected TenantUsersModel $tenantUsersModel;

    public function __construct(EventService $events, Db $db, Logger $log, TenantRolesModel $tenantRolesModel, TenantUsersModel $tenantUsersModel)
    {
        $this->tenantRolesModel = $tenantRolesModel;
        $this->tenantUsersModel = $tenantUsersModel;

        parent::__construct($events, $db, $log);
    }

    /**
     * @inheritDoc
     */
    public function getSelectableCols(): array
    {
        return [
            'id' => 'BIN_TO_UUID(api_tenant_roles.id, 1) as id',
            //'tenantId' => 'BIN_TO_UUID(api_tenant_roles.tenantId, 1) as tenantId',
            'name' => 'name',
            'description' => 'description',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getJsonCols(): array
    {
        return [];
    }

    /**
     * Get roles count for tenant user.
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

        return $this->db->single("SELECT COUNT(*) FROM api_tenant_user_roles WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)", [
            'tenant_id' => $scoped_id,
            'user_id' => $resource_id
        ]);

    }

    /**
     * Does tenant user have role?
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

        return (bool)$this->db->single("SELECT 1 FROM api_tenant_user_roles WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1) AND roleId = UUID_TO_BIN(:role_id, 1)", [
            'tenant_id' => $scoped_id,
            'user_id' => $resource_id,
            'role_id' => $relationship_id
        ]);

    }

    /**
     * Add roles to tenant user.
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

        // Tenant user exists

        if (!$this->tenantUsersModel->has($scoped_id, $resource_id)) {

            $msg = 'Unable to add roles to tenant user';
            $reason = 'User ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id
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

            $stmt = $pdo->prepare("INSERT INTO api_tenant_user_roles SET tenantId = UUID_TO_BIN(:tenant_id, 1), userId = UUID_TO_BIN(:user_id, 1), roleId = UUID_TO_BIN(:role_id, 1) 
                                  ON DUPLICATE KEY UPDATE tenantId = VALUES(tenantId), userId = VALUES(userId), roleId = VALUES(roleId)");

            foreach ($relationship_ids as $role) {

                if (!in_array($role, $role_ids)) {

                    $pdo->rollBack();

                    $msg = 'Unable to add roles to tenant user';
                    $reason = 'Role ID (' . $role . ') is invalid or does not exist';

                    $this->log->notice($msg, [
                        'reason' => $reason,
                        'tenant_id' => $scoped_id,
                        'user_id' => $resource_id,
                        'role_id' => $role
                    ]);

                    throw new BadRequestException($msg . ': ' . $reason);

                }

                $stmt->execute([
                    'tenant_id' => $scoped_id,
                    'user_id' => $resource_id,
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

            $this->log->info('Roles added to tenant user', [
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id,
                'role_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.roles.add', $scoped_id, $resource_id, $relationship_ids);

    }

    /**
     * Get tenant user roles collection.
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

        // Tenant user exists

        if (!$this->tenantUsersModel->has($scoped_id, $resource_id)) {

            $msg = 'Unable to get to tenant user roles collection';
            $reason = 'User ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery();

        try {

            $query->table('api_tenant_roles')
                ->leftJoin('api_tenant_user_roles', 'api_tenant_roles.id', 'api_tenant_user_roles.roleId')
                ->where('api_tenant_user_roles.tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)")
                ->where('api_tenant_user_roles.userId', 'eq', "UUID_TO_BIN('" . $resource_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'name', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant user roles collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant user roles read', [
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id,
                'role_ids' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.roles.read', $scoped_id, $resource_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Remove roles from tenant user.
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

        // Tenant user exists

        if (!$this->tenantUsersModel->has($scoped_id, $resource_id)) {

            $msg = 'Unable to remove roles from tenant user';
            $reason = 'User ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id
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

            $stmt = $pdo->prepare("DELETE FROM api_tenant_user_roles WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1) AND roleId = UUID_TO_BIN(:role_id, 1)");

            foreach ($relationship_ids as $role) {

                if (!Validate::uuid($role)) {
                    continue;
                }

                $stmt->execute([
                    'tenant_id' => $scoped_id,
                    'user_id' => $resource_id,
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

            $this->log->info('Roles removed from tenant user', [
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id,
                'role_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.roles.remove', $scoped_id, $resource_id, $relationship_ids);

    }

}