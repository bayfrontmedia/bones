<?php

namespace Bayfront\Bones\Services\Api\Controllers;

use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Controllers\Abstracts\AuthApiController;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnauthorizedException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\AuthModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\Bones\Services\Api\Schemas\AuthResource;
use Bayfront\Bones\Services\Api\Schemas\Resources\UsersResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
use Exception;

class AuthController extends AuthApiController
{

    protected AuthModel $authModel;
    protected UsersModel $usersModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, AuthModel $authModel, UsersModel $usersModel)
    {
        $this->authModel = $authModel;
        $this->usersModel = $usersModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * @param string $user_id
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    protected function returnAuthResource(string $user_id): void
    {

        // Reset auth bucket

        $this->resetRateLimit(md5('auth-' . Request::getIp()));

        // Create JWT

        $jwt = $this->authModel->createAccessToken($user_id, $this->authModel->getAllowedRateLimit($user_id));

        try {

            $schema = AuthResource::create([
                'user_id' => $user_id,
                'access_token' => $jwt['token'],
                'refresh_token' => $this->authModel->createRefreshToken($user_id),
                'expires_in' => $jwt['expires_in'],
                'expires_at' => $jwt['expires_at']
            ]);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10600);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10601);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10602);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10603);
        }

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Login with email + password.
     *
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function login(): void
    {

        $body = $this->getBodyOrAbort([
            'email',
            'password'
        ], [
            'email',
            'password'
        ]);

        try {

            $user = $this->authModel->authenticateWithPassword($body['email'], $body['password']);

        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10604);
        } catch (NotFoundException|UnauthorizedException) {
            App::abort(401, 'Invalid email and / or password', [], 10605);
        }

        $this->returnAuthResource($user['id']);

    }

    /**
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function refresh(): void
    {

        $body = $this->getBodyOrAbort([
            'accessToken',
            'refreshToken'
        ], [
            'accessToken',
            'refreshToken'
        ]);

        try {

            $user = $this->authModel->authenticateWithRefreshToken($body['accessToken'], $body['refreshToken']);

        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10606);
        } catch (NotFoundException|UnauthorizedException) {
            App::abort(401, 'Token is invalid or has expired', [], 10607);
        }

        $this->returnAuthResource($user['id']);

    }

    /**
     * Create and save password reset token for user.
     *
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     */
    public function createPasswordToken(): void
    {

        $body = $this->getBodyOrAbort([
            'email'
        ], [
            'email'
        ]);

        try {

            $this->authModel->createPasswordToken($body['email']);

        } catch (Exception) {
            $this->response->setStatusCode(202)->send();
            exit;
        }

        $this->response->setStatusCode(202)->send();

    }

    /**
     * Does a valid token exist?
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws ForbiddenException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */
    public function passwordTokenExists(array $args): void
    {

        if (!$this->authModel->passwordTokenExists($args['user_id'], Request::getQuery('token', ''))) {
            App::abort(404, '', [], 10608);
        }

        $this->response->setStatusCode(204)->send();

    }

    /**
     * Update password using token.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function updatePassword(array $args): void
    {

        $attrs = $this->getResourceAttributesOrAbort('users', ['password'], ['password']);

        try {

            $this->authModel->updatePassword($args['user_id'], Request::getQuery('token', ''), $attrs['password']);
            $user = $this->usersModel->get($args['user_id']);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10609);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10610);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10611);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10612);
        }

        $schema = UsersResource::create($user, [
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

}