<?php

namespace Bayfront\Bones\Services\Api\Controllers\Relationships;

use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Controllers\Abstracts\PrivateApiController;
use Bayfront\Bones\Services\Api\Controllers\Interfaces\RelationshipInterface;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Relationships\TenantPermissionRolesModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantRolesCollection;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantPermissionRolesController extends PrivateApiController implements RelationshipInterface
{

    protected TenantPermissionRolesModel $tenantPermissionRolesModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantPermissionRolesModel $tenantPermissionRolesModel)
    {
        $this->tenantPermissionRolesModel = $tenantPermissionRolesModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Add permission to tenant roles.
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

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.role.permissions.add',
            'tenant.role.permissions.add'
        ], $args['tenant_id']);

        $ids = $this->getToManyRelationshipIdsOrAbort('tenantRoles');

        try {

            $this->tenantPermissionRolesModel->add($args['tenant_id'], $args['permission_id'], $ids);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10220);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10221);
        }

        $this->response->setStatusCode(204)->send();

    }

    /**
     * Get tenant permission roles collection.
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
            'tenants.role.permissions.read',
            'tenant.role.permissions.read'
        ], $args['tenant_id']);

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenantRoles');

        try {

            $results = $this->tenantPermissionRolesModel->getCollection($args['tenant_id'], $args['permission_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10222);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10223);
        }

        $schema = TenantRolesCollection::create($results, [
            'tenant_id' => $args['tenant_id'],
            'collection_prefix' => '/tenants/' . $args['tenant_id'] . '/roles',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Remove permission from tenant roles.
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

        $this->canDoAnyOrAbort([
            'global.admin',
            'tenants.role.permissions.remove',
            'tenant.role.permissions.remove'
        ], $args['tenant_id']);

        $ids = $this->getToManyRelationshipIdsOrAbort('tenantRoles');

        try {

            $this->tenantPermissionRolesModel->remove($args['tenant_id'], $args['permission_id'], $ids);

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10224);
        }

        $this->response->setStatusCode(204)->send();

    }

}