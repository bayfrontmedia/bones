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
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\UserMetaModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\UserMetaCollection;
use Bayfront\Bones\Services\Api\Schemas\Resources\UserMetaResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class UserMetaController extends PrivateApiController implements ScopedResourceInterface
{

    protected UserMetaModel $userMetaModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, UserMetaModel $userMetaModel)
    {
        $this->userMetaModel = $userMetaModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Create user meta.
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
            'users.meta.create'
        ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10560);
        }

        $attrs = $this->getResourceAttributesOrAbort('userMeta', $this->userMetaModel->getRequiredAttrs(), $this->userMetaModel->getAllowedAttrs());

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'users.meta.create'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $id = $this->userMetaModel->create($args['user_id'], $attrs, $allow_protected);

            $created = $this->userMetaModel->get($args['user_id'], $id, [], $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10561);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10562);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10563);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10564);
        }

        $schema = UserMetaResource::create($created, [
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get user meta collection.
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
                'users.meta.read'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10565);
        }

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'userMeta');

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'users.meta.read'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $results = $this->userMetaModel->getCollection($args['user_id'], $query, $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10566);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10567);
        }

        $schema = UserMetaCollection::create($results, [
            'user_id' => $args['user_id'],
            'collection_prefix' => '/users/' . $args['user_id'] . '/meta',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get user meta.
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
                'users.meta.read'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10568);
        }

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'userMeta', array_keys($this->userMetaModel->getSelectableCols()));

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'users.meta.read'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $results = $this->userMetaModel->get($args['user_id'], $args['meta_id'], $fields, $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10569);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10570);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10571);
        }

        $schema = UserMetaResource::create($results, [
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Update user meta.
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
                'users.meta.update'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10572);
        }

        $attrs = $this->getResourceAttributesOrAbort('userMeta', [], [
            'metaValue'
        ]);

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'users.meta.update'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $this->userMetaModel->update($args['user_id'], $args['meta_id'], $attrs, $allow_protected);

            $updated = $this->userMetaModel->get($args['user_id'], $args['meta_id'], [], $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10573);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10574);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10575);
        }

        $schema = UserMetaResource::create($updated, [
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Delete user meta.
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
                'users.meta.delete'
            ]) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10576);
        }

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'users.meta.delete'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $this->userMetaModel->delete($args['user_id'], $args['meta_id'], $allow_protected);

            $this->response->setStatusCode(204)->send();

        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10577);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10578);
        }

    }

}