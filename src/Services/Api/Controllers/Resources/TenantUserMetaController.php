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
use Bayfront\Bones\Services\Api\Models\Resources\TenantUserMetaModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantUserMetaCollection;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantUserMetaResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantUserMetaController extends PrivateApiController implements ScopedResourceInterface
{

    protected TenantUserMetaModel $tenantUserMetaModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantUserMetaModel $tenantUserMetaModel)
    {
        $this->tenantUserMetaModel = $tenantUserMetaModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Create tenant user meta.
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

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.user.meta.create',
            'tenant.user.meta.create'
        ], $args['tenant_id']);

        $attrs = $this->getResourceAttributesOrAbort('tenantUserMeta', $this->tenantUserMetaModel->getRequiredAttrs(), $this->tenantUserMetaModel->getAllowedAttrs());

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'tenants.user.meta.create'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $id = $this->tenantUserMetaModel->create($args['tenant_id'], $args['user_id'], $attrs, $allow_protected);

            $created = $this->tenantUserMetaModel->get($args['tenant_id'], $args['user_id'], $id, [], $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantUserMetaResource::create($created, [
            'tenant_id' => $args['tenant_id'],
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant user meta collection.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws InvalidSchemaException
     * @throws HttpException
     * @throws UnexpectedApiException
     * @throws InvalidStatusCodeException
     */
    public function getCollection(array $args): void
    {

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.user.meta.read',
            'tenant.user.meta.read'
        ], $args['tenant_id']);

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenantUserMeta');

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'tenants.user.meta.read'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $results = $this->tenantUserMetaModel->getCollection($args['tenant_id'], $args['user_id'], $query, $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantUserMetaCollection::create($results, [
            'tenant_id' => $args['tenant_id'],
            'user_id' => $args['user_id'],
            'collection_prefix' => '/tenants/' . $args['tenant_id'] . '/users/' . $args['user_id'] . '/meta',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant user meta.
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

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.user.meta.read',
            'tenant.user.meta.read'
        ], $args['tenant_id']);

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'tenantUserMeta', array_keys($this->tenantUserMetaModel->getSelectableCols()));

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'tenants.user.meta.read'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $results = $this->tenantUserMetaModel->get($args['tenant_id'], $args['user_id'], $args['meta_id'], $fields, $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantUserMetaResource::create($results, [
            'tenant_id' => $args['tenant_id'],
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Update tenant user meta.
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

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.user.meta.update',
            'tenant.user.meta.update'
        ], $args['tenant_id']);

        $attrs = $this->getResourceAttributesOrAbort('tenantUserMeta', [], [
            'metaValue'
        ]);

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'tenants.user.meta.update'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $this->tenantUserMetaModel->update($args['tenant_id'], $args['user_id'], $args['meta_id'], $attrs, $allow_protected);

            $updated = $this->tenantUserMetaModel->get($args['tenant_id'], $args['user_id'], $args['meta_id'], [], $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantUserMetaResource::create($updated, [
            'tenant_id' => $args['tenant_id'],
            'user_id' => $args['user_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Delete tenant user meta.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     */
    public function delete(array $args): void
    {

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.user.meta.delete',
            'tenant.user.meta.delete'
        ], $args['tenant_id']);

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'tenants.user.meta.delete'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $this->tenantUserMetaModel->delete($args['tenant_id'], $args['user_id'], $args['meta_id'], $allow_protected);

            $this->response->setStatusCode(204)->send();

        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

    }

}