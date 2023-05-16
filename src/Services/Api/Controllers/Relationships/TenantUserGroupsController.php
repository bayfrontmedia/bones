<?php

namespace Bayfront\Bones\Services\Api\Controllers\Relationships;

use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Controllers\Abstracts\ApiController;
use Bayfront\Bones\Services\Api\Controllers\Interfaces\RelationshipInterface;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Relationships\TenantUserGroupsModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantGroupsCollection;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantUserGroupsController extends ApiController implements RelationshipInterface
{

    protected TenantUserGroupsModel $tenantUserGroupsModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantUserGroupsModel $tenantUserGroupsModel)
    {
        $this->tenantUserGroupsModel = $tenantUserGroupsModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Add user to tenant groups.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws UnexpectedApiException
     * @throws InvalidStatusCodeException
     */
    public function add(array $args): void
    {

        $ids = $this->getToManyRelationshipIdsOrAbort('tenantGroups');

        try {

            $this->tenantUserGroupsModel->add($args['tenant_id'], $args['user_id'], $ids);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $this->response->setStatusCode(204)->send();

    }

    /**
     * Get tenant user groups collection.
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

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenantGroups');

        try {

            $results = $this->tenantUserGroupsModel->getCollection($args['tenant_id'], $args['user_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage());
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

        $schema = TenantGroupsCollection::create($results, [
            'tenant_id' => $args['tenant_id'],
            'collection_prefix' => '/tenants/' . $args['tenant_id'] . '/groups',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Remove user from tenant groups.
     *
     * @param array $args
     * @return void
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function remove(array $args): void
    {

        $ids = $this->getToManyRelationshipIdsOrAbort('tenantGroups');

        try {

            $this->tenantUserGroupsModel->remove($args['tenant_id'], $args['user_id'], $ids);

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage());
        }

    }

}