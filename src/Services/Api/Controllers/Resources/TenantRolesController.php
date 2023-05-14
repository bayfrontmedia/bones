<?php

namespace Bayfront\Bones\Services\Api\Controllers\Resources;

use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Abstracts\Controllers\PrivateApiController;
use Bayfront\Bones\Services\Api\Controllers\Interfaces\ScopedResourceInterface;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\TenantRolesModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantRolesCollection;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantRolesResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantRolesController extends PrivateApiController implements ScopedResourceInterface
{

    protected TenantRolesModel $tenantRolesModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantRolesModel $tenantRolesModel)
    {
        $this->tenantRolesModel = $tenantRolesModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Create tenant role.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws InvalidSchemaException
     * @throws HttpException
     * @throws UnexpectedApiException
     * @throws InvalidStatusCodeException
     */
    public function create(array $args): void
    {

        $attrs = $this->getResourceAttributesOrAbort('tenantRoles', $this->tenantRolesModel->getRequiredAttrs(), $this->tenantRolesModel->getAllowedAttrs());

        try {

            $id = $this->tenantRolesModel->create($args['tenant_id'], $attrs);

            $created = $this->tenantRolesModel->get($args['tenant_id'], $id);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantRolesResource::create($created, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant roles collection.
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

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenantRoles');

        try {

            $results = $this->tenantRolesModel->getCollection($args['tenant_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantRolesCollection::create($results, [
            'tenant_id' => $args['tenant_id'],
            'collection_prefix' => '/tenants/' . $args['tenant_id'] . '/roles',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant role.
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

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'tenantRoles', array_keys($this->tenantRolesModel->getSelectableCols()));

        try {

            $results = $this->tenantRolesModel->get($args['tenant_id'], $args['role_id'], $fields);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantRolesResource::create($results, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Update tenant role.
     *
     * TODO:
     * Check if a default role.
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

        $attrs = $this->getResourceAttributesOrAbort('tenantRoles', [], $this->tenantRolesModel->getAllowedAttrs());

        try {

            $this->tenantRolesModel->update($args['tenant_id'], $args['role_id'], $attrs);

            $updated = $this->tenantRolesModel->get($args['tenant_id'], $args['role_id']);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantRolesResource::create($updated, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Delete tenant role.
     *
     * TODO:
     * Check for default permission.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     */
    public function delete(array $args): void
    {

        try {

            $this->tenantRolesModel->delete($args['tenant_id'], $args['role_id']);

            $this->response->setStatusCode(204)->send();

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

    }

}