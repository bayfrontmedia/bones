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
use Bayfront\Bones\Services\Api\Models\Relationships\TenantRoleUsersModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\UsersCollection;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantRoleUsersController extends PrivateApiController implements RelationshipInterface
{

    protected TenantRoleUsersModel $tenantRoleUsersModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantRoleUsersModel $tenantRoleUsersModel)
    {
        $this->tenantRoleUsersModel = $tenantRoleUsersModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Add users to tenant role.
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

        $ids = $this->getToManyRelationshipIdsOrAbort('users');

        try {

            $this->tenantRoleUsersModel->add($args['tenant_id'], $args['role_id'], $ids);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10260);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10261);
        }

        $this->response->setStatusCode(204)->send();

    }

    /**
     * Get tenant role users collection.
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
            'tenants.user.roles.read',
            'tenant.user.roles.read'
        ], $args['tenant_id']);

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'users');

        try {

            $results = $this->tenantRoleUsersModel->getCollection($args['tenant_id'], $args['role_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10262);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10263);
        }

        $schema = UsersCollection::create($results, [
            'collection_prefix' => '/users',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Remove users from tenant role.
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

        $ids = $this->getToManyRelationshipIdsOrAbort('users');

        try {

            $this->tenantRoleUsersModel->remove($args['tenant_id'], $args['role_id'], $ids);

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10264);
        }

        $this->response->setStatusCode(204)->send();

    }

}