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
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\TenantMetaModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantMetaCollection;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantMetaResource;
use Bayfront\Bones\Services\Api\Schemas\Resources\UserMetaResource;
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

        $attrs = $this->getResourceAttributesOrAbort('tenantMeta', $this->tenantMetaModel->getRequiredAttrs(), $this->tenantMetaModel->getAllowedAttrs());

        try {

            $id = $this->tenantMetaModel->create($args['tenant_id'], $attrs);

            $created = $this->tenantMetaModel->get($args['tenant_id'], $id);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ConflictException $e) {
            App::abort(409, $e->getMessage());
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
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

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenantMeta');

        try {

            $results = $this->tenantMetaModel->getCollection($args['tenant_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
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

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'tenantMeta', array_keys($this->tenantMetaModel->getSelectableCols()));

        try {

            $results = $this->tenantMetaModel->get($args['tenant_id'], $args['meta_id'], $fields);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
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

        $attrs = $this->getResourceAttributesOrAbort('tenantMeta', [], [
            'metaValue'
        ]);

        try {

            $this->tenantMetaModel->update($args['tenant_id'], $args['meta_id'], $attrs);

            $updated = $this->tenantMetaModel->get($args['tenant_id'], $args['meta_id']);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
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

        try {

            $this->tenantMetaModel->delete($args['tenant_id'], $args['meta_id']);

            $this->response->setStatusCode(204)->send();

        } catch (ForbiddenException $e) {
            App::abort(403, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

    }

}