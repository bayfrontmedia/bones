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
use Bayfront\Bones\Services\Api\Models\Relationships\TenantUsersModel;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\MultiLogger\MultiLogger;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\StringHelpers\Str;
use Bayfront\Validator\Validate;

/**
 * ScopedResourceInterface with extra level (user + tenant)
 */
class TenantUserMetaModel extends ApiModel
{

    protected TenantUsersModel $tenantUsersModel;

    public function __construct(EventService $events, Db $db, MultiLogger $multiLogger, TenantUsersModel $tenantUsersModel)
    {
        $this->tenantUsersModel = $tenantUsersModel;

        parent::__construct($events, $db, $multiLogger);
    }

    /**
     * @return array
     */
    public function getRequiredAttrs(): array
    {
        return [
            'id',
            'metaValue'
        ];
    }

    /**
     * @return array
     */
    public function getAllowedAttrs(): array
    {
        return [
            'id',
            'metaValue'
        ];
    }

    /**
     * @return array
     */
    public function getAttrsRules(): array
    {
        return [
            'id' => 'string'
        ];
    }

    /**
     * @return array
     */
    public function getSelectableCols(): array
    {
        return [
            'id' => 'id',
            //'tenantId' => 'BIN_TO_UUID(tenantId, 1) as tenantId',
            //'userId' => 'BIN_TO_UUID(userId, 1) as userId',
            'metaValue' => 'metaValue',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt'
        ];
    }

    /**
     * @return array
     */
    public function getJsonCols(): array
    {
        return [];
    }

