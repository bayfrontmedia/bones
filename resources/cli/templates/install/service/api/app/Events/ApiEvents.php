<?php

namespace _namespace_\Events;

use Bayfront\Bones\Abstracts\EventSubscriber;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\CronScheduler\Cron;
use Bayfront\CronScheduler\LabelExistsException;
use Bayfront\CronScheduler\SyntaxException;
use Bayfront\RouteIt\Router;
use Monolog\Logger;

/**
 * ApiEvents event subscriber.
 *
 * Created with Bones v_bones_version_
 */
class ApiEvents extends EventSubscriber implements EventSubscriberInterface
{

    protected Cron $scheduler;
    protected Logger $log;
    protected Router $router;
    protected Api $api;

    /**
     * The container will resolve any dependencies.
     */

    public function __construct(Cron $scheduler, Logger $log, Router $router, Api $api)
    {
        $this->scheduler = $scheduler;
        $this->log = $log;
        $this->router = $router;
        $this->api = $api;
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptions(): array
    {

        return [
            'app.bootstrap' => [
                [
                    'method' => 'addApiRoutes',
                    'priority' => 10
                ]
            ],
            'app.cli' => [
                [
                    'method' => 'deleteExpiredRefreshTokens',
                    'priority' => 5
                ],
                [
                    'method' => 'deleteExpiredBuckets',
                    'priority' => 5
                ],
                [
                    'method' => 'deleteExpiredInvitations',
                    'priority' => 5
                ],
                [
                    'method' => 'deleteExpiredUserKeys',
                    'priority' => 5
                ]
            ],
            'api.authenticate' => [
                [
                    'method' => 'addUserIdToLogs',
                    'priority' => 5
                ]
            ]
        ];

    }

    public function addApiRoutes(): void
    {

        $this->router->

        // Public

        get('/v1', 'Bayfront\Bones\Services\Api\Controllers\StatusController:index')

            // Authentication

            ->post('/v1/auth/login', 'Bayfront\Bones\Services\Api\Controllers\AuthController:login')
            ->post('/v1/auth/refresh', 'Bayfront\Bones\Services\Api\Controllers\AuthController:refresh')

            // ---- Resources ----

            // Tenant groups

            ->post('/v1/tenants/{*:tenant_id}/groups', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantGroupsController:create')
            ->get('/v1/tenants/{*:tenant_id}/groups', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantGroupsController:getCollection')
            ->get('/v1/tenants/{*:tenant_id}/groups/{*:group_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantGroupsController:get')
            ->patch('/v1/tenants/{*:tenant_id}/groups/{*:group_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantGroupsController:update')
            ->delete('/v1/tenants/{*:tenant_id}/groups/{*:group_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantGroupsController:delete')

            // Tenant invitations

            ->post('/v1/tenants/{*:tenant_id}/invitations', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantInvitationsController:create')
            ->get('/v1/tenants/{*:tenant_id}/invitations', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantInvitationsController:getCollection')
            ->get('/v1/tenants/{*:tenant_id}/invitations/{*:invitation_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantInvitationsController:get')
            ->patch('/v1/tenants/{*:tenant_id}/invitations/{*:invitation_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantInvitationsController:update')
            ->delete('/v1/tenants/{*:tenant_id}/invitations/{*:invitation_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantInvitationsController:delete')

            // Tenant meta

            ->post('/v1/tenants/{*:tenant_id}/meta', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantMetaController:create')
            ->get('/v1/tenants/{*:tenant_id}/meta', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantMetaController:getCollection')
            ->get('/v1/tenants/{*:tenant_id}/meta/{*:meta_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantMetaController:get')
            ->patch('/v1/tenants/{*:tenant_id}/meta/{*:meta_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantMetaController:update')
            ->delete('/v1/tenants/{*:tenant_id}/meta/{*:meta_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantMetaController:delete')

            // Tenant permissions

            ->post('/v1/tenants/{*:tenant_id}/permissions', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantPermissionsController:create')
            ->get('/v1/tenants/{*:tenant_id}/permissions', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantPermissionsController:getCollection')
            ->get('/v1/tenants/{*:tenant_id}/permissions/{*:permission_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantPermissionsController:get')
            ->patch('/v1/tenants/{*:tenant_id}/permissions/{*:permission_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantPermissionsController:update')
            ->delete('/v1/tenants/{*:tenant_id}/permissions/{*:permission_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantPermissionsController:delete')

            // Tenant roles

            ->post('/v1/tenants/{*:tenant_id}/roles', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantRolesController:create')
            ->get('/v1/tenants/{*:tenant_id}/roles', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantRolesController:getCollection', ['collection_prefix' => '/v1/tenants/{tenant_id}/roles'])
            ->get('/v1/tenants/{*:tenant_id}/roles/{*:role_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantRolesController:get')
            ->patch('/v1/tenants/{*:tenant_id}/roles/{*:role_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantRolesController:update')
            ->delete('/v1/tenants/{*:tenant_id}/roles/{*:role_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantRolesController:delete')

            // Tenants

            ->post('/v1/tenants', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantsController:create')
            ->get('/v1/tenants', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantsController:getCollection')
            ->get('/v1/tenants/{*:tenant_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantsController:get')
            ->patch('/v1/tenants/{*:tenant_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantsController:update')
            ->delete('/v1/tenants/{*:tenant_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantsController:delete')

            // Tenant user meta

            ->post('/v1/tenants/{*:tenant_id}/users/{*:user_id}/meta', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantUserMetaController:create')
            ->get('/v1/tenants/{*:tenant_id}/users/{*:user_id}/meta', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantUserMetaController:getCollection')
            ->get('/v1/tenants/{*:tenant_id}/users/{*:user_id}/meta/{*:meta_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantUserMetaController:get')
            ->patch('/v1/tenants/{*:tenant_id}/users/{*:user_id}/meta/{*:meta_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantUserMetaController:update')
            ->delete('/v1/tenants/{*:tenant_id}/users/{*:user_id}/meta/{*:meta_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\TenantUserMetaController:delete')

            // User keys

            ->post('/v1/users/{*:user_id}/keys', 'Bayfront\Bones\Services\Api\Controllers\Resources\UserKeysController:create')
            ->get('/v1/users/{*:user_id}/keys', 'Bayfront\Bones\Services\Api\Controllers\Resources\UserKeysController:getCollection')
            ->get('/v1/users/{*:user_id}/keys/{*:key_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\UserKeysController:get')
            ->patch('/v1/users/{*:user_id}/keys/{*:key_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\UserKeysController:update')
            ->delete('/v1/users/{*:user_id}/keys/{*:key_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\UserKeysController:delete')

            // User meta

            ->post('/v1/users/{*:user_id}/meta', 'Bayfront\Bones\Services\Api\Controllers\Resources\UserMetaController:create')
            ->get('/v1/users/{*:user_id}/meta', 'Bayfront\Bones\Services\Api\Controllers\Resources\UserMetaController:getCollection')
            ->get('/v1/users/{*:user_id}/meta/{*:meta_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\UserMetaController:get')
            ->patch('/v1/users/{*:user_id}/meta/{*:meta_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\UserMetaController:update')
            ->delete('/v1/users/{*:user_id}/meta/{*:meta_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\UserMetaController:delete')

            // Users

            ->post('/v1/users', 'Bayfront\Bones\Services\Api\Controllers\PublicController:createUser')
            ->get('/v1/users', 'Bayfront\Bones\Services\Api\Controllers\Resources\UsersController:getCollection')
            ->get('/v1/users/{*:user_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\UsersController:get')
            ->patch('/v1/users/{*:user_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\UsersController:update')
            ->delete('/v1/users/{*:user_id}', 'Bayfront\Bones\Services\Api\Controllers\Resources\UsersController:delete')
            ->get('/v1/users/{*:user_id}/verify/{*:verify_id}', 'Bayfront\Bones\Services\Api\Controllers\PublicController:verifyUser')

            // ---- Relationships

            // Tenant group users

            ->post('/v1/tenants/{*:tenant_id}/groups/{*:group_id}/users', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantGroupUsersController:add')
            ->get('/v1/tenants/{*:tenant_id}/groups/{*:group_id}/users', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantGroupUsersController:getCollection')
            ->delete('/v1/tenants/{*:tenant_id}/groups/{*:group_id}/users', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantGroupUsersController:remove')

            // Tenant permission roles

            ->post('/v1/tenants/{*:tenant_id}/permissions/{*:permission_id}/roles', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantPermissionRolesController:add')
            ->get('/v1/tenants/{*:tenant_id}/permissions/{*:permission_id}/roles', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantPermissionRolesController:getCollection')
            ->delete('/v1/tenants/{*:tenant_id}/permissions/{*:permission_id}/roles', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantPermissionRolesController:remove')

            // Tenant role permissions

            ->post('/v1/tenants/{*:tenant_id}/roles/{*:role_id}/permissions', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantRolePermissionsController:add')
            ->get('/v1/tenants/{*:tenant_id}/roles/{*:role_id}/permissions', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantRolePermissionsController:getCollection')
            ->delete('/v1/tenants/{*:tenant_id}/roles/{*:role_id}/permissions', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantRolePermissionsController:remove')

            // Tenant role users

            ->post('/v1/tenants/{*:tenant_id}/roles/{*:role_id}/users', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantRoleUsersController:add')
            ->get('/v1/tenants/{*:tenant_id}/roles/{*:role_id}/users', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantRoleUsersController:getCollection')
            ->delete('/v1/tenants/{*:tenant_id}/roles/{*:role_id}/users', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantRoleUsersController:remove')

            // Tenant user groups

            ->post('/v1/tenants/{*:tenant_id}/users/{*:user_id}/groups', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantUserGroupsController:add')
            ->get('/v1/tenants/{*:tenant_id}/users/{*:user_id}/groups', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantUserGroupsController:getCollection')
            ->delete('/v1/tenants/{*:tenant_id}/users/{*:user_id}/groups', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantUserGroupsController:remove')

            // Tenant user roles

            ->post('/v1/tenants/{*:tenant_id}/users/{*:user_id}/roles', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantUserRolesController:add')
            ->get('/v1/tenants/{*:tenant_id}/users/{*:user_id}/roles', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantUserRolesController:getCollection')
            ->delete('/v1/tenants/{*:tenant_id}/users/{*:user_id}/roles', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantUserRolesController:remove')

            // Tenant users

            ->post('/v1/tenants/{*:tenant_id}/users', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantUsersController:add')
            ->get('/v1/tenants/{*:tenant_id}/users', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantUsersController:getCollection')
            ->delete('/v1/tenants/{*:tenant_id}/users', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantUsersController:remove')
            ->get('/v1/tenants/{*:tenant_id}/users/{*:user_id}/permissions', 'Bayfront\Bones\Services\Api\Controllers\Relationships\TenantUsersController:getPermissionCollection')
            ->get('/v1/tenants/{*:tenant_id}/verify/{*:email}', 'Bayfront\Bones\Services\Api\Controllers\PublicController:verifyTenantInvitation')

            // User tenants

            ->post('/v1/users/{*:user_id}/tenants', 'Bayfront\Bones\Services\Api\Controllers\Relationships\UserTenantsController:add')
            ->get('/v1/users/{*:user_id}/tenants', 'Bayfront\Bones\Services\Api\Controllers\Relationships\UserTenantsController:getCollection')
            ->delete('/v1/users/{*:user_id}/tenants', 'Bayfront\Bones\Services\Api\Controllers\Relationships\UserTenantsController:remove');

    }

    /**
     * Delete all expired refresh tokens.
     *
     * @return void
     * @throws LabelExistsException
     * @throws SyntaxException
     */
    public function deleteExpiredRefreshTokens(): void
    {

        $this->scheduler->call('delete-expired-refresh-tokens', function () {

            $count = $this->api->deleteExpiredRefreshTokens();

            $this->log->info("Deleted expired refresh tokens", [
                'count' => $count,
                'scheduled_job' => 'delete-expired-refresh-tokens'
            ]);

        })->daily();

    }

    /**
     * Delete all expired buckets.
     *
     * @return void
     * @throws LabelExistsException
     * @throws SyntaxException
     */
    public function deleteExpiredBuckets(): void
    {

        $this->scheduler->call('delete-expired-buckets', function () {

            $count = $this->api->deleteExpiredBuckets();

            $this->log->info("Deleted expired buckets", [
                'count' => $count,
                'scheduled_job' => 'delete-expired-buckets'
            ]);

        })->everyHours(6);

    }

    /**
     * Delete all expired tenant invitations.
     *
     * @return void
     * @throws LabelExistsException
     * @throws SyntaxException
     */
    public function deleteExpiredInvitations(): void
    {

        $this->scheduler->call('delete-expired-tenant-invitations', function () {

            $count = $this->api->deleteExpiredTenantInvitations();

            $this->log->info("Deleted expired tenant invitations", [
                'count' => $count,
                'scheduled_job' => 'delete-expired-tenant-invitations'
            ]);

        })->weekly();

    }

    /**
     * Delete all expired user keys.
     *
     * @return void
     * @throws LabelExistsException
     * @throws SyntaxException
     */
    public function deleteExpiredUserKeys(): void
    {

        $this->scheduler->call('delete-expired-user-keys', function () {

            $count = $this->api->deleteExpiredUserKeys();

            $this->log->info("Deleted expired user keys", [
                'count' => $count,
                'scheduled_job' => 'delete-expired-user-keys'
            ]);

        })->weekly();

    }

    private string $user_id;

    public function addUserIdToLogs(string $user_id): void
    {

        $this->user_id = $user_id;

        $this->log->pushProcessor(function ($record) {

            $record['extra']['user_id'] = $this->user_id;

            return $record;

        });

    }

}