<?php

namespace Bayfront\Bones\Services\Api\Controllers\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Controllers\Abstracts\PrivateApiController;
use Bayfront\Bones\Services\Api\Controllers\Interfaces\ResourceInterface;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\TenantsModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantsCollection;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantsResource;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantsController extends PrivateApiController implements ResourceInterface
{

    protected TenantsModel $tenantsModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantsModel $tenantsModel)
    {
        $this->tenantsModel = $tenantsModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Create tenant.
     *
     * If registration is open, "enabled" field is not allowed,
     * as all tenants are enabled by default.
     *
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidSchemaException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function create(): void
    {

        if (App::getConfig('api.registration.tenants.open')) {

            $attrs = $this->getResourceAttributesOrAbort('tenants', $this->tenantsModel->getRequiredAttrs(), Arr::except($this->tenantsModel->getAllowedAttrs(), 'enabled'));

            if (!$this->user->hasAnyPermissions([
                    'global.admin',
                    'tenants.create'
                ]) && $this->user->getId() !== $attrs['owner']) {
                App::abort(403, '', [], 10500);
            }

            $attrs['enabled'] = true;

        } else {

            $attrs = $this->getResourceAttributesOrAbort('tenants', $this->tenantsModel->getRequiredAttrs(), $this->tenantsModel->getAllowedAttrs());

            $this->canDoAnyOrAbort([
                'global.admin',
                'tenants.create'
            ]);

        }

        try {

            $id = $this->tenantsModel->create($attrs);
            $created= $this->tenantsModel->get($id);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10501);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10502);
        }

        $schema = TenantsResource::create($created, [
            'tenant_id' => $id
        ]);

        $this->response->setStatusCode(201)->setHeaders([
            'Location' => Request::getUrl() . '/' . $id
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant collection.
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

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenants');

        // Filter tenants

        if (!$this->user->hasAnyPermissions([
            'global.admin',
            'tenants.read'
        ])) {

            $query['where']['owner']['eq'] = "UUID_TO_BIN('" . $this->user->getId() . "', 1)";

        }

        try {

            $results = $this->tenantsModel->getCollection($query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10503);
        }

        $schema = TenantsCollection::create($results, [
            'collection_prefix' => '/tenants',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Get tenant.
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
                'tenants.read'
            ]) && !$this->user->inTenant($args['user_id'])) {
            App::abort(403, '', [], 10504);
        }

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'tenants', array_keys($this->tenantsModel->getSelectableCols()));

        try {

            $results = $this->tenantsModel->get($args['tenant_id'], $fields);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10505);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10506);
        }

        $schema = TenantsResource::create($results, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Update tenant.
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
                'tenants.update'
            ]) && (!$this->user->ownsTenant($args['tenant_id']) || !$this->user->hasAllPermissions([
                'tenant.update'
                ], $args['tenant_id']))) {
            App::abort(403, '', [], 10507);
        }

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'tenants.update'
            ])) {

            $attrs = $this->getResourceAttributesOrAbort('tenants', [], Arr::except($this->tenantsModel->getAllowedAttrs(), 'enabled'));

        } else {

            $attrs = $this->getResourceAttributesOrAbort('tenants', [], $this->tenantsModel->getAllowedAttrs());

        }

        try {

            $this->tenantsModel->update($args['tenant_id'], $attrs);
            $updated = $this->tenantsModel->get($args['tenant_id']);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10508);
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage(), [], 10509);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10510);
        }

        $schema = TenantsResource::create($updated, [
            'tenant_id' => $args['tenant_id']
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Delete tenant.
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
                'tenants.delete'
            ]) && (!$this->user->ownsTenant($args['user_id']) || !$this->user->hasAllPermissions([
                    'tenant.delete'
                ], $args['tenant_id']))) {
            App::abort(403, '', [], 10511);
        }

        try {

            $this->tenantsModel->delete($args['tenant_id']);

            $this->response->setStatusCode(204)->send();

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10512);
        }

    }

}