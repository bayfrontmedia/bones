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
use Bayfront\Bones\Services\Api\Models\Relationships\TenantUserRolesModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantRolesCollection;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantUserRolesController extends PrivateApiController implements RelationshipInterface
{

    protected TenantUserRolesModel $tenantUserRolesModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantUserRolesModel $tenantUserRolesModel)
    {
        $this->tenantUserRolesModel = $tenantUserRolesModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Add roles to tenant user.
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
            'tenants.user.roles.add',
            'tenant.user.roles.add'
        ], $args['tenant_id']);

        $ids = $this->getToManyRelationshipIdsOrAbort('tenantRoles');

        try {

            $this->tenantUserRolesModel->add($args['tenant_id'], $args['user_id'], $ids);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10300);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10301);
        }

        $this->response->setStatusCode(204)->send();

    }

    /**
     * Get tenant user roles collection.
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

        if (!$this->user->hasAnyPermissions([
                'global.admin',
                'tenants.user.roles.read',
                'tenant.user.roles.read'
            ], $args['tenant_id']) && $this->user->getId() !== $args['user_id']) {
            App::abort(403, '', [], 10302);
        }

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenantRoles');

        try {

            $results = $this->tenantUserRolesModel->getCollection($args['tenant_id'], $args['user_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10303);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10304);
        }

        $schema = TenantRolesCollection::create($results, [
            'tenant_id' => $args['tenant_id'],
            'collection_prefix' => '/tenants/' . $args['tenant_id'] . '/roles',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Remove roles from tenant user.
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
            'tenants.user.roles.remove',
            'tenant.user.roles.remove'
        ], $args['tenant_id']);

        $ids = $this->getToManyRelationshipIdsOrAbort('tenantRoles');

        try {

            $this->tenantUserRolesModel->remove($args['tenant_id'], $args['user_id'], $ids);

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10305);
        }

        $this->response->setStatusCode(204)->send();

    }

}