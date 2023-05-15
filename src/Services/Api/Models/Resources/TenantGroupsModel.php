<?php

namespace Bayfront\Bones\Services\Api\Models\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Interfaces\ScopedResourceInterface;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\Validator\Validate;
use Monolog\Logger;

class TenantGroupsModel extends ApiModel implements ScopedResourceInterface
{

    protected TenantsModel $tenantsModel;

    /**
     * @param EventService $events
     * @param Db $db
     * @param Logger $log
     * @param TenantsModel $tenantsModel
     */
    public function __construct(EventService $events, Db $db, Logger $log, TenantsModel $tenantsModel)
    {
        $this->tenantsModel = $tenantsModel;

        parent::__construct($events, $db, $log);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredAttrs(): array
    {
        return [
            'name'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAllowedAttrs(): array
    {
        return [
            'name',
            'description'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAttrsRules(): array
    {
        return [
            'name' => 'string',
            'description' => 'string'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSelectableCols(): array
    {
        return [
            'id' => 'BIN_TO_UUID(id, 1) as id',
            //'tenantId' => 'BIN_TO_UUID(tenantId, 1) as tenantId',
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
     * Get tenant group count.
     *
     * @param string $scoped_id
     * @return int
     */
    public function getCount(string $scoped_id): int
    {

        if (!Validate::uuid($scoped_id)) {
            return 0;
        }

        return $this->db->single("SELECT COUNT(*) FROM api_tenant_groups WHERE tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'tenant_id' => $scoped_id
        ]);

    }

    /**
     * Does tenant group ID exist?
     *
     * @param string $scoped_id
     * @param string $id
     * @return bool
     */
    public function idExists(string $scoped_id, string $id): bool
    {

        if (!Validate::uuid($scoped_id) || !Validate::uuid($id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_tenant_groups WHERE id = UUID_TO_BIN(:id, 1) AND tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'id' => $id,
            'tenant_id' => $scoped_id
        ]);

    }

    /**
     * Does tenant group name exist?
     *
     * @param string $scoped_id
     * @param string $name
     * @param string $exclude_id
     * @return bool
     */
    public function nameExists(string $scoped_id, string $name, string $exclude_id = ''): bool
    {

        if (!Validate::uuid($scoped_id)) {
            return false;
        }

        if ($exclude_id == '') {

            return (bool)$this->db->single("SELECT 1 FROM api_tenant_groups WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND name = :name", [
                'tenant_id' => $scoped_id,
                'name' => $name
            ]);

        }

        if (!Validate::uuid($exclude_id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_tenant_groups WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND name = :name AND id != UUID_TO_BIN(:id, 1)", [
            'tenant_id' => $scoped_id,
            'name' => $name,
            'id' => $exclude_id
        ]);

    }

    /**
     * Create tenant group.
     *
     * @param string $scoped_id
     * @param array $attrs
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws NotFoundException
     */
    public function create(string $scoped_id, array $attrs): string
    {

        // Scoped exists

        if (!$this->tenantsModel->idExists($scoped_id)) {

            $msg = 'Unable to create tenant group';
            $reason = 'Tenant ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Required attributes

        if (Arr::isMissing($attrs, $this->getRequiredAttrs())) {

            $msg = 'Unable to create tenant group';
            $reason = 'Missing required attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to create tenant group';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to create tenant group';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Check name exists

        if ($this->nameExists($scoped_id, $attrs['name'])) {

            $msg = 'Unable to create tenant group';
            $reason = 'Name (' . $attrs['name'] . ') already exists';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new ConflictException($msg . ': ' . $reason);

        }

        // Create

        $uuid = $this->createUUID();

        $attrs['id'] = $uuid['bin'];
        $attrs['tenantId'] = $this->UUIDtoBIN($scoped_id);

        $this->db->insert('api_tenant_groups', $attrs);

        // Log

        if (in_array(Api::ACTION_CREATE, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant group created', [
                'tenant_id' => $scoped_id,
                'group_id' => $uuid['str']
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.group.create', $scoped_id, $uuid['str'], Arr::only($attrs, array_keys($this->getSelectableCols())));

        return $uuid['str'];

    }

    /**
     * Get tenant group collection.
     *
     * @param string $scoped_id
     * @param array $args
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function getCollection(string $scoped_id, array $args = []): array
    {

        if (empty($args['select'])) {
            $args['select'][] = '*';
        } else {
            $args['select'] = array_merge($args['select'], ['id']); // Force return ID
        }

        // Scoped exists

        if (!$this->tenantsModel->idExists($scoped_id)) {

            $msg = 'Unable to get tenant group collection';
            $reason = 'Tenant ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        try {

            $query = $this->startNewQuery()->table('api_tenant_groups')
                ->where('tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'name', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant group collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant group read', [
                'tenant_id' => $scoped_id,
                'group_id' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.group.read', $scoped_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Get tenant group.
     *
     * @param string $scoped_id
     * @param string $id
     * @param array $cols
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function get(string $scoped_id, string $id, array $cols = ['*']): array
    {

        if (empty($cols)) {
            $cols[] = '*';
        } else {
            $cols = array_merge($cols, ['id']); // Force return ID
        }

        // Exists

        if (!$this->idExists($scoped_id, $id)) {

            $msg = 'Unable to get tenant group';
            $reason = 'Tenant and / or group ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'group_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery()->table('api_tenant_groups');

        try {

            $query->where('id', 'eq', "UUID_TO_BIN('" . $id . "', 1)")
                ->where('tenantID', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $result = $this->querySingle($query, $cols, $this->getSelectableCols(), $this->getJsonCols());

        } catch (BadRequestException|NotFoundException $e) {

            $msg = 'Unable to get tenant group';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id,
                'group_id' => $id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant group read', [
                'tenant_id' => $scoped_id,
                'group_id' => [$result['id']]
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.group.read', $scoped_id, [$result['id']]);

        return $result;

    }

    /**
     * Update tenant group.
     *
     * @param string $scoped_id
     * @param string $id
     * @param array $attrs
     * @return void
     * @throws BadRequestException
     * @throws ConflictException
     * @throws NotFoundException
     */
    public function update(string $scoped_id, string $id, array $attrs): void
    {

        if (empty($attrs)) { // Nothing to update
            return;
        }

        // Exists

        if (!$this->idExists($scoped_id, $id)) {

            $msg = 'Unable to update tenant group';
            $reason = 'Tenant and / or group ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'group_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to update tenant group';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'group_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to update tenant group';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'group_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Check name exists

        if (isset($attrs['name']) && $this->nameExists($scoped_id, $attrs['name'], $id)) {

            $msg = 'Unable to update tenant group';
            $reason = 'Name (' . $attrs['name'] . ') already exists';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'group_id' => $id
            ]);

            throw new ConflictException($msg . ': ' . $reason);

        }

        // Update

        $placeholders = [];

        /** @noinspection SqlWithoutWhere */
        $query = "UPDATE api_tenant_groups SET" . " ";

        foreach ($attrs as $k => $v) {
            $placeholders[] = $v;
            $query .= $k . ' = ? ';
        }

        $query .= "WHERE id = UUID_TO_BIN(?, 1) AND tenantId = UUID_TO_BIN(?, 1)";
        array_push($placeholders, $id, $scoped_id);

        $this->db->query($query, $placeholders);

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant group updated', [
                'tenant_id' => $scoped_id,
                'group_id' => $id
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.group.update', $scoped_id, $id, Arr::only($attrs, array_keys($this->getSelectableCols())));

    }

    /**
     * Delete tenant group.
     *
     * @param string $scoped_id
     * @param string $id
     * @return void
     * @throws NotFoundException
     */
    public function delete(string $scoped_id, string $id): void
    {

        if (!$this->idExists($scoped_id, $id)) {

            $msg = 'Unable to delete tenant group';
            $reason = 'Tenant and / or group ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'group_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Delete

        $this->db->query("DELETE FROM api_tenant_groups WHERE id = UUID_TO_BIN(:id, 1) AND tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'id' => $id,
            'tenant_id' => $scoped_id
        ]);

        // Log

        if (in_array(Api::ACTION_DELETE, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant group deleted', [
                'tenant_id' => $scoped_id,
                'group_id' => $id
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.group.delete', $scoped_id, $id);

    }

}