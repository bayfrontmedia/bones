<?php

namespace Bayfront\Bones\Services\Api\Controllers;

use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Abstracts\Controllers\AuthApiController;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnauthorizedException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\AuthModel;
use Bayfront\Bones\Services\Api\Schemas\AuthResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class AuthController extends AuthApiController
{

    protected AuthModel $authModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, AuthModel $authModel)
    {
        $this->authModel = $authModel;

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
                'access_token' => $jwt['token'],
                'refresh_token' => $this->authModel->createRefreshToken($user_id),
                'expires_in' => $jwt['expires_in'],
                'expires_at' => $jwt['expires_at']
            ]);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
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
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        } catch (UnauthorizedException $e) {
            App::abort(401, $e->getMessage());
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

        // Validate access token

        try {

            $user = $this->authModel->authenticateWithRefreshToken($body['accessToken'], $body['refreshToken']);

        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        } catch (UnauthorizedException $e) {
            App::abort(401, $e->getMessage());
        }

        $this->returnAuthResource($user['id']);

    }

}