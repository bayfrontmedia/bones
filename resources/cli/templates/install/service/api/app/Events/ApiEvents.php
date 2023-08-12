<?php

namespace _namespace_\Events;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Abstracts\EventSubscriber;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Resources\TenantsModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\CronScheduler\Cron;
use Bayfront\CronScheduler\LabelExistsException;
use Bayfront\CronScheduler\SyntaxException;
use Bayfront\MultiLogger\Exceptions\ChannelNotFoundException;
use Bayfront\MultiLogger\Log;
use Bayfront\RouteIt\Router;

/**
 * ApiEvents event subscriber.
 *
 * Created with Bones v_bones_version_
 */
class ApiEvents extends EventSubscriber implements EventSubscriberInterface
{

    protected Cron $scheduler;
    protected Log $log;
    protected Router $router;
    protected Api $api;
    protected UsersModel $usersModel;
    protected TenantsModel $tenantsModel;

    /**
     * The container will resolve any dependencies.
     */

    public function __construct(Cron $scheduler, Log $log, Router $router, Api $api, UsersModel $usersModel, TenantsModel $tenantsModel)
    {
        $this->scheduler = $scheduler;
        $this->log = $log;
        $this->router = $router;
        $this->api = $api;
        $this->usersModel = $usersModel;
        $this->tenantsModel = $tenantsModel;
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
                ],
                [
                    'method' => 'deleteExpiredPasswordTokens',
                    'priority' => 5
                ]
            ],
            'api.authenticate' => [
                [
                    'method' => 'addUserIdToLogs',
                    'priority' => 5
                ]
            ],
            'api.user.verification.create' => [
                [
                    'method' => 'sendVerificationCreateEmail',
                    'priority' => 5
                ]
            ],
            'api.user.verification.success' => [
                [
                    'method' => 'sendVerificationSuccessEmail',
                    'priority' => 5
                ]
            ],
            'api.tenant.invitation.create' => [
                [
                    'method' => 'sendInvitationCreateEmail',
                    'priority' => 5
                ]
            ],
            'api.tenant.users.add' => [
                [
                    'method' => 'sendTenantAddEmail',
                    'priority' => 5
                ]
            ],
            'api.tenant.users.remove' => [
                [
                    'method' => 'sendTenantRemoveEmail',
                    'priority' => 5
                ]
            ],
            'api.password.token.create' => [
                [
                    'method' => 'sendPasswordTokenEmail',
                    'priority' => 5
                ]
            ],
            'api.password.token.updated' => [
                [
                    'method' => 'sendPasswordUpdatedEmail',
                    'priority' => 5
                ]
            ]
        ];

    }

    public function addApiRoutes(): void
    {

        $this->router->

        // ---- Public ----

        // Status

        get('/v1', 'Bayfront\Bones\Services\Api\Controllers\StatusController:index')

            // Verify new user

            ->get('/v1/users/{*:user_id}/verify/{*:verify_id}', 'Bayfront\Bones\Services\Api\Controllers\PublicController:verifyUser')

            // Verify tenant invitation

            ->get('/v1/tenants/{*:tenant_id}/verify/{*:email}', 'Bayfront\Bones\Services\Api\Controllers\PublicController:verifyTenantInvitation')

            // Authentication

            ->post('/v1/auth/login', 'Bayfront\Bones\Services\Api\Controllers\AuthController:login')
            ->post('/v1/auth/refresh', 'Bayfront\Bones\Services\Api\Controllers\AuthController:refresh')

            // Password reset token

            ->post('/v1/auth/password-token', 'Bayfront\Bones\Services\Api\Controllers\AuthController:createPasswordToken')
            ->get('/v1/auth/password-token/{*:user_id}', 'Bayfront\Bones\Services\Api\Controllers\AuthController:passwordTokenExists')
            ->post('/v1/auth/password-update/{*:user_id}', 'Bayfront\Bones\Services\Api\Controllers\AuthController:updatePassword')

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

    /**
     * Delete all expired password reset tokens.
     *
     * @return void
     * @throws LabelExistsException
     * @throws SyntaxException
     */
    public function deleteExpiredPasswordTokens(): void
    {

        $this->scheduler->call('delete-expired-password-tokens', function () {

            $count = $this->api->deleteExpiredPasswordTokens();

            $this->log->info("Deleted expired password tokens", [
                'count' => $count,
                'scheduled_job' => 'delete-expired-password-tokens'
            ]);

        })->hourly();

    }

    private string $user_id;

    /**
     * Add user ID to all log channels.
     *
     * @param array $user
     * @return void
     * @throws ChannelNotFoundException
     */
    public function addUserIdToLogs(array $user): void
    {

        $this->user_id = Arr::get($user, 'id', '');

        $channel_names = $this->log->getChannels();

        foreach ($channel_names as $name) {

            $this->log->getChannel($name)->pushProcessor(function($record) {

                $record['extra']['user_id'] = $this->user_id;
                return $record;

            });

        }

    }

    /**
     * Send email to newly registered users to verify their account.
     *
     * @param string $user_id
     * @param string $verification_id
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function sendVerificationCreateEmail(string $user_id, string $verification_id): void
    {

        $user = $this->usersModel->get($user_id);

        $this->log->info('New user verification email sent', [
            'user_id' => $user_id,
            'email' => $user['email'],
            'verification_id' => $verification_id // NOTE: This should never be logged, but is here as a placeholder example
        ]);

    }

    /**
     * Send email to successfully verified users.
     *
     * @param string $user_id
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function sendVerificationSuccessEmail(string $user_id): void
    {

        $user = $this->usersModel->get($user_id);

        $this->log->info('New user verification success email sent', [
            'user_id' => $user_id,
            'email' => $user['email']
        ]);

    }

    /**
     * Send tenant invitation email to user.
     *
     * @param string $tenant_id
     * @param string $email
     * @param array $invitation
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function sendInvitationCreateEmail(string $tenant_id, string $email, array $invitation): void
    {

        $tenant = $this->tenantsModel->get($tenant_id);

        $user = $this->usersModel->getCollection([
            'where' => [
                'email' => [
                    'eq' => $email
                ]
            ],
            'limit' => 1
        ]);

        if (Arr::get($user, 'meta.count', 0) == 1) { // User exists with email

            $this->log->info('Tenant invitation email sent- user already exists', [
                'tenant' => $tenant,
                'email' => $email,
                'invitation' => $invitation // NOTE: This should never be logged, but is here as a placeholder example
            ]);

        } else {

            $this->log->info('Tenant invitation email sent- user does not yet exist', [
                'tenant' => $tenant,
                'email' => $email,
                'invitation' => $invitation // NOTE: This should never be logged, but is here as a placeholder example
            ]);

        }

    }

    /**
     * Send email to user when added to tenant.
     *
     * @param string $tenant_id
     * @param array $user_ids
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function sendTenantAddEmail(string $tenant_id, array $user_ids): void
    {

        foreach ($user_ids as $user_id) {

            $user = $this->usersModel->get($user_id);

            $this->log->info('Added to tenant email sent', [
                'tenant' => $tenant_id,
                'email' => $user['email']
            ]);

        }

    }

    /**
     * Send email to user when removed from tenant.
     *
     * @param string $tenant_id
     * @param array $user_ids
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function sendTenantRemoveEmail(string $tenant_id, array $user_ids): void
    {

        foreach ($user_ids as $user_id) {

            $user = $this->usersModel->get($user_id);

            $this->log->info('Removed from tenant email sent', [
                'tenant' => $tenant_id,
                'email' => $user['email']
            ]);

        }

    }

    /**
     * Send password reset token email to user.
     *
     * @param array $user
     * @param string $token
     * @return void
     */
    public function sendPasswordTokenEmail(array $user, string $token): void
    {

        $this->log->info('Password reset token email sent', [
            'user' => $user,
            'token' => $token // NOTE: This should never be logged, but is here as a placeholder example
        ]);

    }

    /**
     * Send password reset token email to user.
     *
     * @param array $user
     * @return void
     */
    public function sendPasswordUpdatedEmail(array $user): void
    {

        $this->log->info('Password updated email sent', [
            'user' => $user
        ]);

    }

}