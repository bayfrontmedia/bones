<?php

namespace Bayfront\Bones\Services\Api\Models\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Abstracts\Models\ApiModel;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Exceptions\ForbiddenException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Models\Interfaces\ScopedResourceInterface;
use Bayfront\Bones\Services\Api\Models\Relationships\TenantUsersModel;
use Bayfront\Bones\Services\Api\Utilities\Api;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\Validator\Validate;
use Monolog\Logger;

class TenantInvitationsModel extends ApiModel implements ScopedResourceInterface
{

    protected TenantsModel $tenantsModel;
    protected TenantRolesModel $tenantRolesModel;
    protected TenantUsersModel $tenantUsersModel;
    protected UsersModel $usersModel;

    public function __construct(EventService $events, Db $db, Logger $log, TenantsModel $tenantsModel, TenantRolesModel $tenantRolesModel, TenantUsersModel $tenantUsersModel, UsersModel $usersModel)
    {
        $this->tenantsModel = $tenantsModel;
        $this->tenantRolesModel = $tenantRolesModel;
        $this->tenantUsersModel = $tenantUsersModel;
        $this->usersModel = $usersModel;

        parent::__construct($events, $db, $log);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredAttrs(): array
    {
        return [
            'email',
            'roleId'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAllowedAttrs(): array
    {
        return [
            'email',
            'roleId'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAttrsRules(): array
    {
        return [
            'email' => 'email',
            'roleId' => 'uuid'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSelectableCols(): array
    {
        return [
            'email' => 'email',
            //'tenantId' => 'BIN_TO_UUID(tenantId, 1) as tenantId',
            'roleId' => 'BIN_TO_UUID(roleId, 1) as roleId',
            'expiresAt' => 'expiresAt',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getJsonCols(): array
    {
        return [];
    }

    /**
     * Get tenant invitation count.
     *
     * @param string $scoped_id
     * @return int
     */
    public function getCount(string $scoped_id): int
    {

        if (!Validate::uuid($scoped_id)) {
            return 0;
        }

        return $this->db->single("SELECT COUNT(*) FROM api_tenant_invitations WHERE tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'tenant_id' => $scoped_id
        ]);

    }

    /**
     * Does tenant invitation exist?
     *
     * @param string $scoped_id
     * @param string $id
     * @return bool
     */
    public function idExists(string $scoped_id, string $id): bool
    {

        if (!Validate::uuid($scoped_id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_tenant_invitations WHERE email = :email AND tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'email' => $id,
            'tenant_id' => $scoped_id
        ]);

    }

    /**
     * Verify new tenant invitation and add user to tenant if valid.
     *
     * @param string $tenant_id
     * @param string $email
     * @return bool
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function verifyTenantInvitation(string $tenant_id, string $email): bool
    {

        // Get invitation

        $invitation = $this->get($tenant_id, $email);

        // Verify not expired

        if (strtotime($invitation['expiresAt']) <= time()) {

            $this->delete($tenant_id, $email);

            $msg = 'Unable to verify tenant invitation';
            $reason = 'Tenant invitation expired';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $tenant_id,
                'invitation_id' => $email
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Get user if existing

        try {

            $user = $this->usersModel->getEntireFromEmail($email);

        } catch (NotFoundException) {

            // Valid invitation, but user does not yet exist

            $msg = 'Unable to verify tenant invitation';
            $reason = 'User does not exist with email';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $tenant_id,
                'invitation_id' => $email
            ]);

            throw new ConflictException($msg . ': ' . $reason);

        }

        // Check enabled

        if (!$user['enabled']) {

            //$this->delete($tenant_id, $email);

            $msg = 'Unable to verify tenant invitation';
            $reason = 'User disabled';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $tenant_id,
                'invitation_id' => $email
            ]);

            throw new ForbiddenException($msg . ': ' . $reason);

        }

        /*
         * TODO:
         * Finish this once TenantUserRolesModel is completed
         */

        return false;

    }

    /**
     * Create tenant invitation.
     *
     * @param string $scoped_id
     * @param array $attrs
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws NotFoundException
     */
    public function create(string $scoped_id, array $attrs): string
    {

        // Scoped exists

        if (!$this->tenantsModel->idExists($scoped_id)) {

            $msg = 'Unable to create tenant invitation';
            $reason = 'Tenant ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Required attributes

        if (Arr::isMissing($attrs, $this->getRequiredAttrs())) {

            $msg = 'Unable to create tenant invitation';
            $reason = 'Missing required attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to create tenant invitation';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to create tenant invitation';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Role ID exists

        if (!$this->tenantRolesModel->idExists($scoped_id, $attrs['roleId'])) {

            $msg = 'Unable to create tenant invitation';
            $reason = 'Role ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'role_id' => $attrs['roleId']
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Check email exists

        $attrs['email'] = strtolower($attrs['email']);

        if ($this->idExists($scoped_id, $attrs['email'])) {

            $msg = 'Unable to create tenant invitation';
            $reason = 'Invitation already exists for email (' . $attrs['email'] . ')';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'role_id' => $attrs['roleId']
            ]);

            throw new ConflictException($msg . ': ' . $reason);

        }

        // Check email in tenant

        if ($this->tenantUsersModel->hasEmail($scoped_id, $attrs['email'])) {

            $msg = 'Unable to create tenant invitation';
            $reason = 'Email (' . $attrs['email'] . ') already exists in tenant';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'role_id' => $attrs['roleId']
            ]);

            throw new ConflictException($msg . ': ' . $reason);

        }

        // Create

        $attrs['roleId'] = $this->UUIDtoBIN($attrs['roleId']);

        $attrs['tenantId'] = $this->UUIDtoBIN($scoped_id);
        $attrs['expiresAt'] = date('Y-m-d H:i:s', time() + (App::getConfig('api.invitation_duration') * 60)); // Convert seconds to minutes

        $this->db->insert('api_tenant_invitations', $attrs);

        // Log

        if (in_array(Api::ACTION_CREATE, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant invitation created', [
                'tenant_id' => $scoped_id,
                'invitation_id' => $attrs['email']
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.invitation.create', $scoped_id, $attrs['email'], Arr::only($attrs, array_keys($this->getSelectableCols())));

        return $attrs['email'];

    }

    /**
     * Create tenant invitation.
     *
     * @param string $scoped_id
     * @param array $args
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function getCollection(string $scoped_id, array $args = []): array
    {

        if (empty($args['select'])) {
            $args['select'][] = '*';
        } else {
            $args['select'] = array_merge($args['select'], ['email']); // Force return email
        }

        // Scoped exists

        if (!$this->tenantsModel->idExists($scoped_id)) {

            $msg = 'Unable to get tenant invitation collection';
            $reason = 'Tenant ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        try {

            $query = $this->startNewQuery()->table('api_tenant_invitations')
                ->where('tenantId', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $results = $this->queryCollection($query, $args, $this->getSelectableCols(), 'email', $args['limit'], $this->getJsonCols());

        } catch (BadRequestException $e) {

            $msg = 'Unable to get tenant invitation collection';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant invitation read', [
                'tenant_id' => $scoped_id,
                'invitation_id' => Arr::pluck($results['data'], 'email')
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.invitation.read', $scoped_id, Arr::pluck($results['data'], 'email'));

        return $results;

    }

    /**
     * Get tenant invitation.
     *
     * @param string $scoped_id
     * @param string $id
     * @param array $cols
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function get(string $scoped_id, string $id, array $cols = ['*']): array
    {

        if (empty($cols)) {
            $cols[] = '*';
        } else {
            $cols = array_merge($cols, ['email']); // Force return email
        }

        // Exists

        if (!$this->idExists($scoped_id, $id)) {

            $msg = 'Unable to get tenant invitation';
            $reason = 'Tenant and / or invitation ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'invitation_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Query

        $query = $this->startNewQuery()->table('api_tenant_invitations');

        try {

            $query->where('email', 'eq', $id)
                ->where('tenantID', 'eq', "UUID_TO_BIN('" . $scoped_id . "', 1)");

        } catch (QueryException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            $result = $this->querySingle($query, $cols, $this->getSelectableCols(), $this->getJsonCols());

        } catch (BadRequestException|NotFoundException $e) {

            $msg = 'Unable to get tenant invitation';

            $this->log->notice($msg, [
                'reason' => $e->getMessage(),
                'tenant_id' => $scoped_id,
                'invitation_id' => $id
            ]);

            throw $e;

        }

        // Log

        if (in_array(Api::ACTION_READ, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant invitation read', [
                'tenant_id' => $scoped_id,
                'invitation_id' => [$result['email']]
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.invitation.read', $scoped_id, [$result['email']]);

        return $result;

    }

    /**
     * Update tenant invitation.
     *
     * @param string $scoped_id
     * @param string $id
     * @param array $attrs
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     */
    public function update(string $scoped_id, string $id, array $attrs): void
    {

        if (empty($attrs)) { // Nothing to update
            return;
        }

        // Exists

        if (!$this->idExists($scoped_id, $id)) {

            $msg = 'Unable to update tenant invitation';
            $reason = 'Tenant and / or invitation ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'invitation_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Allowed attributes

        // Cannot update email as it is used as the unique ID of this resource

        if (!empty(Arr::except($attrs, Arr::except($this->getAllowedAttrs(), 'email')))) {

            $msg = 'Unable to update tenant invitation';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'invitation_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        if (!Validate::as($attrs, $this->getAttrsRules())) {

            $msg = 'Unable to update tenant invitation';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'invitation_id' => $id
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Role ID exists

        if (isset($attrs['roleId'])) {

            if (!$this->tenantRolesModel->idExists($scoped_id, $attrs['roleId'])) {

                $msg = 'Unable to update tenant invitation';
                $reason = 'Role ID does not exist';

                $this->log->notice($msg, [
                    'reason' => $reason,
                    'tenant_id' => $scoped_id,
                    'invitation_id' => $id,
                    'role_id' => $attrs['roleId']
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

            $attrs['roleId'] = $this->UUIDtoBIN($attrs['roleId']);

        }

        // Update

        $placeholders = [];

        /** @noinspection SqlWithoutWhere */
        $query = "UPDATE api_tenant_invitations SET" . " ";

        foreach ($attrs as $k => $v) {
            $placeholders[] = $v;
            $query .= $k . ' = ? ';
        }

        $query .= "WHERE email = ? AND tenantId = UUID_TO_BIN(?, 1)";
        array_push($placeholders, $id, $scoped_id);

        $this->db->query($query, $placeholders);

        // Log

        if (in_array(Api::ACTION_UPDATE, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant invitation updated', [
                'tenant_id' => $scoped_id,
                'invitation_id' => $id
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.invitation.update', $scoped_id, $id, Arr::only($attrs, array_keys($this->getSelectableCols())));

    }

    /**
     * Delete tenant invitation.
     *
     * @param string $scoped_id
     * @param string $id
     * @return void
     * @throws NotFoundException
     */
    public function delete(string $scoped_id, string $id): void
    {

        if (!$this->idExists($scoped_id, $id)) {

            $msg = 'Unable to delete tenant invitation';
            $reason = 'Tenant and / or invitation ID does not exist';

            $this->log->notice($msg, [
                'reason' => $reason,
                'tenant_id' => $scoped_id,
                'invitation_id' => $id
            ]);

            throw new NotFoundException($msg . ': ' . $reason);

        }

        // Delete

        $this->db->query("DELETE FROM api_tenant_invitations WHERE email = :email AND tenantId = UUID_TO_BIN(:tenant_id, 1)", [
            'email' => $id,
            'tenant_id' => $scoped_id
        ]);

        // Log

        if (in_array(Api::ACTION_DELETE, App::getConfig('api.log_actions'))) {

            $this->log->info('Tenant invitation deleted', [
                'tenant_id' => $scoped_id,
                'invitation_id' => $id
            ]);

        }

        // Event

        $this->events->doEvent('api.tenant.invitation.delete', $scoped_id, $id);

    }

}