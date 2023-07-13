<?php

namespace Bayfront\Bones\Services\Api\Models;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Resources\UserMetaModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\PDO\Db;
use Bayfront\Validator\Validate;
use Exception;
use Monolog\Logger;

class PasswordTokenModel extends ApiModel
{

    protected FilterService $filters;
    protected UsersModel $usersModel;
    protected UserMetaModel $userMetaModel;

    public function __construct(EventService $events, Db $db, Logger $log, FilterService $filters, UsersModel $usersModel, UserMetaModel $userMetaModel)
    {
        parent::__construct($events, $db, $log);

        $this->filters = $filters;
        $this->usersModel = $usersModel;
        $this->userMetaModel = $userMetaModel;
    }

    /**
     * Create and save password reset token for user.
     *
     * @param string $email
     * @return void
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function create(string $email): void
    {

        if (!Validate::email($email)) {

            $msg = 'Unable to create password reset token';
            $reason = 'Invalid email address';

            $this->log->notice($msg, [
                'reason' => $reason,
                'email' => $email
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        $user = $this->usersModel->getEntireFromEmail($email);

        try {

            $token = App::createKey(8);

        } catch (Exception $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        $this->userMetaModel->create($user['id'], [
            'id' => '00-password-token',
            'metaValue' => json_encode([
                'token' => $this->hashPassword($token, $user['salt']),
                'expiresAt' => time() + (App::getConfig('api.duration.password_token') * 60)
            ])
        ], true, true);

        // Event

        $user = Arr::only($user, array_keys($this->usersModel->getSelectableCols())); // Drop sensitive columns

        $this->events->doEvent('api.password.token.create', $user, $token);

    }


    /**
     * Does a valid token exist?.
     *
     * @param string $user_id
     * @param string $token_value
     * @return bool
     * @throws ForbiddenException
     * @throws NotFoundException
     */

    public function has(string $user_id, string $token_value): bool
    {

        try {
            $user = $this->usersModel->getEntire($user_id, true);
        } catch (NotFoundException) {
            return false;
        }

        // Attempt to fetch token

        $token = $this->userMetaModel->getValue($user_id, '00-password-token', true);

        if (!$token) {
            return false;
        }

        // Validate token format

        $token = json_decode($token, true);

        if (Arr::isMissing($token, [
            'token',
            'expiresAt'
        ])) {

            // Delete invalid token

            $this->userMetaModel->delete($user_id, '00-password-token', true);

            return false;

        }

        // Validate token time

        if ($token['expiresAt'] <= time()) {

            // Delete invalid token

            $this->userMetaModel->delete($user_id, '00-password-token', true);

            return false;

        }

        // Validate token value

        return $this->verifyPassword($token_value, $user['salt'], $token['token']);

    }

    /**
     * Update user password and delete token.
     *
     * @param string $user_id
     * @param string $token_value
     * @param string $password
     * @return void
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function updatePassword(string $user_id, string $token_value, string $password): void
    {

        if (!$this->has($user_id, $token_value)) {

            $msg = 'Unable to update password using token';
            $reason = 'Invalid token';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $user_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        $this->usersModel->update($user_id, [
            'password' => $password
        ]);

        $this->userMetaModel->delete($user_id, '00-password-token', true);

    }

}