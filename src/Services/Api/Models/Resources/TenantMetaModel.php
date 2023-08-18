<?php

namespace Bayfront\Bones\Services\Api\Models\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\BonesException;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Interfaces\ScopedResourceInterface;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\MultiLogger\Log;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\StringHelpers\Str;
use Bayfront\Validator\Validate;

class TenantMetaModel extends ApiModel implements ScopedResourceInterface
{

    protected TenantsModel $tenantsModel;

    public function __construct(EventService $events, Db $db, Log $log, TenantsModel $tenantsModel)
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
            'id',
            'metaValue'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAllowedAttrs(): array
    {
        return [
            'id',
            'metaValue'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAttrsRules(): array
    {
        return [
            'id' => 'string'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSelectableCols(): array
    {
        return [
            'id' => 'id',
            //'tenantId' => 'BIN_TO_UUID(tenantId, 1) as tenantId',
            'metaValue' => 'metaValue',
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
     * Get tenant meta count.
     *
     * @inheritDoc
     */
    public function getCount(string $scoped_id, bool $allow_protected = false): int
    {

        if (!Validate::uuid($scoped_id)) {
            return 0;
        }

        if ($allow_protected) {

            return $this->db->single("SELECT COUNT(*) FROM api_tenant_meta WHERE tenantId = UUID_TO_BIN(:tenant_id, 1)", [
                'tenant_id' => $scoped_id
            ]);

        } else {

            return $this->db->single("SELECT COUNT(*) FROM api_tenant_meta WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND id NOT LIKE '00-%'", [
                'tenant_id' => $scoped_id
            ]);

        }

    }

    /**
     * Does tenant meta exist?
     *
     * @inheritDoc
     */
    public function idExists(string $scoped_id, string $id, bool $allow_protected = false): bool
    {

        if (!Validate::uuid($scoped_id)) {
            return false;
        }

        if ($allow_protected) {

            return (bool)$this->db->single("SELECT 1 FROM api_tenant_meta WHERE id = :id AND tenantId = UUID_TO_BIN(:tenant_id, 1)", [
                'id' => $id,
                'tenant_id' => $scoped_id
            ]);

        } else {

            return (bool)$this->db->single("SELECT 1 FROM api_tenant_meta WHERE id = :id AND tenantId = UUID_TO_BIN(:tenant_id, 1) AND id NOT LIKE '00-%'", [
                'id' => $id,
                'tenant_id' => $scoped_id
            ]);

        }

    }

    /**
     * Create tenant meta.
     *
     * @param string $scoped_id
     * @param array $attrs
     * @param bool $allow_protected
     * @param bool $overwrite (Overwrite if existing?)
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function create(string $scoped_id, array $attrs, bool $allow_protected = false, bool $overwrite = false): string
    {

        // Scoped exists

        if (!$this->tenantsModel->idExists($scoped_id)) {

            $msg = 'Unable to create tenant meta';
            $reason = 'Tenant does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Required attributes

        if (Arr::isMissing($attrs, $this->getRequiredAttrs())) {

            $msg = 'Unable to create tenant meta';
            $reason = 'Missing required attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to create tenant meta';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to create tenant meta';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_meta' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Protected

        $attrs['id'] = Str::kebabCase($attrs['id'], true);

        if ($allow_protected === false && str_starts_with($attrs['id'], '00-')) {

            $msg = 'Unable to create tenant meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        if ($overwrite === false) {

            // Check exists

            if ($this->idExists($scoped_id, $attrs['id'])) {

                $msg = 'Unable to create tenant meta';
                $reason = 'ID (' . $attrs['id'] . ') already exists';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'tenant_id' => $scoped_id
                ]);

                throw new ConflictException($msg . ': ' . $reason);

            }

            // Create

            $this->db->query("INSERT INTO api_tenant_meta SET id = :id, tenantId = UUID_TO_BIN(:tenant_id, 1), metaValue = :value", [
                'id' => $attrs['id'],
                'tenant_id' => $scoped_id,
                'value' => $attrs['metaValue']
            ]);

        } else {

            // Create

            $this->db->query("INSERT INTO api_tenant_meta SET id = :id, tenantId = UUID_TO_BIN(:tenant_id, 1), metaValue = :value
                                        ON DUPLICATE KEY UPDATE id=VALUES(id), tenantId=VALUES(tenantId), metaValue=VALUES(metaValue)", [
                'id' => $attrs['id'],
                'tenant_id' => $scoped_id,
                'value' => $attrs['metaValue']
            ]);

        }

        // Log

        if (in_array(Api::ACTION_CREATE, App::getConfig('api.log.audit.actions'))) {

            $context = [
                'action' => 'api.tenant.meta.create',
                'tenant_id' => $scoped_id,
                'meta_id' => $attrs['id']
            ];

            if (App::getConfig('api.log.audit.include_resource')) {
                $context['resource'] = Arr::only($attrs, $this->getAllowedAttrs());
            }

            $this->auditLogChannel->info('Tenant meta created', $context);

        }

        // Event

        $this->events->doEvent('api.tenant.meta.create', $scoped_id, $attrs['id'], Arr::only($attrs, $this->getAllowedAttrs()));

        return $attrs['id'];

    }

    /**
     * Get tenant meta collection.
     *
     * @param string $scoped_id
     * @param array $args
     * @param bool $allow_protected
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function getCollection(string $scoped_id, array $args = [], bool $allow_protected = false): array
    {

        if (empty($args['select'])) {
            $args['select'][] = '*';
        } else {
            $args['select'] = array_merge($args['select'], ['id']); // Force return ID
        }

        // Scoped exists

        if (!$this->tenantsModel->idExists($scoped_id)) {

            $msg = 'Unable to get tenant meta collection';
            $reason = 'Tenant does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        try {

            $query = $this->startNewQuery()->table('api_tenant_meta')
                ->where('tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)");

            if ($allow_protected === false) {

                $query->where('id', '!sw', '00-');

            }

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'id', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant meta collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'user_id' => $scoped_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.audit.actions'))) {

            $this->auditLogChannel->info('Tenant meta read', [
                'action' => 'api.tenant.meta.read',
                'tenant_id' => $scoped_id,
                'meta_id' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.meta.read', $scoped_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Get tenant meta.
     *
     * @param string $scoped_id
     * @param string $id
     * @param array $cols
     * @param bool $allow_protected
     * @return array
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function get(string $scoped_id, string $id, array $cols = [], bool $allow_protected = false): array
    {

        if (empty($cols)) {
            $cols[] = '*';
        } else {
            $cols = array_merge($cols, ['id']); // Force return ID
        }

        // Exists

        if (!$this->idExists($scoped_id, $id, $allow_protected)) {

            $msg = 'Unable to get tenant meta';
            $reason = 'Tenant and / or meta ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Protected

        if ($allow_protected === false && str_starts_with($id, '00-')) {

            $msg = 'Unable to get tenant meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery()->table('api_tenant_meta');

        try {

            $query->where('id', 'eq', $id)
                ->where('tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $result = $this->querySingle($query, $cols, $this->getSelectableCols(), $this->getJsonCols());

        } catch (BadRequestException|NotFoundException $e) {

            $msg = 'Unable to get tenant meta';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.audit.actions'))) {

            $this->auditLogChannel->info('Tenant meta read', [
                'action' => 'api.tenant.meta.read',
                'tenant_id' => $scoped_id,
                'meta_id' => [$result['id']]
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.meta.read', $scoped_id, [$result['id']]);

        return $result;

    }

    /**
     * Get value of single tenant meta or false if not existing.
     *
     * @param string $scoped_id
     * @param string $id
     * @param bool $allow_protected
     * @return mixed
     */
    public function getValue(string $scoped_id, string $id, bool $allow_protected = false): mixed
    {

        try {

            $result = $this->get($scoped_id, $id, ['metaValue'], $allow_protected);

        } catch (BonesException) {
            return false;
        }

        return $result['metaValue'];

    }

    /**
     * Update tenant meta.
     *
     * @param string $scoped_id
     * @param string $id
     * @param array $attrs
     * @param bool $allow_protected
     * @return void
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function update(string $scoped_id, string $id, array $attrs, bool $allow_protected = false): void
    {

        if (empty($attrs)) { // Nothing to update
            return;
        }

        // Exists

        try {
            $pre_update = $this->get($scoped_id, $id, [], $allow_protected);
        } catch (NotFoundException) {

            $msg = 'Unable to update tenant meta';
            $reason = 'Tenant and / or meta ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Allowed attributes

        // Cannot update id as it is used as the ID of this resource

        if (!empty(Arr::except($attrs, Arr::except($this->getAllowedAttrs(), 'id')))) {

            $msg = 'Unable to update tenant meta';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to update tenant meta';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Protected

        if ($allow_protected === false && str_starts_with($id, '00-')) {

            $msg = 'Unable to update tenant meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Update

        $placeholders = [];

        /** @noinspection SqlWithoutWhere */
        $query = "UPDATE api_tenant_meta SET" . " ";

        foreach ($attrs as $k => $v) {
            $placeholders[] = $v;
            $query .= $k . ' = ?, ';
        }

        $query = rtrim($query, ', ');

        $query .= "WHERE id = ? and tenantId = UUID_TO_BIN(?, 1)";

        array_push($placeholders, $id, $scoped_id);

        $this->db->query($query, $placeholders);

        // Log

        $pre_update = Arr::only($pre_update, $this->getAllowedAttrs());
        $post_update = Arr::only(array_merge($pre_update, $attrs), $this->getAllowedAttrs());
        $cols_updated = array_keys(Arr::only($attrs, $this->getAllowedAttrs()));

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.audit.actions'))) {

            $context = [
                'action' => 'api.tenant.meta.update',
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ];

            if (App::getConfig('api.log.audit.include_resource')) {
                $context['resource'] = $post_update;
            }

            $this->auditLogChannel->info('Tenant meta updated', $context);

        }

        // Event

        $this->events->doEvent('api.tenant.meta.update', $scoped_id, $id, $pre_update, $post_update, $cols_updated);

    }

    /**
     * Delete tenant meta.
     *
     * @param string $scoped_id
     * @param string $id
     * @param bool $allow_protected
     * @return void
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function delete(string $scoped_id, string $id, bool $allow_protected = false): void
    {

        // Exists

        try {
            $resource = $this->get($scoped_id, $id, [], $allow_protected);
        } catch (NotFoundException) {

            $msg = 'Unable to delete tenant meta';
            $reason = 'Tenant and / or meta ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Protected

        if ($allow_protected === false && str_starts_with($id, '00-')) {

            $msg = 'Unable to delete tenant meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Delete

        $this->db->query("DELETE FROM api_tenant_meta WHERE id = :id AND tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'id' => $id,
            'tenant_id' => $scoped_id
        ]);

        // Log

        if (in_array(Api::ACTION_DELETE, App::getConfig('api.log.audit.actions'))) {

            $context = [
                'action' => 'api.tenant.meta.delete',
                'tenant_id' => $scoped_id,
                'meta_id' => $id
            ];

            if (App::getConfig('api.log.audit.include_resource')) {
                $context['resource'] = $resource;
            }

            $this->auditLogChannel->info('Tenant meta deleted', $context);

        }

        // Event

        $this->events->doEvent('api.tenant.meta.delete', $scoped_id, $id, $resource);

    }

}