<?php

namespace Bayfront\Bones\Services\Api\Models\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Abstracts\Models\ApiModel;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\InternalServerErrorException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Interfaces\ResourceInterface;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\PDO\Db;
use Bayfront\Validator\Validate;
use Bayfront\Validator\ValidationException;
use Exception;
use Monolog\Logger;

class UsersModel extends ApiModel implements ResourceInterface
{

    protected FilterService $filters;

    public function __construct(EventService $events, Db $db, Logger $log, FilterService $filters)
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

        if (Validate::uuid($id)) {
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
     */
    public function verifyNewUserVerification(string $user_id, string $id): bool
    {

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
     * @throws InternalServerErrorException
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

        // Validate meta

        if (isset($attrs['meta'])) {

            try {

                Validate::as($attrs['meta'], App::getConfig('api.required_meta.users'), true);

            } catch (ValidationException) {

                $msg = 'Unable to create user';
                $reason = 'Missing or invalid meta attribute(s)';

                $this->log->notice($msg, [
                    'reason' => $reason
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

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

        try {

            Validate::as($attrs, $this->getAttrsRules());

        } catch (ValidationException) {

            $msg = 'Unable to create user';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

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

        // Email

        $attrs['email'] = strtolower($attrs['email']);

        if ($this->emailExists($attrs['email'])) {

            $msg = 'Unable to create user';
            $reason = 'Email (' . $attrs['email'] . ') already exists';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new ConflictException($msg . ': ' . $reason);

        }

        // Meta

        if (isset($attrs['meta'])) { // Remove null and json_encode
            $attrs['meta'] = $this->encodeMeta($attrs['meta']);
        }

        // Enabled

        if (isset($attrs['enabled'])) { // Cast to integer
            $attrs['enabled'] = (int)$attrs['enabled'];
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

            throw new InternalServerErrorException($msg . ': ' . $reason);

        }

        $attrs['password'] = $this->hashPassword($attrs['password'], $attrs['salt']);

        // ID

        $uuid = $this->createUUID();

        $attrs['id'] = $uuid['bin'];

        // Create

        $this->db->insert('api_users', $attrs);

        if ($include_verification) {
            $this->createNewUserVerification($uuid['str']);
        }

        if (in_array(Api::ACTION_CREATE, App::getConfig('api.log_actions'))) {

            $this->log->info('User created', [
                'user_id' => $uuid['str']
            ]);

        }

        $attrs['password'] = '****'; // Hide password from event

        $this->events->doEvent('api.user.create', $uuid['str'], Arr::except($attrs, [
            'id',
            'salt'
        ]));

        return $uuid['str'];

    }

    /**
     * Get user collection.
     *
     * @param array $args
     * @return array
     * @throws BadRequestException
     * @throws InternalServerErrorException
     */
    public function getCollection(array $args = []): array
    {

        if (empty($args['select'])) {
            $args['select'][] = '*';
        } else {
            $args['select'] = array_merge($args['select'], ['id']); // Force return ID
        }

        try {

            $results = $this->queryCollection('api_users', $args, $this->getSelectableCols(), 'id', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException|InternalServerErrorException $e) {

            $msg = 'Unable to get user collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage()
            ]);

            throw $e;

        }

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('User read', [
                'user_id' => Arr::pluck($results['data'], 'id')
            ]);

        }

        $this->events->doEvent('api.user.read', Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Get user.
     *
     * @param string $id
     * @param array $cols
     * @param bool $include_verification
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     */
    public function get(string $id, array $cols = ['*'], bool $include_verification = false): array
    {

        $cols = array_merge($cols, ['id']); // Force return ID

        if (!Validate::uuid($id)) {

            $this->log->notice('Unable to get user', [
                'reason' => 'Invalid user ID'
            ]);

            throw new NotFoundException('Unable to get user: Invalid user ID');

        }

        $result = $this->db->row("SELECT BIN_TO_UUID(id, 1) as id, email, meta, enabled, createdAt, updatedAt FROM api_users WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

        try {

            $result = $this->filterResult($result, $cols, $this->getSelectableCols(), $this->getJsonCols());

        } catch (BadRequestException|NotFoundException $e) {

            $msg = 'Unable to get user';

            $this->log->notice($msg, [
                'reason' => $e->getMessage()
            ]);

            throw $e;

        }

        if ($include_verification) {
            $result['verificationId'] = $this->getNewUserVerification($id);
        }

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('User read', [
                'user_id' => [$result['id']]
            ]);

        }

        $this->events->doEvent('api.user.read', [$result['id']]);

        return $result;

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
     */
    public function update(string $id, array $attrs): void
    {

        if (empty($attrs)) { // Nothing to update
            return;
        }

        if (!Validate::uuid($id)) {

            $this->log->notice('Unable to update user', [
                'reason' => 'Invalid user ID'
            ]);

            throw new NotFoundException('Unable to update user: Invalid user ID');

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to update user';
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

            $msg = 'Unable to update user';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // ID exists?

        $existing = $this->db->single("SELECT id, salt, meta from api_users WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

        if (!$existing) {

            $msg = 'Unable to update user';
            $reason = 'Does not exist';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Password

        if (isset($attrs['password'])) {

            if ($this->filters->doFilter('api.user.password', $attrs['password']) == '') {

                $msg = 'Unable to update user';
                $reason = 'Password does not meet the minimum requirements';

                $this->log->notice($msg, [
                    'reason' => $reason
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

            $attrs['password'] = $this->hashPassword($attrs['password'], $existing['salt']);

        }

        // Email

        if (isset($attrs['email'])) {

            $attrs['email'] = strtolower($attrs['email']);

            if ($this->emailExists($attrs['email'], $id)) {

                $msg = 'Unable to update user';
                $reason = 'Email ' . $attrs['email'] . ' already exists';

                $this->log->notice($msg, [
                    'reason' => $reason
                ]);

                throw new ConflictException($msg . ': ' . $reason);

            }

        }

        // Meta

        if (isset($attrs['meta'])) {

            if ($existing['meta']) {
                $attrs['meta'] = array_merge(json_decode($existing['meta'], true), $attrs['meta']);
            } else {
                $attrs['meta'] = json_decode($existing['meta'], true);
            }

            // Validate meta

            try {

                Validate::as($attrs['meta'], App::getConfig('api.required_meta.users'), true);

            } catch (ValidationException) {

                $msg = 'Unable to update user';
                $reason = 'Missing or invalid meta attribute(s)';

                $this->log->notice($msg, [
                    'reason' => $reason
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

            $attrs['meta'] = $this->encodeMeta($attrs['meta']);

        }

        // Enabled

        if (isset($attrs['enabled'])) { // Cast to integer
            $attrs['enabled'] = (int)$attrs['enabled'];
        }

        // Update

        $this->db->update('api_users', $attrs, [
            'id' => 'UUID_TO_BIN(' . $existing['id'] . ', 1)'
        ]);

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log_actions'))) {

            $this->log->info('User updated', [
                'user_id' => $id
            ]);

        }

        if (isset($attrs['password'])) { // Hide password from event
            $attrs['password'] = '****';
        }

        $this->events->doEvent('api.user.update', $id, $attrs);

    }

    /**
     * Delete user.
     *
     * @param string $id
     * @return bool
     * @throws NotFoundException
     */
    public function delete(string $id): bool
    {

        if (!Validate::uuid($id)) {

            $this->log->notice('Unable to delete user', [
                'reason' => 'Invalid user ID'
            ]);

            throw new NotFoundException('Unable to delete user: Invalid user ID');

        }

        $deleted = $this->db->delete('api_users', [
            'id' => 'UUID_TO_BIN(' . $id . ', 1)'
        ]);

        if ($deleted) {

            if (in_array(Api::ACTION_DELETE, App::getConfig('api.log_actions'))) {

                $this->log->info('User deleted', [
                    'user_id' => $id
                ]);

            }

            $this->events->doEvent('api.user.delete', $id);

        }

        return $deleted;

    }

}