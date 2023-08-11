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

class TenantRoleUsersModel extends ApiModel implements ScopedRelationshipInterface
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
        return $this->tenantUsersModel->getSelectableCols();
    }

    /**
     * @inheritDoc
     */
    public function getJsonCols(): array
    {
        return $this->tenantUsersModel->getJsonCols();
    }

    /**
     * Get count of role users.
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

        return $this->db->single("SELECT COUNT(*) FROM api_tenant_user_roles WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND roleId = UUID_TO_BIN(:role_id, 1)", [
            'tenant_id' => $scoped_id,
            'role_id' => $resource_id
        ]);

    }

    /**
     * Does tenant role have user?
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
            'user_id' => $relationship_id,
            'role_id' => $resource_id
        ]);

    }

    /**
     * Add users to tenant role.
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

            $msg = 'Unable to add users to tenant role';
            $reason = 'Role ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Add

        $user_ids = $this->tenantUsersModel->getAllIds($scoped_id);

        try {
            $pdo = $this->db->get();
        } catch (InvalidDatabaseException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO api_tenant_user_roles SET tenantId = UUID_TO_BIN(:tenant_id, 1), userId = UUID_TO_BIN(:user_id, 1), roleId = UUID_TO_BIN(:role_id, 1) 
                                  ON DUPLICATE KEY UPDATE tenantId = VALUES(tenantId), userId = VALUES(userId), roleId = VALUES(roleId)");

            foreach ($relationship_ids as $user) {

                if (!in_array($user, $user_ids)) {

                    $pdo->rollBack();

                    $msg = 'Unable to add users to tenant role';
                    $reason = 'User ID (' . $user . ') is invalid or does not exist';

                    $this->log->notice($msg, [
                        'reason' => $reason,
                        'tenant_id' => $scoped_id,
                        'role_id' => $resource_id,
                        'user_id' => $user
                    ]);

                    throw new BadRequestException($msg . ': ' . $reason);

                }

                $stmt->execute([
                    'tenant_id' => $scoped_id,
                    'user_id' => $user,
                    'role_id' => $resource_id
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->log->info('Users added to tenant role', [
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id,
                'user_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.role.users.add', $scoped_id, $resource_id, $relationship_ids);

    }

    /**
     * Get tenant role users collection.
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

            $msg = 'Unable to add users to tenant role';
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

            $query->table('api_users')
                ->leftJoin('api_tenant_user_roles', 'api_users.id', 'api_tenant_user_roles.userId')
                ->where('api_tenant_user_roles.tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)")
                ->where('api_tenant_user_roles.roleId', 'eq', "UUID_TO_BIN('" . $resource_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'id', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant role users collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

            $this->log->info('Tenant role users read', [
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id,
                'user_ids' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.role.users.read', $scoped_id, $resource_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Remove users from tenant role.
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

            $msg = 'Unable to remove users from tenant role';
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

            $stmt = $pdo->prepare("DELETE FROM api_tenant_user_roles WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1) AND roleId = UUID_TO_BIN(:role_id, 1)");

            foreach ($relationship_ids as $user) {

                if (!Validate::uuid($user)) {
                    continue;
                }

                $stmt->execute([
                    'tenant_id' => $scoped_id,
                    'user_id' => $user,
                    'role_id' => $resource_id
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->log->info('Users removed from tenant role', [
                'tenant_id' => $scoped_id,
                'role_id' => $resource_id,
                'user_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.role.users.remove', $scoped_id, $resource_id, $relationship_ids);

    }

}