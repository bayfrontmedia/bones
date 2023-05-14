<?php

namespace Bayfront\Bones\Services\Api\Controllers\Resources;

use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Abstracts\Controllers\PrivateApiController;
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

        $attrs = $this->getResourceAttributesOrAbort('tenants', $this->tenantsModel->getRequiredAttrs(), $this->tenantsModel->getAllowedAttrs());

        try {

            $id = $this->tenantsModel->create($attrs);
            $created= $this->tenantsModel->get($id);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
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

        try {

            $results = $this->tenantsModel->getCollection($query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
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

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'tenants', array_keys($this->tenantsModel->getSelectableCols()));

        try {

            $results = $this->tenantsModel->get($args['tenant_id'], $fields);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
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

        $attrs = $this->getResourceAttributesOrAbort('tenants', [], $this->tenantsModel->getAllowedAttrs());

        try {

            $this->tenantsModel->update($args['tenant_id'], $attrs);

            $updated = $this->tenantsModel->get($args['tenant_id']);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
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

        try {

            $this->tenantsModel->delete($args['tenant_id']);

            $this->response->setStatusCode(204)->send();

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

    }

}