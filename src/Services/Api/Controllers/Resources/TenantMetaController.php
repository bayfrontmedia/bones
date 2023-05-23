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
use Bayfront\Bones\Services\Api\Models\Resources\TenantMetaModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantMetaCollection;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantMetaResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantMetaController extends PrivateApiController implements ScopedResourceInterface
{

    protected TenantMetaModel $tenantMetaModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantMetaModel $tenantMetaModel)
    {
        $this->tenantMetaModel = $tenantMetaModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Create tenant meta.
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
            'tenants.meta.create',
            'tenant.meta.create'
        ], $args['tenant_id']);

        $attrs = $this->getResourceAttributesOrAbort('tenantMeta', $this->tenantMetaModel->getRequiredAttrs(), $this->tenantMetaModel->getAllowedAttrs());

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'tenants.meta.create'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $id = $this->tenantMetaModel->create($args['tenant_id'], $attrs, $allow_protected);

            $created = $this->tenantMetaModel->get($args['tenant_id'], $id, [], $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10440);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10441);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10442);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10443);
        }

        $schema = TenantMetaResource::create($created, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant meta collection.
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

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.meta.read',
            'tenant.meta.read'
        ], $args['tenant_id']);

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenantMeta');

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'tenants.meta.read'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $results = $this->tenantMetaModel->getCollection($args['tenant_id'], $query, $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10444);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10445);
        }

        $schema = TenantMetaCollection::create($results, [
            'tenant_id' => $args['tenant_id'],
            'collection_prefix' => '/tenants/' . $args['tenant_id'] . '/meta',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant meta.
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
            'tenants.meta.read',
            'tenant.meta.read'
        ], $args['tenant_id']);

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'tenantMeta', array_keys($this->tenantMetaModel->getSelectableCols()));

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'tenants.meta.read'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $results = $this->tenantMetaModel->get($args['tenant_id'], $args['meta_id'], $fields, $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10446);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10447);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10448);
        }

        $schema = TenantMetaResource::create($results, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Update tenant meta.
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
            'tenants.meta.update',
            'tenant.meta.update'
        ], $args['tenant_id']);

        $attrs = $this->getResourceAttributesOrAbort('tenantMeta', [], [
            'metaValue'
        ]);

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'tenants.meta.update'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $this->tenantMetaModel->update($args['tenant_id'], $args['meta_id'], $attrs, $allow_protected);

            $updated = $this->tenantMetaModel->get($args['tenant_id'], $args['meta_id'], [], $allow_protected);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10449);
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10450);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10451);
        }

        $schema = TenantMetaResource::create($updated, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Delete tenant meta.
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
            'tenants.meta.delete',
            'tenant.meta.delete'
        ], $args['tenant_id']);

        // Protected meta

        if ($this->user->hasAnyPermissions([
            'global.admin',
            'tenants.meta.delete'
        ])) {
            $allow_protected = true;
        } else {
            $allow_protected = false;
        }

        try {

            $this->tenantMetaModel->delete($args['tenant_id'], $args['meta_id'], $allow_protected);

            $this->response->setStatusCode(204)->send();

        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage(), [], 10452);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10453);
        }

    }

}