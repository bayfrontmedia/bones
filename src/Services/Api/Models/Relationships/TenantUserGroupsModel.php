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
use Bayfront\Bones\Services\Api\Models\Resources\TenantGroupsModel;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\MultiLogger\Log;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\InvalidDatabaseException;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\Validator\Validate;
use PDOException;

class TenantUserGroupsModel extends ApiModel implements ScopedRelationshipInterface
{

    protected TenantGroupsModel $tenantGroupsModel;
    protected TenantUsersModel $tenantUsersModel;

    public function __construct(EventService $events, Db $db, Log $log, TenantGroupsModel $tenantGroupsModel, TenantUsersModel $tenantUsersModel)
    {
        $this->tenantGroupsModel = $tenantGroupsModel;
        $this->tenantUsersModel = $tenantUsersModel;

        parent::__construct($events, $db, $log);
    }

    /**
     * @inheritDoc
     */
    public function getSelectableCols(): array
    {
        return $this->tenantGroupsModel->getSelectableCols();
    }

    /**
     * @inheritDoc
     */
    public function getJsonCols(): array
    {
        return $this->tenantGroupsModel->getJsonCols();
    }

    /**
     * Get count of tenant user groups.
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

        return $this->db->single("SELECT COUNT(*) FROM api_tenant_group_users WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)", [
            'tenant_id' => $scoped_id,
            'user_id' => $resource_id
        ]);

    }

    /**
     * Is tenant user in group?
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

        return (bool)$this->db->single("SELECT 1 FROM api_tenant_group_users WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND groupId = UUID_TO_BIN(:group_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)", [
            'tenant_id' => $scoped_id,
            'group_id' => $relationship_id,
            'user_id' => $resource_id
        ]);

    }

    /**
     * Add user to tenant groups.
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

            $msg = 'Unable to add user to tenant groups';
            $reason = 'User ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Add

        $group_ids = $this->tenantGroupsModel->getAllIds($scoped_id);

        try {
            $pdo = $this->db->get();
        } catch (InvalidDatabaseException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO api_tenant_group_users SET tenantId = UUID_TO_BIN(:tenant_id, 1), groupId = UUID_TO_BIN(:group_id, 1), userId = UUID_TO_BIN(:user_id, 1) 
                                  ON DUPLICATE KEY UPDATE tenantId = VALUES(tenantId), groupId = VALUES(groupId), userId = VALUES(userId)");

            foreach ($relationship_ids as $group) {

                if (!in_array($group, $group_ids)) {

                    $pdo->rollBack();

                    $msg = 'Unable to add user to tenant groups';
                    $reason = 'Group ID (' . $group . ') is invalid or does not exist';

                    $this->log->notice($msg, [
                        'reason' => $reason,
                        'tenant_id' => $scoped_id,
                        'user_id' => $resource_id,
                        'group_id' => $group
                    ]);

                    throw new BadRequestException($msg . ': ' . $reason);

                }

                $stmt->execute([
                    'tenant_id' => $scoped_id,
                    'group_id' => $group,
                    'user_id' => $resource_id
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->log->info('User added to tenant groups', [
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id,
                'group_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.groups.add', $scoped_id, $resource_id, $relationship_ids);

    }

    /**
     * Get tenant user groups collection.
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

            $msg = 'Unable to get tenant user groups collection';
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

            $query->table('api_tenant_groups')
                ->leftJoin('api_tenant_group_users', 'api_tenant_groups.id', 'api_tenant_group_users.groupId')
                ->where('api_tenant_group_users.tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)")
                ->where('api_tenant_group_users.userId', 'eq', "UUID_TO_BIN('" . $resource_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'name', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant user group collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

            $this->log->info('Tenant user groups read', [
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id,
                'group_ids' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.groups.read', $scoped_id, $resource_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Remove user from tenant groups.
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

            $msg = 'Unable to remove user from tenant groups';
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

            $stmt = $pdo->prepare("DELETE FROM api_tenant_group_users WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND groupId = UUID_TO_BIN(:group_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)");

            foreach ($relationship_ids as $group) {

                if (!Validate::uuid($group)) {
                    continue;
                }

                $stmt->execute([
                    'tenant_id' => $scoped_id,
                    'group_id' => $group,
                    'user_id' => $resource_id
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->log->info('User removed from tenant groups', [
                'tenant_id' => $scoped_id,
                'user_id' => $resource_id,
                'group_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.groups.remove', $scoped_id, $resource_id, $relationship_ids);

    }

}