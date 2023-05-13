<?php

namespace Bayfront\Bones\Services\Api\Models\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Abstracts\Models\ApiModel;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Interfaces\Models\ModelScopedResourceInterface;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\StringHelpers\Str;
use Bayfront\Validator\Validate;
use Bayfront\Validator\ValidationException;
use Monolog\Logger;

class UserMetaModel extends ApiModel implements ModelScopedResourceInterface
{

    protected UsersModel $usersModel;

    public function __construct(EventService $events, Db $db, Logger $log, UsersModel $usersModel)
    {
        parent::__construct($events, $db, $log);

        $this->usersModel = $usersModel;
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
            //'userId' => 'BIN_TO_UUID(userId, 1) as userId',
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
     * Get user meta count.
     *
     * @inheritDoc
     */
    public function getCount(string $scoped_id, bool $allow_protected = false): int
    {

        if (!Validate::uuid($scoped_id)) {
            return 0;
        }

        if ($allow_protected) {

            return $this->db->single("SELECT COUNT(*) FROM api_user_meta WHERE userId = UUID_TO_BIN(:user_id, 1)", [
                'user_id' => $scoped_id
            ]);

        } else {

            return $this->db->single("SELECT COUNT(*) FROM api_user_meta WHERE userId = UUID_TO_BIN(:user_id, 1) AND id NOT LIKE '00-%'", [
                'user_id' => $scoped_id
            ]);

        }

    }

    /**
     * Does user meta exist?
     *
     * @inheritDoc
     */
    public function idExists(string $scoped_id, string $id, bool $allow_protected = false): bool
    {

        if (!Validate::uuid($scoped_id)) {
            return false;
        }

        if ($allow_protected) {

            return (bool)$this->db->single("SELECT 1 FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1)", [
                'id' => $id,
                'user_id' => $scoped_id
            ]);

        } else {

            return (bool)$this->db->single("SELECT 1 FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1) AND id NOT LIKE '00-%'", [
                'id' => $id,
                'user_id' => $scoped_id
            ]);

        }

    }

    /**
     * Create user meta.
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

        // UUID

        if (!Validate::uuid($scoped_id)) {

            $msg = 'Unable to create user meta';
            $reason = 'Invalid user ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Scoped exists

        if (!$this->usersModel->idExists($scoped_id)) {

            $msg = 'Unable to create user meta';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Required attributes

        if (Arr::isMissing($attrs, $this->getRequiredAttrs())) {

            $msg = 'Unable to create user meta';
            $reason = 'Missing required attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to create user meta';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        try {

            Validate::as($attrs, $this->getAttrsRules());

        } catch (ValidationException) {

            $msg = 'Unable to create user meta';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Protected

        $attrs['id'] = Str::kebabCase($attrs['id'], true);

        if ($allow_protected === false && str_starts_with($attrs['id'], '00-')) {

            $msg = 'Unable to create user meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        if ($overwrite === false) {

            // Check exists

            if ($this->idExists($scoped_id, $attrs['id'])) {

                $msg = 'Unable to create user meta';
                $reason = 'ID (' . $attrs['id'] . ') already exists';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'user_id' => $scoped_id
                ]);

                throw new ConflictException($msg . ': ' . $reason);

            }

            // Create

            $this->db->query("INSERT INTO api_user_meta SET id = :id, userId = UUID_TO_BIN(:user_id, 1), metaValue = :value", [
                'id' => $attrs['id'],
                'user_id' => $scoped_id,
                'value' => $attrs['metaValue']
            ]);

        } else {

            // Create

            $this->db->query("INSERT INTO api_user_meta SET id = :id, userId = UUID_TO_BIN(:user_id, 1), metaValue = :value
                                        ON DUPLICATE KEY UPDATE id=VALUES(id), userId=VALUES(userId), metaValue=VALUES(metaValue)", [
                'id' => $attrs['id'],
                'user_id' => $scoped_id,
                'value' => $attrs['metaValue']
            ]);

        }

        // Log

        if (in_array(Api::ACTION_CREATE, App::getConfig('api.log_actions'))) {

            $this->log->info('User meta created', [
                'user_id' => $scoped_id,
                'meta_id' => $attrs['id']
            ]);

        }

        // Event

        $this->events->doEvent('api.user.meta.create', $scoped_id, $attrs['id'], Arr::only($attrs, array_keys($this->getSelectableCols())));

        return $attrs['id'];

    }

    /**
     * Get user meta collection.
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

        // UUID

        if (!Validate::uuid($scoped_id)) {

            $msg = 'Unable to get user meta collection';
            $reason = 'Invalid user ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Scoped exists

        if (!$this->usersModel->idExists($scoped_id)) {

            $msg = 'Unable to get user meta collection';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        if (empty($args['select'])) {
            $args['select'][] = '*';
        } else {
            $args['select'] = array_merge($args['select'], ['id']); // Force return ID
        }

        // Query

        try {

            $query = $this->startNewQuery()->table('api_user_meta')
                ->where('userId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)");

            if ($allow_protected === false) {

                $query->where('id', '!sw', '00-');

            }

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'id', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get user meta collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'user_id' => $scoped_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('User meta read', [
                'user_id' => $scoped_id,
                'meta_id' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.user.meta.read', $scoped_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Get user meta.
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

        $id = Str::kebabCase($id, true);

        // UUID

        if (!Validate::uuid($scoped_id)) {

            $msg = 'Unable to get user meta';
            $reason = 'Invalid user ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Protected

        if ($allow_protected === false && str_starts_with($id, '00-')) {

            $msg = 'Unable to get user meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery()->table('api_user_meta');

        try {

            $query->where('id', 'eq', $id)
                ->where('userId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $result = $this->querySingle($query, $cols, $this->getSelectableCols(), $this->getJsonCols());

        } catch (BadRequestException|NotFoundException $e) {

            $msg = 'Unable to get user meta';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('User meta read', [
                'user_id' => $scoped_id,
                'meta_id' => [$result['id']]
            ]);

        }

        // Event

        $this->events->doEvent('api.user.meta.read', $scoped_id, [$result['id']]);

        return $result;

    }

    /**
     * Return value of single user meta, or false if not existing.
     *
     * @param string $scoped_id
     * @param string $id
     * @param bool $allow_protected
     * @return mixed
     */
    public function getValue(string $scoped_id, string $id, bool $allow_protected = false): mixed
    {

        $id = Str::kebabCase($id, true);

        // UUID

        if (!Validate::uuid($scoped_id)) {
            return false;
        }

        // Protected

        if ($allow_protected === false && str_starts_with($id, '00-')) {
            return false;
        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('User meta read', [
                'user_id' => $scoped_id,
                'meta_id' => [$id]
            ]);

        }

        // Event

        $this->events->doEvent('api.user.meta.read', $scoped_id, [$id]);

        return $this->db->single("SELECT metaValue FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1)", [
            'id' => $id,
            'user_id' => $scoped_id
        ]);

    }

    /**
     * Update user meta.
     *
     * @param string $scoped_id
     * @param string $id
     * @param array $attrs
     * @param bool $allow_protected
     * @return void
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function update(string $scoped_id, string $id, array $attrs, bool $allow_protected = false): void
    {

        if (empty($attrs)) { // Nothing to update
            return;
        }

        $id = Str::kebabCase($id, true);

        // UUID

        if (!Validate::uuid($scoped_id)) {

            $msg = 'Unable to update user meta';
            $reason = 'Invalid user ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Scoped exists

        if (!$this->usersModel->idExists($scoped_id)) {

            $msg = 'Unable to update user meta';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to update user meta';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        try {

            Validate::as($attrs, $this->getAttrsRules());

        } catch (ValidationException) {

            $msg = 'Unable to update user meta';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Protected

        if ($allow_protected === false && str_starts_with($id, '00-')) {

            $msg = 'Unable to update user meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Check exists

        if (!$this->idExists($scoped_id, $id)) {

            $msg = 'Unable to update user meta';
            $reason = 'Does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Update

        $placeholders = [];

        /** @noinspection SqlWithoutWhere */
        $query = "UPDATE api_user_meta SET" . " ";

        foreach ($attrs as $k => $v) {
            $placeholders[] = $v;
            $query .= $k . ' = ? ';
        }

        $query .= "WHERE id = ? and userId = UUID_TO_BIN(?, 1)";

        array_push($placeholders, $id, $scoped_id);

        $this->db->query($query, $placeholders);

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log_actions'))) {

            $this->log->info('User meta updated', [
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

        }

        // Event

        $this->events->doEvent('api.user.meta.update', $scoped_id, $id, Arr::only($attrs, array_keys($this->getSelectableCols())));

    }

    /**
     * Delete user meta.
     *
     * @param string $scoped_id
     * @param string $id
     * @param bool $allow_protected
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function delete(string $scoped_id, string $id, bool $allow_protected = false): void
    {

        $id = Str::kebabCase($id, true);

        // UUID

        if (!Validate::uuid($scoped_id)) {

            $msg = 'Unable to delete user meta';
            $reason = 'Invalid user ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Protected

        if ($allow_protected === false && str_starts_with($id, '00-')) {

            $msg = 'Unable to delete user meta';
            $reason = 'Meta ID is protected';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Delete

        $this->db->query("DELETE FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1)", [
            'id' => $id,
            'user_id' => $scoped_id
        ]);

        if ($this->db->rowCount() > 0) {

            // Log

            if (in_array(Api::ACTION_DELETE, App::getConfig('api.log_actions'))) {

                $this->log->info('User meta deleted', [
                    'user_id' => $scoped_id,
                    'meta_id' => $id
                ]);

            }

            // Event

            $this->events->doEvent('api.user.meta.delete', $scoped_id, $id);

            return;

        }

        $msg = 'Unable to delete user meta';
        $reason = 'User and / or meta does not exist';

        $this->log->notice($msg, [
            'reason' => $reason,
            'user_id' => $scoped_id,
            'meta_id' => $id
        ]);

        throw new NotFoundException($msg . ': ' . $reason);

    }

}