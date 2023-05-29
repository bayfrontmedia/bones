<?php

namespace Bayfront\Bones\Services\Api\Controllers\Relationships;

use Bayfront\ArrayHelpers\Arr;
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
use Bayfront\Bones\Services\Api\Models\Relationships\TenantRolePermissionsModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantMetaModel;
use Bayfront\Bones\Services\Api\Schemas\Resources\TenantPermissionsCollection;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class TenantRolePermissionsController extends PrivateApiController implements RelationshipInterface
{

    protected TenantRolePermissionsModel $tenantRolePermissionsModel;
    protected TenantMetaModel $tenantMetaModel;

    public function __construct(EventService $events, FilterService $filters, Response $response, TenantRolePermissionsModel $tenantRolePermissionsModel, TenantMetaModel $tenantMetaModel)
    {
        $this->tenantRolePermissionsModel = $tenantRolePermissionsModel;
        $this->tenantMetaModel = $tenantMetaModel;

        parent::__construct($events, $filters, $response);
    }

    /**
     * Add permissions to tenant role.
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

        if (!$this->user->hasAnyPermissions([
            'global.admin',
            'tenants.role.permissions.add'
        ])) {

            $plan_roles = $this->tenantMetaModel->getValue($args['tenant_id'], '00-plan-roles', true);

            if ($plan_roles) {

                $plan_roles = json_decode($plan_roles, true);

                if (in_array($args['role_id'], Arr::pluck($plan_roles, 'id'))) {
                    App::abort(403, 'Unable to add permissions to tenant role: Role is protected', [], 10240);
                }

            }

        }

        $ids = $this->getToManyRelationshipIdsOrAbort('tenantPermissions');

        try {

            $this->tenantRolePermissionsModel->add($args['tenant_id'], $args['role_id'], $ids);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10241);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10242);
        }

        $this->response->setStatusCode(204)->send();

    }

    /**
     * Get tenant role permissions collection.
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

        $query = $this->parseCollectionQueryOrAbort(Request::getQuery(), 'tenantPermissions');

        try {

            $results = $this->tenantRolePermissionsModel->getCollection($args['tenant_id'], $args['role_id'], $query);

        } catch (BadRequestException $e) {
            App::abort(400, $e->getMessage(), [], 10243);
        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10244);
        }

        $schema = TenantPermissionsCollection::create($results, [
            'tenant_id' => $args['tenant_id'],
            'collection_prefix' => '/tenants/' . $args['tenant_id'] . '/permissions',
            'query_string' => Request::getQuery()
        ]);

        $this->response->setStatusCode(200)->sendJson($this->filters->doFilter('api.response', $schema));

    }

    /**
     * Remove permissions from tenant role.
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

        if (!$this->user->hasAnyPermissions([
            'global.admin',
            'tenants.role.permissions.remove'
        ])) {

            $plan_roles = $this->tenantMetaModel->getValue($args['tenant_id'], '00-plan-roles', true);

            if ($plan_roles) {

                $plan_roles = json_decode($plan_roles, true);

                if (in_array($args['role_id'], Arr::pluck($plan_roles, 'id'))) {
                    App::abort(403, 'Unable to remove permissions from tenant role: Role is protected', [], 10245);
                }

            }

        }

        $ids = $this->getToManyRelationshipIdsOrAbort('tenantPermissions');

        try {

            $this->tenantRolePermissionsModel->remove($args['tenant_id'], $args['role_id'], $ids);

        } catch (NotFoundException $e) {
            App::abort(404, $e->getMessage(), [], 10246);
        }

        $this->response->setStatusCode(204)->send();

    }

}