    /**
     * Get tenant user meta count.
     *
     * @param string $scoped_id
     * @param string $user_id
     * @param bool $allow_protected
     * @return int
     */
    public function getCount(string $scoped_id, string $user_id, bool $allow_protected = false): int
    {

        if (!$this->tenantUsersModel->has($scoped_id, $user_id)) {
            return 0;
        }

        if ($allow_protected) {

            return $this->db->single("SELECT COUNT(*) FROM api_tenant_user_meta WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)", [
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

        } else {

            return $this->db->single("SELECT COUNT(*) FROM api_tenant_user_meta WHERE tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1) AND id NOT LIKE '00-%'", [
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

        }

    }

    /**
     * Does tenant user meta exist?
     *
     * @param string $scoped_id
     * @param string $user_id
     * @param string $id
     * @param bool $allow_protected
     * @return bool
     */
    public function idExists(string $scoped_id, string $user_id, string $id, bool $allow_protected = false): bool
    {

        if (!Validate::uuid($scoped_id) || !Validate::uuid($user_id)) {
            return false;
        }

        if ($allow_protected) {

            return (bool)$this->db->single("SELECT 1 FROM api_tenant_user_meta WHERE id = :id AND tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)", [
                'id' => $id,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

        } else {

            return (bool)$this->db->single("SELECT 1 FROM api_tenant_user_meta WHERE id = :id AND tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1) AND id NOT LIKE '00-%'", [
                'id' => $id,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

        }

    }

    /**
     * Create tenant user meta.
     *
     * @param string $scoped_id
     * @param string $user_id
     * @param array $attrs
     * @param bool $allow_protected
     * @param bool $overwrite
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function create(string $scoped_id, string $user_id, array $attrs, bool $allow_protected = false, bool $overwrite = false): string
    {

        // Scoped exists

        if (!$this->tenantUsersModel->has($scoped_id, $user_id)) {

            $msg = 'Unable to create tenant user meta';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Required attributes

        if (Arr::isMissing($attrs, $this->getRequiredAttrs())) {

            $msg = 'Unable to create tenant user meta';
            $reason = 'Missing required attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to create tenant user meta';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to create tenant user meta';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Protected

        $attrs['id'] = Str::kebabCase($attrs['id'], true);

        if ($allow_protected === false && str_starts_with($attrs['id'], '00-')) {

            $msg = 'Unable to create tenant user meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        if ($overwrite === false) {

            // Check exists

            if ($this->idExists($scoped_id, $user_id, $attrs['id'])) {

                $msg = 'Unable to create tenant user meta';
                $reason = 'ID (' . $attrs['id'] . ') already exists';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'tenant_id' => $scoped_id,
                    'user_id' => $user_id
                ]);

                throw new ConflictException($msg . ': ' . $reason);

            }

            // Create

            $this->db->query("INSERT INTO api_tenant_user_meta SET id = :id, tenantId = UUID_TO_BIN(:tenant_id, 1), userId = UUID_TO_BIN(:user_id, 1), metaValue = :value", [
                'id' => $attrs['id'],
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'value' => $attrs['metaValue']
            ]);

        } else {

            // Create

            $this->db->query("INSERT INTO api_tenant_user_meta SET id = :id, tenantId = UUID_TO_BIN(:tenant_id, 1), userId = UUID_TO_BIN(:user_id, 1), metaValue = :value
                                        ON DUPLICATE KEY UPDATE id=VALUES(id), tenantId=VALUES(tenantId), userId=VALUES(userId), metaValue=VALUES(metaValue)", [
                'id' => $attrs['id'],
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'value' => $attrs['metaValue']
            ]);

        }

        // Log

        if (in_array(Api::ACTION_CREATE, App::getConfig('api.log.actions'))) {

            $this->log->info('Tenant user meta created', [
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $attrs['id']
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.meta.create', $scoped_id, $user_id, $attrs['id'], Arr::only($attrs, array_keys($this->getSelectableCols())));

        return $attrs['id'];

    }

    /**
     * Get tenant user meta collection.
     *
     * @param string $scoped_id
     * @param string $user_id
     * @param array $args
     * @param bool $allow_protected
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function getCollection(string $scoped_id, string $user_id, array $args = [], bool $allow_protected = false): array
    {

        if (empty($args['select'])) {
            $args['select'][] = '*';
        } else {
            $args['select'] = array_merge($args['select'], ['id']); // Force return ID
        }

        // Scoped exists

        if (!$this->tenantUsersModel->has($scoped_id, $user_id)) {

            $msg = 'Unable to get tenant user meta collection';
            $reason = 'Tenant and / or user does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        try {

            $query = $this->startNewQuery()->table('api_tenant_user_meta')
                ->where('tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)")
                ->where('userId', 'eq', "UUID_TO_BIN('" . $user_id . "', 1)");

            if ($allow_protected === false) {

                $query->where('id', '!sw', '00-');

            }

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'id', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant user meta collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id,
                'user_id' => $user_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

            $this->log->info('Tenant user meta read', [
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.meta.read', $scoped_id, $user_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Get tenant user meta.
     *
     * @param string $scoped_id
     * @param string $user_id
     * @param string $id
     * @param array $cols
     * @param bool $allow_protected
     * @return array
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function get(string $scoped_id, string $user_id, string $id, array $cols = [], bool $allow_protected = false): array
    {

        if (empty($cols)) {
            $cols[] = '*';
        } else {
            $cols = array_merge($cols, ['id']); // Force return ID
        }

        // Exists

        if (!$this->idExists($scoped_id, $user_id, $id, $allow_protected)) {

            $msg = 'Unable to get tenant user meta';
            $reason = 'Tenant, user and / or meta ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Protected

        if ($allow_protected === false && str_starts_with($id, '00-')) {

            $msg = 'Unable to get tenant user meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery()->table('api_tenant_user_meta');

        try {

            $query->where('id', 'eq', $id)
                ->where('tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)")
                ->where('userId', 'eq', "UUID_TO_BIN('" . $user_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $result = $this->querySingle($query, $cols, $this->getSelectableCols(), $this->getJsonCols());

        } catch (BadRequestException|NotFoundException $e) {

            $msg = 'Unable to get tenant user meta';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

            $this->log->info('Tenant user meta read', [
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => [$result['id']]
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.meta.read', $scoped_id, $user_id, [$result['id']]);

        return $result;

    }

    /**
     * Get value of single tenant user meta or false if not existing.
     *
     * @param string $scoped_id
     * @param string $user_id
     * @param string $id
     * @param bool $allow_protected
     * @return mixed
     */
    public function getValue(string $scoped_id, string $user_id, string $id, bool $allow_protected = false): mixed
    {

        try {

            $result = $this->get($scoped_id, $user_id, $id, ['metaValue'], $allow_protected);

        } catch (BonesException) {
            return false;
        }

        return $result['metaValue'];

    }

    /**
     * Update tenant user meta.
     *
     * @param string $scoped_id
     * @param string $user_id
     * @param string $id
     * @param array $attrs
     * @param bool $allow_protected
     * @return void
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function update(string $scoped_id, string $user_id, string $id, array $attrs, bool $allow_protected = false): void
    {

        if (empty($attrs)) { // Nothing to update
            return;
        }

        // Exists

        if (!$this->idExists($scoped_id, $user_id, $id, $allow_protected)) {

            $msg = 'Unable to update tenant user meta';
            $reason = 'Tenant, user and / or meta ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Allowed attributes

        // Cannot update id as it is used as the ID of this resource

        if (!empty(Arr::except($attrs, Arr::except($this->getAllowedAttrs(), 'id')))) {

            $msg = 'Unable to update tenant user meta';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to update tenant user meta';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Protected

        if ($allow_protected === false && str_starts_with($id, '00-')) {

            $msg = 'Unable to update tenant user meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Update

        $placeholders = [];

        /** @noinspection SqlWithoutWhere */
        $query = "UPDATE api_tenant_user_meta SET" . " ";

        foreach ($attrs as $k => $v) {
            $placeholders[] = $v;
            $query .= $k . ' = ?, ';
        }

        $query = rtrim($query, ', ');

        $query .= "WHERE id = ? and tenantId = UUID_TO_BIN(?, 1) AND userId = UUID_TO_BIN(?, 1)";

        array_push($placeholders, $id, $scoped_id, $user_id);

        $this->db->query($query, $placeholders);

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->log->info('Tenant user meta updated', [
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.meta.update', $scoped_id, $user_id, $id, Arr::only($attrs, array_keys($this->getSelectableCols())));

    }

    /**
     * Delete tenant user meta.
     *
     * @param string $scoped_id
     * @param string $user_id
     * @param string $id
     * @param bool $allow_protected
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function delete(string $scoped_id, string $user_id, string $id, bool $allow_protected = false): void
    {

        if (!$this->idExists($scoped_id, $user_id, $id)) {

            $msg = 'Unable to delete tenant user meta';
            $reason = 'Tenant, user and / or meta ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Protected

        if ($allow_protected === false && str_starts_with($id, '00-')) {

            $msg = 'Unable to delete tenant user meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Delete

        $this->db->query("DELETE FROM api_tenant_user_meta WHERE id = :id AND tenantId = UUID_TO_BIN(:tenant_id, 1) AND userId = UUID_TO_BIN(:user_id, 1)", [
            'id' => $id,
            'tenant_id' => $scoped_id,
            'user_id' => $user_id
        ]);

        // Log

        if (in_array(Api::ACTION_DELETE, App::getConfig('api.log.actions'))) {

            $this->log->info('Tenant user meta deleted', [
                'tenant_id' => $scoped_id,
                'user_id' => $user_id,
                'meta_id' => $id
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.user.meta.delete', $scoped_id, $user_id, $id);

    }

}