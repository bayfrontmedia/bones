<?php

namespace Bayfront\Bones\Services\Api\Controllers;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Controllers\Abstracts\PublicApiController;
use Bayfront\Bones\Services\Api\Controllers\Resources\UsersController;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\TenantInvitationsModel;
use Bayfront\Bones\Services\Api\Models\Resources\UserMetaModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\UsersResource;
use Bayfront\Container\ContainerException;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
use Bayfront\Validator\Validate;

class PublicController extends PublicApiController
{

    protected UsersModel $usersModel;
    protected TenantInvitationsModel $tenantInvitationsModel;

    /**
     * @param EventService $events
     * @param FilterService $filters
     * @param Response $response
     * @param UsersModel $usersModel
     * @param TenantInvitationsModel $tenantInvitationsModel
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function __construct(EventService $events, FilterService $filters, Response $response, UsersModel $usersModel, TenantInvitationsModel $tenantInvitationsModel)
    {
        $this->usersModel = $usersModel;
        $this->tenantInvitationsModel = $tenantInvitationsModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Create user.
     *
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     * @throws ContainerException
     */
    public function createUser(): void
    {

        if (!App::getConfig('api.registration.users.public')) {

            /*
             * Required permissions handled by UsersController
             */

            /** @var UsersController $usersController */
            $usersController = App::make('Bayfront\Bones\Services\Api\Controllers\Resources\UsersController');
            $usersController->create();

            return;

        }

        // Public registration

        $this->createUserProcess();

    }

    /**
     * Create user process.
     * This method should not be mapped to a route, but is used by
     * createUser() and the create() method of UsersController.
     *
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function createUserProcess(): void
    {

        $attrs = $this->getResourceAttributesOrAbort('users', $this->usersModel->getRequiredAttrs(), Arr::except($this->usersModel->getAllowedAttrs(), 'enabled'));

        try {

            if (App::getConfig('api.registration.users.enabled')) {

                $attrs['enabled'] = true;
                $id = $this->usersModel->create($attrs);
                $created = $this->usersModel->get($id);

            } else {

                $attrs['enabled'] = false;
                $id = $this->usersModel->create($attrs, true);
                $created = $this->usersModel->get($id);

            }

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10620);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10621);
        }

        $schema = UsersResource::create($created, [
            'user_id' => $id
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Verify new user verification meta and enable user if valid.
     *
     * @param array $args
     * @return void
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function verifyUser(array $args): void
    {

        if ($this->usersModel->verifyNewUserVerification($args['user_id'], $args['verify_id'])) {

            $results = $this->usersModel->get($args['user_id']);

            $schema = UsersResource::create($results, [
                'user_id' => $args['user_id']
            ]);

            $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

            return;

        }

        App::abort(404, '', [], 10622);

    }

    /**
     * Verify new tenant invitation and add user to tenant if valid.
     *
     * @param array $args
     * @return void
     * @throws BadRequestException
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function verifyTenantInvitation(array $args): void
    {

        try {

            $this->tenantInvitationsModel->verifyTenantInvitation($args['tenant_id'], $args['email']);

        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10623);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10624);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10625);
        }

        $this->response->setStatusCode(204)->send();

    }

    public function passwordTokenCreate(array $args): void
    {

        if (!Validate::email($args['email'])) {
            App::abort(400, 'Unable to create password reset token: Invalid attribute type(s)');
        }

        try {

            $user = $this->usersModel->getEntireFromEmail($args['email']);

        } catch (NotFoundException) {
            $this->response->setStatusCode(202)->send();
            exit;
        }

        $user = Arr::only($user, array_keys($this->usersModel->getSelectableCols())); // Drop sensitive columns

        $token = App::createKey(8);

        try {

            /** @var UserMetaModel $userMetaModel */
            $userMetaModel = App::make('Bayfront\Bones\Services\Api\Models\Resources\UserMetaModel');
            $userMetaModel->create($user['id'], [
                'id' => '00-password-reset-token',
                'metaValue' => json_encode([
                    'token' => $token,
                    'expiresAt' => time() + (App::getConfig('api.duration.reset_token', 0) * 60)
                ])
            ], true, true);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10626);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10627);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10628);
        } catch (NotFoundException) {
            $this->response->setStatusCode(202)->send();
            exit;
        }

        $this->events->doEvent('api.password.token.create', $user, $token);

    }

    public function passwordTokenGet(): void
    {

    }

    public function updatePassword(): void
    {

    }


}