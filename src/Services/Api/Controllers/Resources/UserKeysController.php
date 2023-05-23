<?php

namespace Bayfront\Bones\Services\Api\Controllers\Resources;

use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Controllers\Abstracts\PrivateApiController;
use Bayfront\Bones\Services\Api\Controllers\Interfaces\ScopedResourceInterface;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\UserKeysModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\UserKeysCollection;
use Bayfront\Bones\Services\Api\Schemas\Resources\UserKeysResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class UserKeysController extends PrivateApiController implements ScopedResourceInterface
{

    protected UserKeysModel $userKeysModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, UserKeysModel $userKeysModel)
    {
        $this->userKeysModel = $userKeysModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Create user key.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function create(array $args): void
    {

        if (!$this->user->hasAnyPermissions([
            'global.admin',
            'users.keys.create'
        ]) && App::getConfig('api.keys.enabled') === false) {
            App::abort(403, '', [], 10540);
        }

        if (!$this->user->hasAnyPermissions([
            'global.admin',
            'users.keys.create'
        ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10541);
        }

        $attrs = $this->getResourceAttributesOrAbort('userKeys', $this->userKeysModel->getRequiredAttrs(), $this->userKeysModel->getAllowedAttrs());

        try {

            $id = $this->userKeysModel->create($args['user_id'], $attrs);
            $id_short = substr($id, 0, 7);

            $created = $this->userKeysModel->get($args['user_id'], $id_short);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10542);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10543);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10544);
        }

        // Add full key value

        $created['keyValue'] = $id;

        $schema = UserKeysResource::create($created, [
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get user key collection.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function getCollection(array $args): void
    {

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'users.keys.read'
            ]) && App::getConfig('api.keys.enabled') === false) {
            App::abort(403, '', [], 10545);
        }

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'users.keys.create'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10546);
        }

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'userKeys');

        try {

            $results = $this->userKeysModel->getCollection($args['user_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10547);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10548);
        }

        $schema = UserKeysCollection::create($results, [
            'user_id' => $args['user_id'],
            'collection_prefix' => '/users/' . $args['user_id'] . '/keys',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get user key.
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
                'users.keys.read'
            ]) && App::getConfig('api.keys.enabled') === false) {
            App::abort(403, '', [], 10549);
        }

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'users.keys.create'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10550);
        }

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'userKeys', array_keys($this->userKeysModel->getSelectableCols()));

        try {

            $results = $this->userKeysModel->get($args['user_id'], $args['key_id'], $fields);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10551);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10552);
        }

        $schema = UserKeysResource::create($results, [
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Update user key.
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

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'users.keys.update'
            ]) && App::getConfig('api.keys.enabled') === false) {
            App::abort(403, '', [], 10553);
        }

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'users.keys.update'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10554);
        }

        $attrs = $this->getResourceAttributesOrAbort('userKeys', [], $this->userKeysModel->getAllowedAttrs());

        try {

            $this->userKeysModel->update($args['user_id'], $args['key_id'], $attrs);

            $updated = $this->userKeysModel->get($args['user_id'], $args['key_id']);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10555);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10556);
        }

        $schema = UserKeysResource::create($updated, [
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Delete user key.
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
                'users.keys.delete'
            ]) && App::getConfig('api.keys.enabled') === false) {
            App::abort(403, '', [], 10557);
        }

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'users.keys.delete'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10558);
        }

        try {

            $this->userKeysModel->delete($args['user_id'], $args['key_id']);

            $this->response->setStatusCode(204)->send();

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10559);
        }

    }

}