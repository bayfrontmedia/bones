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
use Bayfront\Bones\Services\Api\Exceptions\UnauthorizedException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Relationships\TenantUsersModel;
use Bayfront\Bones\Services\Api\Models\Resources\UserMetaModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\HttpRequest\Request;
use Bayfront\JWT\Jwt;
use Bayfront\JWT\TokenException;
use Bayfront\MultiLogger\Log;
use Bayfront\PDO\Db;
use Bayfront\Validator\Validate;
use Exception;

class AuthModel extends ApiModel
{

    protected FilterService $filters;
    protected UsersModel $usersModel;
    protected UserMetaModel $userMetaModel;
    protected TenantUsersModel $tenantUsersModel;

    public function __construct(EventService $events, Db $db, Log $log, FilterService $filters, UsersModel $usersModel, UserMetaModel $userMetaModel, TenantUsersModel $tenantUsersModel)
    {
        parent::__construct($events, $db, $log);

        $this->filters = $filters;
        $this->usersModel = $usersModel;
        $this->userMetaModel = $userMetaModel;
        $this->tenantUsersModel = $tenantUsersModel;
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

        $expiration = $time + (App::getConfig('api.duration.access_token') * 60);

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
            'expires_in' => (string)App::getConfig('api.duration.access_token') * 60,
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
                'expiresAt' => time() + (App::getConfig('api.duration.refresh_token') * 60)
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

        if (!in_array(Api::AUTH_PASSWORD, App::getConfig('api.auth.methods'))) {

            $msg = 'Unable to authenticate with password';
            $reason = 'Authentication with password not allowed';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        try {

            $user = $this->usersModel->getEntireFromEmail($email);

        } catch (NotFoundException) {

            $msg = 'Unable to authenticate with password';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'email' => $email
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        if ($user['enabled'] !== 1) {

            $msg = 'Unable to authenticate with password';
            $reason = 'User disabled';

            $this->log->notice($msg, [
                'reason' => $reason,
                'email' => $email
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        if (!$this->verifyPassword($password, $user['salt'], $user['password'])) {

            $msg = 'Unable to authenticate with password';
            $reason = 'Incorrect password';

            $this->log->notice($msg, [
                'reason' => $reason,
                'email' => $email
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        $user = Arr::only($user, array_keys($this->usersModel->getSelectableCols())); // Filter unsafe cols

        $this->log->info('Successful authentication with password', [
            'user_id' => $user['id'],
            'name' => Arr::get($user, 'meta.name', '')
        ]);

        $this->events->doEvent('api.authenticate', $user, Api::AUTH_PASSWORD);

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

        if (!in_array(Api::AUTH_REFRESH_TOKEN, App::getConfig('api.auth.methods'))) {

            $msg = 'Unable to authenticate with refresh token';
            $reason = 'Authentication with refresh token not allowed';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        $jwt = new Jwt(App::getConfig('app.key'));

        try {

            /*
             * Validate the JWT has not been modified, even if it is expired.
             * All that is needed is the user ID
             */

            $token = $jwt->validateSignature($access_token)->decode($access_token, false);

        } catch (TokenException) { // Invalid JWT

            $msg = 'Unable to authenticate with refresh token';
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

            $msg = 'Unable to authenticate with refresh token';
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

            $msg = 'Unable to authenticate with refresh token';
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

            $msg = 'Unable to authenticate with refresh token';
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

            $msg = 'Unable to authenticate with refresh token';
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

            $msg = 'Unable to authenticate with refresh token';
            $reason = 'Invalid credentials';

            $this->log->notice($msg, [
                'reason' => $reason,
                'access_token' => $access_token
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // User enabled?

        if (!$user['enabled']) {

            $msg = 'Unable to authenticate with refresh token';
            $reason = 'User disabled';

            $this->log->notice($msg, [
                'reason' => $reason,
                'access_token' => $access_token
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        $user = Arr::only($user, array_keys($this->usersModel->getSelectableCols())); // Filter unsafe cols

        $this->log->info('Successful authentication with refresh token', [
            'user_id' => $user['id'],
            'name' => Arr::get($user, 'meta.name', '')
        ]);


        $this->events->doEvent('api.authenticate', $user, Api::AUTH_REFRESH_TOKEN);

        return $user;

    }

    /**
     * Authenticate with access token (JWT), ensuring user exists and is enabled.
     *
     * @param string $token
     * @return array (Keys: user_model, rate_limit)
     * @throws ForbiddenException
     * @throws UnauthorizedException
     * @throws UnexpectedApiException
     */
    public function authenticateWithAccessToken(string $token): array
    {

        if (!in_array(Api::AUTH_ACCESS_TOKEN, App::getConfig('api.auth.methods'))) {

            $msg = 'Unable to authenticate with access token';
            $reason = 'Authentication with access token not allowed';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);
        }

        try {

            $decoded = $this->decodeAccessToken($token);

        } catch (UnauthorizedException) {

            $msg = 'Unable to authenticate with access token';
            $reason = 'Invalid token';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // Check user exists

        try {

            // Skip api.user.read event and logging for self
            $user = new UserModel($this->events, $this->db, $this->log, $this->usersModel, $this->userMetaModel, $this->tenantUsersModel, $decoded['payload']['sub'], true);

        } catch (NotFoundException) {

            $msg = 'Unable to authenticate with access token';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // Check enabled

        if (!$user->isEnabled()) {

            $msg = 'Unable to authenticate with access token';
            $reason = 'User disabled';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $user->getId()
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        /*
         * No log on success as this would be done on each PrivateApiController request.
         */

        // Event

        $user_arr = Arr::only($user->getUser(), array_keys($this->usersModel->getSelectableCols()));

        $this->events->doEvent('api.authenticate', $user_arr, Api::AUTH_ACCESS_TOKEN);

        return [
            'user_model' => $user,
            'rate_limit' => $decoded['payload']['rate_limit']
        ];

    }

    /**
     * Authenticate with user (API) key, ensuring user exists and is enabled.
     *
     * @param string $key
     * @param string $domain
     * @param string $ip
     * @return array (Keys: user_model, rate_limit)
     * @throws ForbiddenException
     * @throws UnauthorizedException
     * @throws UnexpectedApiException
     */
    public function authenticateWithKey(string $key, string $domain = '', string $ip = ''): array
    {

        if (!in_array(Api::AUTH_KEY, App::getConfig('api.auth.methods'))) {

            $msg = 'Unable to authenticate with key';
            $reason = 'Authentication with key not allowed';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);
        }

        $id_short = substr($key, 0, 7);

        // Check key valid

        $valid_key = $this->db->row("SELECT id, BIN_TO_UUID(userId, 1) as userId, keyValue, allowedDomains, allowedIps, lastUsed 
                                                FROM api_user_keys WHERE id = :id AND expiresAt > NOW()", [
            'id' => $id_short
        ]);

        if (!$valid_key) {

            $msg = 'Unable to authenticate with key';
            $reason = 'Key does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'key' => $key
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // Check user exists

        try {

            // Skip api.user.read event and logging for self
            $user = new UserModel($this->events, $this->db, $this->log, $this->usersModel, $this->userMetaModel, $this->tenantUsersModel, $valid_key['userId'], true);

        } catch (NotFoundException) {

            $msg = 'Unable to authenticate with key';
            $reason = 'User does not exist';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            /*
             * Delete invalid.
             * This should never happen due to db constraints.
             */

            $this->db->delete('api_user_keys', [
                'id' => $id_short
            ]);

            throw new UnauthorizedException($msg . ': ' . $reason);

        }

        // Check enabled

        if (!$user->isEnabled()) {

            $msg = 'Unable to authenticate with key';
            $reason = 'User disabled';

            $this->log->notice($msg, [
                'reason' => $reason,
                'user_id' => $user->getId()
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        // Check referring domain

        if ($domain != '' && $valid_key['allowedDomains']) {

            $domains = json_decode($valid_key['allowedDomains'], true);

            if (!in_array($domain, $domains)) {

                $msg = 'Unable to authenticate with key';
                $reason = 'Invalid domain';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'user_id' => $user->getId()
                ]);

                throw new ForbiddenException($msg . ': ' . $reason);

            }

        }

        // Check IP

        if ($ip != '' && $valid_key['allowedIps']) {

            $ips = json_decode($valid_key['allowedIps'], true);

            if (!in_array($ip, $ips)) {

                $msg = 'Unable to authenticate with key';
                $reason = 'Invalid IP';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'user_id' => $user->getId()
                ]);

                throw new ForbiddenException($msg . ': ' . $reason);

            }

        }

        // Verify key

        if ($this->verifyPassword($key, $user->get('salt', ''), $valid_key['keyValue'])) {

            // Update lastUsed if older than today (prevent queries on every request)

            if ($valid_key['lastUsed']) {
                $valid_key['lastUsed'] = strtotime($valid_key['lastUsed']);
            } else { // NULL if not yet used
                $valid_key['lastUsed'] = time() - 100000;
            }

            if (date('Y-m-d', $valid_key['lastUsed']) != date('Y-m-d')) {

                $this->db->query("UPDATE api_user_keys SET lastUsed = NOW() WHERE id = :id", [
                    'id' => $id_short
                ]);

            }

            /*
             * No log on success as this would be done on each PrivateApiController request.
             */

            $user_arr = Arr::only($user->getUser(), array_keys($this->usersModel->getSelectableCols()));

            $this->events->doEvent('api.authenticate', $user_arr, Api::AUTH_KEY);

            return [
                'user_model' => $user,
                'rate_limit' => $this->getAllowedRateLimit($user->getId())
            ];

        }

        $msg = 'Unable to authenticate with key';
        $reason = 'Invalid key';

        $this->log->notice($msg, [
            'reason' => $reason,
            'user_id' => $user['id']
        ]);

        throw new UnauthorizedException($msg . ': ' . $reason);

    }

    /**
     * Create and save password token for user.
     *
     * 15 minute interval is enforced before requesting a new token when one already exists.
     *
     * @param string $email
     * @return void
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function createPasswordToken(string $email): void
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

        // Check request interval

        $existing_token = $this->db->row("SELECT id FROM api_user_meta WHERE id = :id AND userId = UUID_TO_BIN(:user_id, 1) AND updatedAt > NOW() - interval 15 minute", [
            'id' => '00-password-token',
            'user_id' => $user['id']
        ]);

        if ($existing_token) {

            $msg = 'Unable to create password reset token';
            $reason = 'Request interval not yet elapsed';

            $this->log->notice($msg, [
                'reason' => $reason,
                'email' => $email
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        try {

            $token = App::createKey(16);

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
     * Does a valid password token exist?
     *
     * @param string $user_id
     * @param string $token_value
     * @return bool
     * @throws ForbiddenException
     * @throws NotFoundException
     */

    public function passwordTokenExists(string $user_id, string $token_value): bool
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

        if (!$this->passwordTokenExists($user_id, $token_value)) {

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

        // Event

        /*
         * NOTE:
         * The user was already queried in the passwordTokenExists method,
         * but since it is not returned, it must be queried again.
         */

        $user = $this->usersModel->getEntire($user_id, true);

        $user = Arr::only($user, array_keys($this->usersModel->getSelectableCols())); // Drop sensitive columns

        $this->events->doEvent('api.password.token.updated', $user);

    }

}