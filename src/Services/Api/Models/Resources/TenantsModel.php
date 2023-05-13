<?php

namespace Bayfront\Bones\Services\Api\Models\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Abstracts\Models\ApiModel;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Interfaces\Models\ModelResourceInterface;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\Validator\Validate;
use Bayfront\Validator\ValidationException;
use Exception;
use Monolog\Logger;

class TenantsModel extends ApiModel implements ModelResourceInterface
{

    public function __construct(EventService $events, Db $db, Logger $log)
    {
        parent::__construct($events, $db, $log);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredAttrs(): array
    {
        return [
            'owner',
            'name'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAllowedAttrs(): array
    {
        return [
            'owner',
            'name',
            'meta',
            'enabled'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAttrsRules(): array
    {
        return [
            'name' => 'string',
            'meta' => 'array',
            'enabled' => 'boolean'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSelectableCols(): array
    {
        return [
            'id' => 'BIN_TO_UUID(id, 1) as id',
            'owner' => 'BIN_TO_UUID(owner, 1) as owner',
            'name' => 'name',
            'meta' => 'meta',
            'enabled' => 'enabled',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getJsonCols(): array
    {
        return [
            'meta'
        ];
    }

    /**
     * Get tenant count.
     *
     * @inheritDoc
     */
    public function getCount(): int
    {
        return $this->db->single("SELECT COUNT(*) FROM api_tenants");
    }

    /**
     * Does tenant ID exist?
     *
     * @inheritDoc
     */
    public function idExists(string $id): bool
    {

        if (!Validate::uuid($id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_tenants WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

    }

    /**
     * Does tenant name exist?
     *
     * @param string $name
     * @param string $exclude_id
     * @return bool
     */
    public function nameExists(string $name, string $exclude_id = ''): bool
    {

        if ($exclude_id == '') {

            return (bool)$this->db->single("SELECT 1 FROM api_tenants WHERE name = :name", [
                'name' => $name
            ]);

        }

        if (!Validate::uuid($exclude_id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_tenants WHERE name = :name AND id != UUID_TO_BIN(:id, 1)", [
            'name' => $name,
            'id' => $exclude_id
        ]);

    }

    /**
     * Is tenant enabled?
     *
     * @param string $id
     * @return bool
     */
    public function isEnabled(string $id): bool
    {

        if (!Validate::uuid($id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT enabled FROM api_tenants WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

    }

    /**
     * Create tenant.
     *
     * @param array $attrs
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     */
    public function create(array $attrs): string
    {

        // Required attributes

        if (Arr::isMissing($attrs, $this->getRequiredAttrs())) {

            $msg = 'Unable to create tenant';
            $reason = 'Missing required attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to create tenant';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        try {

            Validate::as($attrs, $this->getAttrsRules());

        } catch (ValidationException) {

            $msg = 'Unable to create tenant';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Validate meta

        if (isset($attrs['meta'])) {

            try {

                Validate::as($attrs['meta'], App::getConfig('api.required_meta.tenants'), true);

            } catch (ValidationException) {

                $msg = 'Unable to create tenant';
                $reason = 'Missing or invalid meta attribute(s)';

                $this->log->notice($msg, [
                    'reason' => $reason
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

            $attrs['meta'] = $this->encodeMeta($attrs['meta']);

        }

        // Check name exists

        if ($this->nameExists($attrs['name'])) {

            $msg = 'Unable to create tenant';
            $reason = 'Name (' . $attrs['name'] . ') already exists';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new ConflictException($msg . ': ' . $reason);

        }

        // Enabled

        if (isset($attrs['enabled'])) { // Cast to integer
            $attrs['enabled'] = (int)$attrs['enabled'];
        }

        // Tenant ID

        $uuid = $this->createUUID();

        $attrs['id'] = $uuid['bin'];

        // Owner ID

        $owner_id = $attrs['owner'];
        $attrs['owner'] = $this->UUIDtoBIN($attrs['owner']);

        // Create

        try {

            $this->db->beginTransaction();

            //$this->db->insert('api_tenants', $attrs);

            /** @noinspection SqlInsertValues */
            $sql = sprintf("INSERT INTO api_tenants (%s) VALUES (%s)",
                implode(', ', array_keys($attrs)),
                implode(', ', array_fill(0, count($attrs), '?')));

            $this->db->query($sql, array_values($attrs));

            // Add owner as tenant user

            $this->db->query("INSERT INTO api_tenant_users SET tenantId = :tenant_id, userId = :user_id", [
                'tenant_id' => $attrs['id'], // Already as BIN
                'user_id' => $attrs['owner'] // Already as BIN
            ]);

        } catch (Exception $e) {
            die($e->getMessage());


            $this->db->rollbackTransaction();

            $msg = 'Unable to create tenant';
            $reason = 'Owner ID (' . $owner_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Log

        if (in_array(Api::ACTION_CREATE, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant created', [
                'tenant_id' => $uuid['str']
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.create', $uuid['str'], Arr::only($attrs, array_keys($this->getSelectableCols())));

        return $uuid['str'];

    }

    /**
     * Get tenant collection.
     *
     * @param array $args
     * @return array
     * @throws BadRequestException
     * @throws UnexpectedApiException
     */
    public function getCollection(array $args = []): array
    {

        if (empty($args['select'])) {
            $args['select'][] = '*';
        } else {
            $args['select'] = array_merge($args['select'], ['id']); // Force return ID
        }

        // Query

        $query = $this->startNewQuery()->table('api_tenants');

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'id', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage()
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant read', [
                'tenant_id' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.read', Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Get tenant.
     *
     * @param string $id
     * @param array $cols
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function get(string $id, array $cols = []): array
    {

        if (empty($cols)) {
            $cols[] = '*';
        } else {
            $cols = array_merge($cols, ['id']); // Force return ID
        }

        // UUID

        if (!Validate::uuid($id)) {

            $msg = 'Unable to get tenant';
            $reason = 'Invalid tenant ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery()->table('api_tenants');

        try {

            $query->where('id', 'eq', "UUID_TO_BIN('" . $id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $result = $this->querySingle($query, $cols, $this->getSelectableCols(), $this->getJsonCols());

        } catch (BadRequestException|NotFoundException $e) {

            $msg = 'Unable to get tenant';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant read', [
                'tenant_id' => [$result['id']]
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.read', [$result['id']]);

        return $result;

    }

    /**
     * Update tenant.
     * New owners must already belong to tenant.
     *
     * @param string $id
     * @param array $attrs
     * @return void
     * @throws BadRequestException
     * @throws ConflictException
     * @throws NotFoundException
     */
    public function update(string $id, array $attrs): void
    {

        if (empty($attrs)) { // Nothing to update
            return;
        }

        // UUID

        if (!Validate::uuid($id)) {

            $msg = 'Unable to update tenant';
            $reason = 'Invalid tenant ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to update tenant';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        try {

            Validate::as($attrs, $this->getAttrsRules());

        } catch (ValidationException) {

            $msg = 'Unable to update tenant';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Check exists

        $existing = $this->db->row("SELECT id, meta from api_tenants WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

        if (!$existing) {

            $msg = 'Unable to update tenant';
            $reason = 'Does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Validate meta

        if (isset($attrs['meta'])) {

            if ($existing['meta']) {
                $attrs['meta'] = array_merge(json_decode($existing['meta'], true), $attrs['meta']);
            } else {
                $attrs['meta'] = json_decode($existing['meta'], true);
            }

            // Validate meta

            try {

                Validate::as($attrs['meta'], App::getConfig('api.required_meta.tenants'), true);

            } catch (ValidationException) {

                $msg = 'Unable to update tenant';
                $reason = 'Missing or invalid meta attribute(s)';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'tenant_id' => $id
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

            $attrs['meta'] = $this->encodeMeta($attrs['meta']);

        }

        // Tenant owner

        if (isset($attrs['owner'])) {

            $owner_exists = $this->db->query("SELECT 1 from api_tenant_users WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)", [
                'tenant_id' => $id,
                'user_id' => $attrs['owner']
            ]);

            if (!$owner_exists) {

                $msg = 'Unable to update tenant';
                $reason = 'Owner ID (' . $attrs['owner'] . ') does not exist in tenant';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'tenant_id' => $id
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

        }

        // Check name exists

        if (isset($attrs['name'])) {

            if ($this->nameExists($attrs['name'], $id)) {

                $msg = 'Unable to update tenant';
                $reason = 'Name (' . $attrs['name'] . ') already exists';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'tenant_id' => $id
                ]);

                throw new ConflictException($msg . ': ' . $reason);

            }

        }

        // Enabled

        if (isset($attrs['enabled'])) { // Cast to integer
            $attrs['enabled'] = (int)$attrs['enabled'];
        }

        // Update

        $this->db->update('api_tenants', $attrs, [
            'id' => $existing['id']
        ]);

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant updated', [
                'tenant_id' => $id
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.update', $id, $attrs);

    }

    /**
     * Delete tenant.
     *
     * @param string $id
     * @return void
     * @throws NotFoundException
     */
    public function delete(string $id): void
    {

        // UUID

        if (!Validate::uuid($id)) {

            $msg = 'Unable to delete tenant';
            $reason = 'Invalid tenant ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Delete

        $this->db->query("DELETE FROM api_tenants WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

        if ($this->db->rowCount() > 0) {

            // Log

            if (in_array(Api::ACTION_DELETE, App::getConfig('api.log_actions'))) {

                $this->log->info('Tenant deleted', [
                    'tenant_id' => $id
                ]);

            }

            // Event

            $this->events->doEvent('api.tenant.delete', $id);

            return;

        }

        $msg = 'Unable to delete tenant';
        $reason = 'Tenant does not exist';

        $this->log->notice($msg, [
            'reason' => $reason,
            'tenant_id' => $id
        ]);

        throw new NotFoundException($msg . ': ' . $reason);

    }

}