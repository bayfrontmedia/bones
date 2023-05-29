<?php

namespace Bayfront\Bones\Services\Api\Controllers\Resources;

use Bayfront\ArrayHelpers\Arr;
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
use Bayfront\Bones\Services\Api\Models\Resources\TenantMetaModel;
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
    protected TenantMetaModel $tenantMetaModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantPermissionsModel $tenantPermissionsModel, TenantMetaModel $tenantMetaModel)
    {
        $this->tenantPermissionsModel = $tenantPermissionsModel;
        $this->tenantMetaModel = $tenantMetaModel;

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
            'tenants.permissions.create',
            'tenant.permissions.create'
        ]);

        $attrs = $this->getResourceAttributesOrAbort('tenantPermissions', $this->tenantPermissionsModel->getRequiredAttrs(), $this->tenantPermissionsModel->getAllowedAttrs());

        try {

            $id = $this->tenantPermissionsModel->create($args['tenant_id'], $attrs);
            $created = $this->tenantPermissionsModel->get($args['tenant_id'], $id);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10460);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10461);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10462);
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
            App::abort(400, $e->getMessage(), [], 10463);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10464);
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
            App::abort(400, $e->getMessage(), [], 10465);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10466);
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
            'tenants.permissions.update',
            'tenant.permissions.update'
        ]);

        if (!$this->user->hasAnyPermissions([
            'global.admin',
            'tenants.permissions.update'
        ])) {

            $plan_permissions = $this->tenantMetaModel->getValue($args['tenant_id'], '00-plan-permissions', true);

            if ($plan_permissions) {

                $plan_permissions = json_decode($plan_permissions, true);

                if (in_array($args['permission_id'], Arr::pluck($plan_permissions, 'id'))) {
                    App::abort(403, 'Unable to update tenant permission: Permission is protected', [], 10467);
                }

            }

        }

        $attrs = $this->getResourceAttributesOrAbort('tenantPermissions', [], $this->tenantPermissionsModel->getAllowedAttrs());

        try {

            $this->tenantPermissionsModel->update($args['tenant_id'], $args['permission_id'], $attrs);
            $updated = $this->tenantPermissionsModel->get($args['tenant_id'], $args['permission_id']);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10468);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10469);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10470);
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
            'tenants.permissions.delete',
            'tenant.permissions.delete'
        ]);

        if (!$this->user->hasAnyPermissions([
            'global.admin',
            'tenants.permissions.delete'
        ])) {

            $plan_permissions = $this->tenantMetaModel->getValue($args['tenant_id'], '00-plan-permissions', true);

            if ($plan_permissions) {

                $plan_permissions = json_decode($plan_permissions, true);

                if (in_array($args['permission_id'], Arr::pluck($plan_permissions, 'id'))) {
                    App::abort(403, 'Unable to delete tenant permission: Permission is protected', [], 10471);
                }

            }

        }

        try {

            $this->tenantPermissionsModel->delete($args['tenant_id'], $args['permission_id']);

            $this->response->setStatusCode(204)->send();

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10472);
        }

    }

}