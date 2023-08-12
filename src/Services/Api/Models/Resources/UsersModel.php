<?php

namespace Bayfront\Bones\Services\Api\Models\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Interfaces\ResourceInterface;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\MultiLogger\Log;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\Validator\Validate;
use Exception;

class UsersModel extends ApiModel implements ResourceInterface
{

    protected FilterService $filters;

    public function __construct(EventService $events, Db $db, Log $log, FilterService $filters)
    {
        $this->filters = $filters;

        parent::__construct($events, $db, $log);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredAttrs(): array
    {
        return [
            'email',
            'password'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAllowedAttrs(): array
    {
        return [
            'email',
            'password',
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
            'email' => 'email',
            'password' => 'string',
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
            'email' => 'email',
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
     * Get user count.
     *
     * @inheritDoc
     */
    public function getCount(): int
    {
        return $this->db->single("SELECT COUNT(*) FROM api_users");
    }

    /**
     * Does user ID exist?
     *
     * @inheritDoc
     */
    public function idExists(string $id): bool
    {

        if (!Validate::uuid($id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_users WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

    }

    /**
     * Does user exist with email?
     *
     * @param string $email
     * @param string $exclude_id
     * @return bool
     */
    public function emailExists(string $email, string $exclude_id = ''): bool
    {

        $email = strtolower($email);

        if ($exclude_id == '') {

            return (bool)$this->db->single("SELECT 1 FROM api_users WHERE email = :email", [
                'email' => $email
            ]);

        }

        if (!Validate::uuid($exclude_id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_users WHERE email = :email AND id != UUID_TO_BIN(:id, 1)", [
            'email' => $email,
            'id' => $exclude_id
        ]);

    }

    /**
     * Is user enabled?
     *
     * @param string $id
     * @return bool
     */
    public function isEnabled(string $id): bool
    {

        if (!Validate::uuid($id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT enabled FROM api_users WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

    }

    /**
     * Get all owned tenant ID's.
     *
     * @param string $id
     * @return array
     */
    public function getOwnedTenantIds(string $id): array
    {

        if (!Validate::uuid($id)) {
            return [];
        }

        return Arr::pluck($this->db->select("SELECT BIN_TO_UUID(id, 1) as id FROM api_tenants WHERE owner = UUID_TO_BIN(:user_id, 1)", [
            'user_id' => $id
        ]), 'id');

    }

    /**
     * Create new user verification meta.
     *
     * @param string $user_id
     * @return string
     * @throws UnexpectedApiException
     */
    protected function createNewUserVerification(string $user_id): string
    {

        try {
            $id = App::createKey(8);
        } catch (Exception $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        $this->db->query("INSERT INTO api_user_meta (id, userId, metaValue) VALUES ('00-new-user-verification', UUID_TO_BIN(:user_id, 1), :id) 
                                ON DUPLICATE KEY UPDATE id=VALUES(id), userId=VALUES(userId), metaValue=VALUES(metaValue)", [
            'id' => $id,
            'user_id' => $user_id
        ]);

        $this->events->doEvent('api.user.verification.create', $user_id, $id);

        return $id;

    }

    /**
     * Get value of new user verification meta.
     *
     * @param string $user_id
     * @return string
     */
    protected function getNewUserVerification(string $user_id): string
    {

        return $this->db->single("SELECT metaValue FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1)", [
            'id' => '00-new-user-verification',
            'user_id' => $user_id
        ]);

    }

    /**
     * Verify new user verification meta and enable user if valid.
     *
     * @param string $user_id
     * @param string $id
     * @return bool
     * @throws BadRequestException
     * @throws ConflictException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function verifyNewUserVerification(string $user_id, string $id): bool
    {

        if (!Validate::uuid($user_id)) {
            return false;
        }

        $exists = $this->db->single("SELECT metaValue FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1) AND metaValue = :value", [
            'id' => '00-new-user-verification',
            'user_id' => $user_id,
            'value' => $id
        ]);

        if ($exists) {

            $this->db->query("DELETE FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1) AND metaValue = :value", [
                'id' => '00-new-user-verification',
                'user_id' => $user_id,
                'value' => $id
            ]);

            $this->update($user_id, [
                'enabled' => true
            ]);

            $this->events->doEvent('api.user.verification.success', $user_id);

            return true;

        }

        return false;

    }

    /**
     * Create user.
     *
     * @param array $attrs
     * @param bool $include_verification
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws UnexpectedApiException
     */
    public function create(array $attrs, bool $include_verification = false): string
    {

        // Required attributes

        if (Arr::isMissing($attrs, $this->getRequiredAttrs())) {

            $msg = 'Unable to create user';
            $reason = 'Missing required attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to create user';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to create user';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Validate meta

        if (!empty(App::getConfig('api.required_meta.users'))) {

            if (!Validate::as(Arr::get($attrs, 'meta', []), App::getConfig('api.required_meta.users'), true)) {

                $msg = 'Unable to create user';
                $reason = 'Missing or invalid meta attribute(s)';

                $this->log->notice($msg, [
                    'reason' => $reason
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

            $attrs['meta'] = $this->encodeMeta($attrs['meta']);

        }

        // Salt

        try {

            $attrs['salt'] = App::createKey(16);

        } catch (Exception) {

            $msg = 'Unable to create user';
            $reason = 'Error creating salt';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new UnexpectedApiException($msg . ': ' . $reason);

        }

        // Password

        if ($this->filters->doFilter('api.user.password', $attrs['password']) == '') {

            $msg = 'Unable to create user';
            $reason = 'Password does not meet the minimum requirements';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        $attrs['password'] = $this->hashPassword($attrs['password'], $attrs['salt']);

        // Check email exists

        $attrs['email'] = strtolower($attrs['email']);

        if ($this->emailExists($attrs['email'])) {

            $msg = 'Unable to create user';
            $reason = 'Email (' . $attrs['email'] . ') already exists';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new ConflictException($msg . ': ' . $reason);

        }

        // Enabled

        if (isset($attrs['enabled'])) { // Cast to integer
            $attrs['enabled'] = (int)$attrs['enabled'];
        }

        // Create

        $uuid = $this->createUUID();

        $attrs['id'] = $uuid['bin'];

        $this->db->insert('api_users', $attrs);

        if ($include_verification) {
            $this->createNewUserVerification($uuid['str']);
        }

        // Log

        if (in_array(Api::ACTION_CREATE, App::getConfig('api.log.actions'))) {

            $this->apiLogChannel->info('User created', [
                'user_id' => $uuid['str']
            ]);

        }

        // Event

        $attrs['password'] = '****'; // Hide password from event

        $this->events->doEvent('api.user.create', $uuid['str'], Arr::only($attrs, $this->getAllowedAttrs()));

        return $uuid['str'];

    }

    /**
     * Get user collection.
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

        $query = $this->startNewQuery()->table('api_users');

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'id', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get user collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage()
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

            $this->apiLogChannel->info('User read', [
                'user_id' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.user.read', Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Get user.
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

        // Exists

        if (!$this->idExists($id)) {

            $msg = 'Unable to get user';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery()->table('api_users');

        try {

            $query->where('id', 'eq', "UUID_TO_BIN('" . $id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $result = $this->querySingle($query, $cols, $this->getSelectableCols(), $this->getJsonCols());

        } catch (BadRequestException|NotFoundException $e) {

            $msg = 'Unable to get user';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'user_id' => $id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

            $this->apiLogChannel->info('User read', [
                'user_id' => [$result['id']]
            ]);

        }

        // Event

        $this->events->doEvent('api.user.read', [$result['id']]);

        return $result;

    }

    /**
     * Get entire user, including protected fields (password and salt).
     *
     * @param string $id
     * @param bool $skip_log (Skip logs and events - used when a user authenticates)
     * @return array
     * @throws NotFoundException
     */
    public function getEntire(string $id, bool $skip_log = false): array
    {

        // Exists

        if (!$this->idExists($id)) {

            $msg = 'Unable to get user';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $result = $this->db->row("SELECT BIN_TO_UUID(id, 1) as id, email, password, salt, meta, enabled, createdAt, updatedAt FROM api_users WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

        // json_decode

        foreach ($this->getJsonCols() as $col) {

            if ($result[$col]) { // May be NULL
                $result[$col] = json_decode($result[$col], true);
            }

        }

        if (!$skip_log) {

            // Log

            if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

                $this->apiLogChannel->info('User read', [
                    'user_id' => [$result['id']]
                ]);

            }

            // Event

            $this->events->doEvent('api.user.read', [$result['id']]);

        }

        return $result;

    }

    /**
     * Get entire user from email, including protected fields (password and salt).
     *
     * @param string $email
     * @return array
     * @throws NotFoundException
     */
    public function getEntireFromEmail(string $email): array
    {

        // Exists

        if (!$this->emailExists($email)) {

            $msg = 'Unable to get user';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'email' => $email
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $result = $this->db->row("SELECT BIN_TO_UUID(id, 1) as id, email, password, salt, meta, enabled, createdAt, updatedAt FROM api_users WHERE email = :email", [
            'email' => strtolower($email)
        ]);

        // json_decode

        foreach ($this->getJsonCols() as $col) {

            if ($result[$col]) { // May be NULL
                $result[$col] = json_decode($result[$col], true);
            }

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log.actions'))) {

            $this->apiLogChannel->info('User read', [
                'user_id' => [$result['id']]
            ]);

        }

        // Event

        $this->events->doEvent('api.user.read', [$result['id']]);

        return $result;

    }

    /**
     * Get user salt.
     *
     * @param string $id
     * @return string
     */
    public function getSalt(string $id): string
    {

        if (!$this->idExists($id)) {
            return '';
        }

        return $this->db->single("SELECT salt FROM api_users WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

    }

    /**
     * Update user.
     *
     * @param string $id
     * @param array $attrs
     * @return void
     * @throws BadRequestException
     * @throws ConflictException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function update(string $id, array $attrs): void
    {

        if (empty($attrs)) { // Nothing to update
            return;
        }

        // UUID

        if (!Validate::uuid($id)) {

            $msg = 'Unable to update user';
            $reason = 'Invalid user ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Exists

        try {
            $pre_update = $this->get($id);
        } catch (NotFoundException) {

            $msg = 'Unable to update user';
            $reason = 'Does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to update user';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to update user';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Validate meta

        if (isset($attrs['meta'])) {

            if ($pre_update['meta']) {
                $attrs['meta'] = array_merge($pre_update['meta'], $attrs['meta']);
            } else {
                $attrs['meta'] = $pre_update['meta'];
            }

            // Validate meta

            if (!Validate::as($attrs['meta'], App::getConfig('api.required_meta.users'), true)) {

                $msg = 'Unable to update user';
                $reason = 'Missing or invalid meta attribute(s)';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'user_id' => $id
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

            $attrs['meta'] = $this->encodeMeta($attrs['meta']);

        }

        // Password

        if (isset($attrs['password'])) {

            if ($this->filters->doFilter('api.user.password', $attrs['password']) == '') {

                $msg = 'Unable to update user';
                $reason = 'Password does not meet the minimum requirements';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'user_id' => $id
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

            $attrs['password'] = $this->hashPassword($attrs['password'], $pre_update['salt']);

        }

        // Check email exists

        if (isset($attrs['email'])) {

            $attrs['email'] = strtolower($attrs['email']);

            if ($this->emailExists($attrs['email'], $id)) {

                $msg = 'Unable to update user';
                $reason = 'Email (' . $attrs['email'] . ') already exists';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'user_id' => $id
                ]);

                throw new ConflictException($msg . ': ' . $reason);

            }

        }

        // Enabled

        if (isset($attrs['enabled'])) { // Cast to integer
            $attrs['enabled'] = (int)$attrs['enabled'];
        }

        // Update

        $this->db->update('api_users', $attrs, [
            'id' => $pre_update['id']
        ]);

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log.actions'))) {

            $this->apiLogChannel->info('User updated', [
                'user_id' => $id
            ]);

        }

        // Event

        if (isset($attrs['password'])) { // Hide password from event
            $attrs['password'] = '****';
        }

        if (isset($attrs['meta'])) {
            $attrs['meta'] = json_decode($attrs['meta'], true);
        }

        $pre_update = Arr::only($pre_update, $this->getAllowedAttrs());
        $post_update = Arr::only(array_merge($pre_update, $attrs), $this->getAllowedAttrs());
        $cols_updated = array_keys(Arr::only($attrs, $this->getAllowedAttrs()));

        $this->events->doEvent('api.user.update', $id, $pre_update, $post_update, $cols_updated);

    }

    /**
     * Delete user.
     *
     * @param string $id
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function delete(string $id): void
    {

        // Exists

        try {
            $resource = $this->get($id);
        } catch (NotFoundException) {

            $msg = 'Unable to delete user';
            $reason = 'User ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Delete

        $this->db->query("DELETE FROM api_users WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

        // Log

        if (in_array(Api::ACTION_DELETE, App::getConfig('api.log.actions'))) {

            $this->apiLogChannel->info('User deleted', [
                'user_id' => $id
            ]);

        }

        // Event

        $this->events->doEvent('api.user.delete', $id, $resource);

    }

}