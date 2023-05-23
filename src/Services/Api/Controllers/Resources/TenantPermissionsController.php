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
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\TenantPermissionsModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantPermissionsCollection;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantPermissionsResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantPermissionsController extends PrivateApiController implements ScopedResourceInterface
{

    protected TenantPermissionsModel $tenantPermissionsModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantPermissionsModel $tenantPermissionsModel)
    {
        $this->tenantPermissionsModel = $tenantPermissionsModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Create tenant permission.
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
            'tenants.permissions.create'
        ]);

        $attrs = $this->getResourceAttributesOrAbort('tenantPermissions', $this->tenantPermissionsModel->getRequiredAttrs(), $this->tenantPermissionsModel->getAllowedAttrs());

        try {

            $id = $this->tenantPermissionsModel->create($args['tenant_id'], $attrs);
            $created = $this->tenantPermissionsModel->get($args['tenant_id'], $id);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantPermissionsResource::create($created, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant permission collection.
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
            'tenants.permissions.read',
            'tenant.permissions.read'
        ], $args['tenant_id']);

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenantPermissions');

        try {

            $results = $this->tenantPermissionsModel->getCollection($args['tenant_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantPermissionsCollection::create($results, [
            'tenant_id' => $args['tenant_id'],
            'collection_prefix' => '/tenants/' . $args['tenant_id'] . '/permissions',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant permission.
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
            'tenants.permissions.read',
            'tenant.permissions.read'
        ], $args['tenant_id']);

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'tenantPermissions', array_keys($this->tenantPermissionsModel->getSelectableCols()));

        try {

            $results = $this->tenantPermissionsModel->get($args['tenant_id'], $args['permission_id'], $fields);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantPermissionsResource::create($results, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Update tenant permission.
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
            'tenants.permissions.update'
        ]);

        $attrs = $this->getResourceAttributesOrAbort('tenantPermissions', [], $this->tenantPermissionsModel->getAllowedAttrs());

        try {

            $this->tenantPermissionsModel->update($args['tenant_id'], $args['permission_id'], $attrs);
            $updated = $this->tenantPermissionsModel->get($args['tenant_id'], $args['permission_id']);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantPermissionsResource::create($updated, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Delete tenant permission.
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
            'tenants.permissions.delete'
        ]);

        try {

            $this->tenantPermissionsModel->delete($args['tenant_id'], $args['permission_id']);

            $this->response->setStatusCode(204)->send();

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

    }

}