<?php

namespace Bayfront\Bones\Services\Api\Models;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Abstracts\Models\ApiModel;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\InternalServerErrorException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnauthorizedException;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\JWT\Jwt;
use Bayfront\JWT\TokenException;
use Bayfront\PDO\Db;
use Monolog\Logger;

class AuthModel extends ApiModel
{

    protected UsersModel $usersModel;

    public function __construct(EventService $events, Db $db, Logger $log, UsersModel $usersModel)
    {
        parent::__construct($events, $db, $log);

        $this->usersModel = $usersModel;
    }

    /**
     * Authenticate with email + password.
     * Returns user.
     *
     * @param string $email
     * @param string $password
     * @return array
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function authenticateWithPassword(string $email, string $password): array
    {

        try {

            $user = $this->usersModel->getEntireFromEmail($email);

        } catch (NotFoundException) {

            $msg = 'Unsuccessful authentication with password';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'email' => $email
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        if ($user['enabled'] !== 1) {

            $msg = 'Unsuccessful authentication with password';
            $reason = 'User disabled';

            $this->log->notice($msg, [
                'reason' => $reason,
                'email' => $email
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        if (!$this->verifyPassword($password, $user['salt'], $user['password'])) {

            $msg = 'Unsuccessful authentication with password';
            $reason = 'Incorrect password';

            $this->log->notice($msg, [
                'reason' => $reason,
                'email' => $email
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        if ($user['meta']) {
            $user['meta'] = json_decode($user['meta'], true);
        }

        $user = Arr::except($user, [
            'password',
            'salt'
        ]);

        $this->log->info('Successful authentication with password', [
            'email' => $email,
            'user_id' => $user['id'],
        ]);

        $this->events->doEvent('auth.success', $user, 'password');

        return $user;

    }

    /**
     * Authenticate with access + refresh tokens.
     * Returns user.
     *
     * @param string $access_token
     * @param string $refresh_token
     * @return array
     * @throws ForbiddenException
     * @throws InternalServerErrorException
     * @throws UnauthorizedException
     */
    public function authenticateWithRefreshToken(string $access_token, string $refresh_token): array
    {

        $jwt = new Jwt(App::getConfig('app.key'));

        try {

            /*
             * Validate the JWT has not been modified, even if it is expired.
             * All that is needed is the user ID
             */

            $token = $jwt->validateSignature($access_token)->decode($access_token, false);

        } catch (TokenException) { // Invalid JWT

            $msg = 'Unsuccessful authentication with token';
            $reason = 'Invalid access token';

            $this->log->notice($msg, [
                'reason' => $reason,
                'access_token' => $access_token
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // Attempt to fetch refresh token

        $existing_refresh_token = $this->db->single("SELECT metaValue FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1)", [
            'id' => '00-refresh-token',
            'user_id' => $token['payload']['sub']
        ]);

        if (!$existing_refresh_token) {

            $msg = 'Unsuccessful authentication with token';
            $reason = 'Refresh token does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'access_token' => $access_token
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // Validate refresh token format

        $existing_refresh_token = json_decode($existing_refresh_token, true);

        if (Arr::isMissing($existing_refresh_token, [
            'token',
            'expiresAt'
        ])) {

            // Delete invalid token

            $this->db->query("DELETE FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1)", [
                'id' => '00-refresh-token',
                'user_id' => $token['payload']['sub']
            ]);

            $msg = 'Unsuccessful authentication with token';
            $reason = 'Invalid refresh token format';

            $this->log->critical($msg, [
                'reason' => $reason,
                'access_token' => $access_token
            ]);

            throw new InternalServerErrorException($msg . ': ' . $reason);

        }

        // Validate refresh token time

        if ($existing_refresh_token['expiresAt'] <= time()) {

            // Delete invalid token

            $this->db->query("DELETE FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1)", [
                'id' => '00-refresh-token',
                'user_id' => $token['payload']['sub']
            ]);

            $msg = 'Unsuccessful authentication with token';
            $reason = 'Refresh token is expired';

            $this->log->notice($msg, [
                'reason' => $reason,
                'access_token' => $access_token
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // Validate refresh token value

        try {

            $user = $this->usersModel->getEntire($token['payload']['sub']);

        } catch (NotFoundException) {

            // Delete invalid token

            $this->db->query("DELETE FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1)", [
                'id' => '00-refresh-token',
                'user_id' => $token['payload']['sub']
            ]);

            $msg = 'Unsuccessful authentication with token';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'access_token' => $access_token
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // Validate password

        if (!$this->verifyPassword($refresh_token, $user['salt'], $existing_refresh_token['token'])) {

            // Do not delete token as the token itself is valid

            $msg = 'Unsuccessful authentication with token';
            $reason = 'Invalid credentials';

            $this->log->notice($msg, [
                'reason' => $reason,
                'access_token' => $access_token
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // User enabled?

        if (!$user['enabled']) {

            $msg = 'Unsuccessful authentication with token';
            $reason = 'User disabled';

            $this->log->notice($msg, [
                'reason' => $reason,
                'access_token' => $access_token
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        /*
         * TODO:
         * Should this be deleting the refresh token
         * and generating new/issuing new credentials?
         */

        $user = Arr::except($user, [
            'password',
            'salt'
        ]);

        $this->log->info('Successful authentication with token', [
            'user_id' => $user['id'],
        ]);

        $this->events->doEvent('auth.success', $user, 'token');

        return $user;

    }

}