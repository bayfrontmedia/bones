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
use Bayfront\Bones\Services\Api\Models\Resources\TenantGroupsModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantGroupsCollection;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantGroupsResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantGroupsController extends PrivateApiController implements ScopedResourceInterface
{

    protected TenantGroupsModel $tenantGroupsModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantGroupsModel $tenantGroupsModel)
    {
        $this->tenantGroupsModel = $tenantGroupsModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Create tenant group.
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
            'tenants.groups.create',
            'tenant.group.create'
        ], $args['tenant_id']);

        $attrs = $this->getResourceAttributesOrAbort('tenantGroups', $this->tenantGroupsModel->getRequiredAttrs(), $this->tenantGroupsModel->getAllowedAttrs());

        try {

            $id = $this->tenantGroupsModel->create($args['tenant_id'], $attrs);

            $created = $this->tenantGroupsModel->get($args['tenant_id'], $id);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10400);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10401);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10402);
        }

        $schema = TenantGroupsResource::create($created, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant group collection.
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
            'tenants.groups.read',
            'tenant.groups.read'
        ], $args['tenant_id']);

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenantGroups');

        try {

            $results = $this->tenantGroupsModel->getCollection($args['tenant_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10403);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10404);
        }

        $schema = TenantGroupsCollection::create($results, [
            'tenant_id' => $args['tenant_id'],
            'collection_prefix' => '/tenants/' . $args['tenant_id'] . '/groups',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant group.
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
            'tenants.groups.read',
            'tenant.group.read'
        ], $args['tenant_id']);

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'tenantGroups', array_keys($this->tenantGroupsModel->getSelectableCols()));

        try {

            $results = $this->tenantGroupsModel->get($args['tenant_id'], $args['group_id'], $fields);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10405);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10406);
        }

        $schema = TenantGroupsResource::create($results, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Update tenant group.
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
            'tenants.groups.update',
            'tenant.groups.update'
        ], $args['tenant_id']);

        $attrs = $this->getResourceAttributesOrAbort('tenantGroups', [], $this->tenantGroupsModel->getAllowedAttrs());

        try {

            $this->tenantGroupsModel->update($args['tenant_id'], $args['group_id'], $attrs);

            $updated = $this->tenantGroupsModel->get($args['tenant_id'], $args['group_id']);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10407);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10408);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10409);
        }

        $schema = TenantGroupsResource::create($updated, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Delete tenant group.
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
            'tenants.groups.delete',
            'tenant.groups.delete'
        ], $args['tenant_id']);

        try {

            $this->tenantGroupsModel->delete($args['tenant_id'], $args['group_id']);

            $this->response->setStatusCode(204)->send();

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10410);
        }

    }

}