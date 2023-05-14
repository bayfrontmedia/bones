<?php

namespace Bayfront\Bones\Services\Api\Models\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Abstracts\Models\ApiModel;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Interfaces\ScopedResourceInterface;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\Validator\Validate;
use Exception;
use Monolog\Logger;

class UserKeysModel extends ApiModel implements ScopedResourceInterface
{

    protected UsersModel $usersModel;

    public function __construct(EventService $events, Db $db, Logger $log, UsersModel $usersModel)
    {
        $this->usersModel = $usersModel;

        parent::__construct($events, $db, $log);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredAttrs(): array
    {
        return [
            'description',
            'expiresAt'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAllowedAttrs(): array
    {
        return [
            'description',
            'allowedDomains',
            'allowedIps',
            'expiresAt'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAttrsRules(): array
    {
        return [
            'description' => 'string',
            'allowedDomains' => 'array',
            'allowedIps' => 'array'
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
            'description' => 'description',
            'allowedDomains' => 'allowedDomains',
            'allowedIps' => 'allowedIps',
            'expiresAt' => 'expiresAt',
            'lastUsed' => 'lastUsed',
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
     * Get user keys count.
     *
     * @inheritDoc
     */
    public function getCount(string $scoped_id): int
    {

        if (!Validate::uuid($scoped_id)) {
            return 0;
        }

        return $this->db->single("SELECT COUNT(*) FROM api_user_keys WHERE userId = UUID_TO_BIN(:user_id, 1)", [
            'user_id' => $scoped_id
        ]);

    }

    /**
     * Does user key exist?
     *
     * @inheritDoc
     */
    public function idExists(string $scoped_id, string $id): bool
    {

        if (!Validate::uuid($scoped_id) || !Validate::uuid($id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_user_keys WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1)", [
            'id' => $id,
            'user_id' => $scoped_id
        ]);

    }

    /**
     * Does key ID exist for any user?
     * Used when creating keys to ensure uniqueness.
     *
     * @param string $id
     * @return bool
     */
    private function globalIdExists(string $id): bool
    {

        return (bool)$this->db->single("SELECT 1 FROM api_user_keys WHERE id = :id", [
            'id' => $id
        ]);

    }

    /**
     * Is expiresAt time valid?
     *
     * @param string $expires_at
     * @return bool
     */
    private function isValidExpiration(string $expires_at): bool
    {

        $time = strtotime($expires_at);

        if (!Validate::date($expires_at)
            || $time <= time()
            || $time >= time() + (60 * 60 * 24 * App::getConfig('api.auth.max_key_duration'))) {
            return false;
        }

        return true;

    }

    /**
     * Create user key.
     * This returns the full key ID and is the only time it is recoverable.
     *
     * @param string $scoped_id
     * @param array $attrs
     * @return string
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function create(string $scoped_id, array $attrs): string
    {

        // UUID

        if (!Validate::uuid($scoped_id)) {

            $msg = 'Unable to create user key';
            $reason = 'Invalid user ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Scoped exists

        $salt = $this->usersModel->getSalt($scoped_id);

        if ($salt == '') {

            $msg = 'Unable to create user key';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Required attributes

        if (Arr::isMissing($attrs, $this->getRequiredAttrs())) {

            $msg = 'Unable to create user key';
            $reason = 'Missing required attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to create user key';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to create user key';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Create ID

        try {


            $id_full = App::createKey();
            $id_short = substr($id_full, 0, 7);

            while ($this->globalIdExists($id_short)) {

                $id_full = App::createKey();
                $id_short = substr($id_full, 0, 7);

            }

        } catch (Exception $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        if (isset($attrs['allowedDomains'])) {
            $attrs['allowedDomains'] = json_encode($attrs['allowedDomains']);
        }

        if (isset($attrs['allowedIps'])) {
            $attrs['allowedIps'] = json_encode($attrs['allowedIps']);
        }

        $attrs['id'] = $id_short;
        $attrs['userId'] = $this->UUIDtoBIN($scoped_id);
        $attrs['keyValue'] = $this->hashPassword($id_full, $salt);

        // Create

        $this->db->insert('api_user_keys', $attrs);

        // Log

        if (in_array(Api::ACTION_CREATE, App::getConfig('api.log_actions'))) {

            $this->log->info('User key created', [
                'user_id' => $scoped_id,
                'key_id' => $id_short
            ]);

        }

        // Event

        $this->events->doEvent('api.user.key.create', $scoped_id, $id_short, Arr::only($attrs, array_keys($this->getSelectableCols())));

        return $id_full;

    }

    /**
     * Get user key collection.
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

        // UUID

        if (!Validate::uuid($scoped_id)) {

            $msg = 'Unable to get user key collection';
            $reason = 'Invalid user ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Scoped exists

        if (!$this->usersModel->idExists($scoped_id)) {

            $msg = 'Unable to get user key collection';
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

            $query = $this->startNewQuery()->table('api_user_keys')
                ->where('userId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'createdAt', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get user key collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'user_id' => $scoped_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('User key read', [
                'user_id' => $scoped_id,
                'key_id' => Arr::pluck($results['data'], 'id')
            ]);

        }

        // Event

        $this->events->doEvent('api.user.key.read', $scoped_id, Arr::pluck($results['data'], 'id'));

        return $results;

    }

    /**
     * Get user key.
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

        // UUID

        if (!Validate::uuid($scoped_id)) {

            $msg = 'Unable to get user key';
            $reason = 'Invalid user ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'key_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery()->table('api_user_keys');

        try {

            $query->where('id', 'eq', $id)
                ->where('userId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $result = $this->querySingle($query, $cols, $this->getSelectableCols(), $this->getJsonCols());

        } catch (BadRequestException|NotFoundException $e) {

            $msg = 'Unable to get user key';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'user_id' => $scoped_id,
                'meta_id' => $id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('User key read', [
                'user_id' => $scoped_id,
                'key_id' => [$result['id']]
            ]);

        }

        // Event

        $this->events->doEvent('api.user.key.read', $scoped_id, [$result['id']]);

        return $result;

    }

    /**
     * Update user key.
     *
     * @param string $scoped_id
     * @param string $id
     * @param array $attrs
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     */
    public function update(string $scoped_id, string $id, array $attrs): void
    {

        if (empty($attrs)) { // Nothing to update
            return;
        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to update user key';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'key_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to update user key';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'key_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Check exists

        if (!$this->idExists($scoped_id, $id)) {

            $msg = 'Unable to update user key';
            $reason = 'Key does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'key_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Check expiresAt

        if (isset($attrs['expiresAt']) && !$this->isValidExpiration($attrs['expiresAt'])) {

            $msg = 'Unable to update user key';
            $reason = 'Invalid expiresAt time';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'key_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // json_encode

        if (isset($attrs['allowedDomains'])) {
            $attrs['allowedDomains'] = json_encode($attrs['allowedDomains']);
        }

        if (isset($attrs['allowedIps'])) {
            $attrs['allowedIps'] = json_encode($attrs['allowedIps']);
        }

        // Update

        $placeholders = [];

        /** @noinspection SqlWithoutWhere */
        $query = "UPDATE api_user_keys SET" . " ";

        foreach ($attrs as $k => $v) {
            $placeholders[] = $v;
            $query .= $k . ' = ? ';
        }

        /*
         * Needs manual updatedAt time
         * due to lastUsed being updated at each authentication.
         */

        $placeholders[] = $id;
        $query = rtrim($query) . ", updatedAt = NOW() WHERE id = ?";

        $this->db->query($query, $placeholders);

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log_actions'))) {

            $this->log->info('User key updated', [
                'user_id' => $scoped_id,
                'key_id' => $id
            ]);

        }

        // Event

        $this->events->doEvent('api.user.key.update', $scoped_id, $id, Arr::only($attrs, array_keys($this->getSelectableCols())));

    }

    /**
     * Delete user key.
     *
     * @param string $scoped_id
     * @param string $id
     * @return void
     * @throws NotFoundException
     */
    public function delete(string $scoped_id, string $id): void
    {

        // UUID

        if (!Validate::uuid($scoped_id)) {

            $msg = 'Unable to delete user key';
            $reason = 'Invalid user ID';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $scoped_id,
                'key_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Delete

        $this->db->query("DELETE FROM api_user_keys WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1)", [
            'id' => $id,
            'user_id' => $scoped_id
        ]);

        if ($this->db->rowCount() > 0) {

            // Log

            if (in_array(Api::ACTION_DELETE, App::getConfig('api.log_actions'))) {

                $this->log->info('User key deleted', [
                    'user_id' => $scoped_id,
                    'key_id' => $id
                ]);

            }

            // Event

            $this->events->doEvent('api.user.key.delete', $scoped_id, $id);

            return;

        }

        $msg = 'Unable to delete user key';
        $reason = 'User and / or key does not exist';

        $this->log->notice($msg, [
            'reason' => $reason,
            'user_id' => $scoped_id,
            'key_id' => $id
        ]);

        throw new NotFoundException($msg . ': ' . $reason);

    }

}