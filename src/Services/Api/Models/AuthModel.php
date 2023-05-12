<?php

namespace Bayfront\Bones\Services\Api\Models;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Abstracts\Models\ApiModel;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnauthorizedException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\UserMetaModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\HttpRequest\Request;
use Bayfront\JWT\Jwt;
use Bayfront\JWT\TokenException;
use Bayfront\PDO\Db;
use Exception;
use Monolog\Logger;

class AuthModel extends ApiModel
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
     * Return rate limit for user based on meta key or API config value.
     *
     * @param string $user_id
     * @return int
     */
    public function getAllowedRateLimit(string $user_id): int
    {

        $rate_limit = $this->userMetaModel->getValue($user_id, '00-rate-limit', true);

        if (!$rate_limit) {
            return App::getConfig('api.rate_limit.private');
        }

        return (int)$rate_limit;

    }

    /**
     * Create and return access token (JWT) for user.
     *
     * @param string $user_id
     * @param int $rate_limit
     * @return array (Keys: token, expires_in, expires_at)
     */
    public function createAccessToken(string $user_id, int $rate_limit): array
    {

        $payload = $this->filters->doFilter('api.jwt.payload', [
            'rate_limit' => $rate_limit
        ]);

        $time = time();

        $expiration = $time + App::getConfig('api.token_duration.access');

        $jwt = new Jwt(App::getConfig('app.key'));

        $token = $jwt
            ->iss(Request::getRequest('host'))
            ->sub($user_id)
            ->iat($time)
            ->nbf($time)
            ->exp($expiration)
            ->encode($payload);

        return [
            'token' => $token,
            'expires_in' => (string)App::getConfig('api.token_duration.access'),
            'expires_at' => (string)$expiration
        ];

    }

    /**
     * Create and save refresh token for user.
     * Returns refresh token.
     *
     * @param string $user_id
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function createRefreshToken(string $user_id): string
    {

        try {

            $refresh_token = App::createKey();

        } catch (Exception $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        $this->userMetaModel->create($user_id, [
            'id' => '00-refresh-token',
            'metaValue' => json_encode([
                'token' => $this->hashPassword($refresh_token, $this->usersModel->getSalt($user_id)),
                'expiresAt' => time() + App::getConfig('api.token_duration.refresh')
            ])
        ], true, true);

        return $refresh_token;

    }

    /**
     * Decode access token (JWT).
     *
     * @param string $token
     * @param bool $validate (Validate signature and claims)
     * @return array (Keys: header, payload, signature)
     * @throws UnauthorizedException
     */
    public function decodeAccessToken(string $token, bool $validate = true): array
    {

        try {

            $jwt = new Jwt(App::getConfig('app.key'));
            return $jwt->decode($token, $validate);

        } catch (TokenException) {
            throw new UnauthorizedException('Invalid Bearer token');
        }

    }

    /**
     * Validate identity with token (JWT), ensuring user exists and is enabled.
     *
     * @param string $token
     * @return array (Keys: user_id, rate_limit)
     * @throws ForbiddenException
     * @throws UnauthorizedException
     */
    public function validateToken(string $token): array
    {

        try {

            $decoded = $this->decodeAccessToken($token);

        } catch (UnauthorizedException $e) {

            $this->log->notice('Unable to validate token', [
                'reason' => $e->getMessage()
            ]);

            throw $e;

        }

        // Valid token. Check user exists and is enabled...

        try {

            $user = $this->usersModel->get($decoded['payload']['sub'], [
                'enabled'
            ]);

        } catch (Exception $e) {

            $this->log->notice('Unable to validate token', [
                'reason' => $e->getMessage()
            ]);

            throw $e;

        }

        if ($user['enabled'] !== 1) {

            $msg = 'Unable to validate token';
            $reason = 'User disabled';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Event

        $this->events->doEvent('auth.validate', $decoded['payload']['sub'], 'token');

        return [
            'user_id' => $decoded['payload']['sub'],
            'rate_limit' => $decoded['payload']['rate_limit']
        ];

    }

    /**
     * Validate identity with user (API) key, ensuring user exists and is enabled.
     *
     * @param string $key
     * @param string $domain
     * @param string $ip
     * @return array (Keys: user_id, rate_limit)
     * @throws ForbiddenException
     * @throws UnauthorizedException
     */
    public function validateKey(string $key, string $domain = '', string $ip = ''): array
    {

        $id_short = substr($key, 0, 7);

        // Check key valid

        $valid_key = $this->db->single("SELECT id, BIN_TO_UUID(userId, 1) as userId, keyValue, allowedDomains, allowedIps FROM api_user_keys WHERE id = :id AND expiresAt > NOW()", [
            'id' => $id_short
        ]);

        if (!$valid_key) {

            $msg = 'Unable to validate key';
            $reason = 'Key does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'key' => $key
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // Check user exists

        try {

            $user = $this->usersModel->getEntire($valid_key['userId']);

        } catch (NotFoundException) {

           /*
            * Delete invalid.
            * This should never happen due to db constraints.
            */

            $this->db->delete('api_user_keys', [
                'id' => $id_short
            ]);

            $msg = 'Unable to validate key';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $valid_key['userId']
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // Check enabled

        if ($user['enabled'] !== 1) {

            $msg = 'Unable to validate key';
            $reason = 'User disabled';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $user['id']
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Check referring domain

        if ($domain != '' && $valid_key['allowedDomains']) {

            $domains = json_decode($valid_key['allowedDomains'], true);

            if (!in_array($domain, $domains)) {

                $msg = 'Unable to validate key';
                $reason = 'Invalid domain';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'user_id' => $user['id']
                ]);

                throw new ForbiddenException($msg . ': ' . $reason);

            }

        }

        // Check IP

        if ($ip != '' && $valid_key['allowedIps']) {

            $ips = json_decode($valid_key['allowedIps'], true);

            if (!in_array($ip, $ips)) {

                $msg = 'Unable to validate key';
                $reason = 'Invalid IP';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'user_id' => $user['id']
                ]);

                throw new ForbiddenException($msg . ': ' . $reason);

            }

        }

        // Verify key

        if ($this->verifyPassword($key, $user['salt'], $valid_key['keyValue'])) {

            // Update lastUsed

            $this->db->query("UPDATE api_user_keys SET lastUsed = NOW() WHERE id = :id", [
                'id' => $id_short
            ]);

            return [
                'user_id' => $user['id'],
                'rate_limit' => $this->getAllowedRateLimit($user['id'])
            ];

        }

        $msg = 'Unable to validate key';
        $reason = 'Invalid key';

        $this->log->notice($msg, [
            'reason' => $reason,
            'user_id' => $user['id']
        ]);

        throw new UnauthorizedException($msg . ': ' . $reason);

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
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws UnexpectedApiException
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

        $existing_refresh_token = $this->userMetaModel->getValue($token['payload']['sub'], '00-refresh-token', true);

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

            $this->userMetaModel->delete($token['payload']['sub'], '00-refresh-token', true);

            $msg = 'Unsuccessful authentication with token';
            $reason = 'Invalid refresh token format';

            $this->log->critical($msg, [
                'reason' => $reason,
                'access_token' => $access_token
            ]);

            throw new UnexpectedApiException($msg . ': ' . $reason);

        }

        // Validate refresh token time

        if ($existing_refresh_token['expiresAt'] <= time()) {

            // Delete invalid token

            $this->userMetaModel->delete($token['payload']['sub'], '00-refresh-token', true);

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

            $this->userMetaModel->delete($token['payload']['sub'], '00-refresh-token', true);

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