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
use Bayfront\Bones\Services\Api\Models\Resources\TenantsModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\MultiLogger\Log;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\InvalidDatabaseException;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\Validator\Validate;
use PDOException;

class UserTenantsModel extends ApiModel implements RelationshipInterface
{

    protected TenantsModel $tenantsModel;
    protected UsersModel $usersModel;

    public function __construct(EventService $events, Db $db, Log $log, TenantsModel $tenantsModel, UsersModel $usersModel)
    {
        $this->tenantsModel = $tenantsModel;
        $this->usersModel = $usersModel;

        parent::__construct($events, $db, $log);
    }

    /**
     * @inheritDoc
     */
    public function getSelectableCols(): array
    {
        return $this->tenantsModel->getSelectableCols();
    }

    /**
     * @inheritDoc
     */
    public function getJsonCols(): array
    {
        return $this->tenantsModel->getJsonCols();
    }

    /**
     * Get user tenants count.
     *
     * @param string $resource_id
     * @return int
     */
    public function getCount(string $resource_id): int
    {

        if (!Validate::uuid($resource_id)) {
            return 0;
        }

        return $this->db->single("SELECT COUNT(*) FROM api_tenant_users WHERE userId = UUID_TO_BIN(:user_id, 1)", [
            'user_id' => $resource_id
        ]);

    }

    /**
     * Is user in tenant?
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
            'tenant_id' => $relationship_id,
            'user_id' => $resource_id
        ]);

    }

    /**
     * Add user to tenants.
     *
     * @param string $resource_id
     * @param array $relationship_ids
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function add(string $resource_id, array $relationship_ids): void
    {

        // Exists

        if (!$this->usersModel->idExists($resource_id)) {

            $msg = 'Unable to add user to tenants';
            $reason = 'User ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

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

            foreach ($relationship_ids as $tenant) {

                if (!$this->tenantsModel->idExists($tenant)) {

                    $pdo->rollBack();

                    $msg = 'Unable to add user to tenants';
                    $reason = 'Tenant ID (' . $tenant . ') is invalid or does not exist';

                    $this->log->notice($msg, [
                        'reason' => $reason,
                        'user_id' => $resource_id,
                        'tenant_id' => $tenant
                    ]);

                    throw new BadRequestException($msg . ': ' . $reason);

                }

                $stmt->execute([
                    'tenant_id' => $tenant,
                    'user_id' => $resource_id
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.audit.actions'))) {

            $this->auditLogChannel->info('User added to tenants', [
                'action' => 'api.user.tenants.add',
                'user_id' => $resource_id,
                'tenant_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.user.tenants.add', $resource_id, $relationship_ids);

    }

    /**
     * Get user tenants collection.
     * Tenants who user owns or belongs to.
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

        if (!$this->usersModel->idExists($resource_id)) {

            $msg = 'Unable to get user tenants';
            $reason = 'User ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery();

        try {

            $query->table('api_tenants')
                ->leftJoin('api_tenant_users', 'api_tenants.id', 'api_tenant_users.tenantId')
                ->where('api_tenant_users.userId', 'eq', "UUID_TO_BIN('" . $resource_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'id', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get user tenants collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'user_id' => $resource_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.audit.actions'))) {

            $this->auditLogChannel->info('User tenants read', [
                'action' => 'api.user.tenants.read',
                'user_id' => $resource_id,
                'tenant_ids' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.user.tenants.read', $resource_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Remove user from tenants.
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

        if (!$this->usersModel->idExists($resource_id)) {

            $msg = 'Unable to remove user from tenants';
            $reason = 'User ID (' . $resource_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        $owned = $this->usersModel->getOwnedTenantIds($resource_id);

        // Remove

        try {
            $pdo = $this->db->get();
        } catch (InvalidDatabaseException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM api_tenant_users WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)");

            foreach ($relationship_ids as $tenant) {

                if (!Validate::uuid($tenant)) {
                    continue;
                }

                if (in_array($tenant, $owned)) {

                    $pdo->rollBack();

                    $msg = 'Unable to remove user from tenants';
                    $reason = 'Tenant ID (' . $tenant . ') is owned by user';

                    $this->log->notice($msg, [
                        'reason' => $reason,
                        'user_id' => $resource_id,
                        'tenant_id' => $tenant
                    ]);

                    throw new ForbiddenException($msg . ': ' . $reason);

                }

                $stmt->execute([
                    'user_id' => $resource_id,
                    'tenant_id' => $tenant
                ]);

            }

            $pdo->commit();

        } catch (PDOException $e) {

            $pdo->rollBack();

            throw new UnexpectedApiException($e->getMessage());

        }

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.audit.actions'))) {

            $this->auditLogChannel->info('User removed from tenants', [
                'action' => 'api.user.tenants.remove',
                'user_id' => $resource_id,
                'tenant_ids' => $relationship_ids
            ]);

        }

        // Event

        $this->events->doEvent('api.user.tenants.remove', $resource_id, $relationship_ids);

    }

}