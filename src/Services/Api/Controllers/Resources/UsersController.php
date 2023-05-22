<?php

namespace Bayfront\Bones\Services\Api\Controllers\Resources;

use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Controllers\Abstracts\PrivateApiController;
use Bayfront\Bones\Services\Api\Controllers\Interfaces\ResourceInterface;
use Bayfront\Bones\Services\Api\Controllers\PublicController;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\UsersCollection;
use Bayfront\Bones\Services\Api\Schemas\Resources\UsersResource;
use Bayfront\Container\ContainerException;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class UsersController extends PrivateApiController implements ResourceInterface
{

    protected UsersModel $usersModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, UsersModel $usersModel)
    {
        $this->usersModel = $usersModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Create user.
     *
     * TODO:
     * Require users.create if registration is not public.
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
    public function create(): void
    {

        /** @var PublicController $publicController */
        $publicController = App::make('Bayfront\Bones\Services\Api\Controllers\PublicController');
        $publicController->createUser();

    }

    /**
     * Get user collection.
     *
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function getCollection(): void
    {

        $this->canDoAnyOrAbort([
            'global.admin',
            'users.read'
        ]);

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'users');

        try {

            $results = $this->usersModel->getCollection($query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        }

        $schema = UsersCollection::create($results, [
            'collection_prefix' => '/users',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get user.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function get(array $args): void
    {

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'users.read'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403);
        }

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'users', array_keys($this->usersModel->getSelectableCols()));

        try {

            $results = $this->usersModel->get($args['user_id'], $fields);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = UsersResource::create($results, [
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Update user.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function update(array $args): void
    {

        /*
         * TODO:
         * Can control "enabled" with permissions
         */

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'users.update'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403);
        }

        $attrs = $this->getResourceAttributesOrAbort('users', [], [
            'email',
            'password',
            'meta',
            //'enabled'
        ]);

        try {

            $this->usersModel->update($args['user_id'], $attrs);

            $updated = $this->usersModel->get($args['user_id']);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = UsersResource::create($updated, [
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Delete user.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     */
    public function delete(array $args): void
    {

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'users.delete'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403);
        }

        try {

            $this->usersModel->delete($args['user_id']);

            $this->response->setStatusCode(204)->send();

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

    }

}