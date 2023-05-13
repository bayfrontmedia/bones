<?php

namespace Bayfront\Bones\Services\Api\Models\Resources;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Abstracts\Models\ApiModel;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\ConflictException;
use Bayfront\Bones\Services\Api\Models\Interfaces\ResourceInterface;
use Bayfront\Bones\Services\Api\Models\Relationships\TenantUsersModel;
use Bayfront\PDO\Db;
use Bayfront\Validator\Validate;
use Bayfront\Validator\ValidationException;
use Exception;
use Monolog\Logger;

class TenantsModel extends ApiModel implements ResourceInterface
{

    protected TenantUsersModel $tenantUsersModel;

    public function __construct(EventService $events, Db $db, Logger $log, TenantUsersModel $tenantUsersModel)
    {
        $this->tenantUsersModel = $tenantUsersModel;

        parent::__construct($events, $db, $log);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredAttrs(): array
    {
        return [
            'owner',
            'name'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAllowedAttrs(): array
    {
        return [
            'owner',
            'name',
            'meta',
            'enabled'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAttrsRules(): array
    {
        return [
            'name' => 'string',
            'meta' => 'array',
            'enabled' => 'boolean'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSelectableCols(): array
    {
        return [
            'id' => 'BIN_TO_UUID(id, 1) as id',
            'owner' => 'BIN_TO_UUID(owner, 1) as owner',
            'name' => 'name',
            'meta' => 'meta',
            'enabled' => 'enabled',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getJsonCols(): array
    {
        return [
            'meta'
        ];
    }

    /**
     * Get tenant count.
     *
     * @inheritDoc
     */
    public function getCount(): int
    {
        return $this->db->single("SELECT COUNT(*) FROM api_tenants");
    }

    /**
     * Does tenant ID exist?
     *
     * @inheritDoc
     */
    public function idExists(string $id): bool
    {

        if (!Validate::uuid($id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_tenants WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

    }

    /**
     * Does tenant name exist?
     *
     * @param string $name
     * @param string $exclude_id
     * @return bool
     */
    public function nameExists(string $name, string $exclude_id = ''): bool
    {

        if ($exclude_id == '') {

            return (bool)$this->db->single("SELECT 1 FROM api_tenants WHERE name = :name", [
                'name' => $name
            ]);

        }

        if (!Validate::uuid($exclude_id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT 1 FROM api_tenants WHERE name = :name AND id != UUID_TO_BIN(:id, 1)", [
            'name' => $name,
            'id' => $exclude_id
        ]);

    }

    /**
     * Is tenant enabled?
     *
     * @param string $id
     * @return bool
     */
    public function isEnabled(string $id): bool
    {

        if (!Validate::uuid($id)) {
            return false;
        }

        return (bool)$this->db->single("SELECT enabled FROM api_tenants WHERE id = UUID_TO_BIN(:id, 1)", [
            'id' => $id
        ]);

    }

    /**
     * Create tenant.
     *
     * @param array $attrs
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     */
    public function create(array $attrs): string
    {

        // Required attributes

        if (Arr::isMissing($attrs, $this->getRequiredAttrs())) {

            $msg = 'Unable to create tenant';
            $reason = 'Missing required attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Allowed attributes

        if (!empty(Arr::except($attrs, $this->getAllowedAttrs()))) {

            $msg = 'Unable to create tenant';
            $reason = 'Invalid attribute(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Attribute rules

        try {

            Validate::as($attrs, $this->getAttrsRules());

        } catch (ValidationException) {

            $msg = 'Unable to create tenant';
            $reason = 'Invalid attribute type(s)';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        // Validate meta

        if (isset($attrs['meta'])) {

            try {

                Validate::as($attrs['meta'], App::getConfig('api.required_meta.tenants'), true);

            } catch (ValidationException) {

                $msg = 'Unable to create tenant';
                $reason = 'Missing or invalid meta attribute(s)';

                $this->log->notice($msg, [
                    'reason' => $reason
                ]);

                throw new BadRequestException($msg . ': ' . $reason);

            }

            $attrs['meta'] = $this->encodeMeta($attrs['meta']);

        }

        // Check exists

        if ($this->nameExists($attrs['name'])) {

            $msg = 'Unable to create tenant';
            $reason = 'Name (' . $attrs['name'] . ') already exists';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new ConflictException($msg . ': ' . $reason);

        }

        $owner_id = $attrs['owner']; // Needed to addTenantToUsers

        $attrs['owner'] = $this->UUIDtoBIN($attrs['owner']);

        $uuid = $this->createUUID();

        $attrs['id'] = $uuid['bin'];

        // Create

        try {

            $this->db->beginTransaction();

            $this->db->insert('api_tenants', $attrs);

            // Add owner as tenant user

            $this->tenantUsersModel->add($uuid['str'], [
                $owner_id
            ]);

        } catch (Exception $e) {

            $this->db->rollbackTransaction();

            $msg = 'Unable to create tenant';
            $reason = 'Owner ID (' . $owner_id . ') does not exist';

            $this->log->notice($msg, [
                'reason' => $reason
            ]);

            throw new BadRequestException($msg . ': ' . $reason);

        }

        return $uuid['str'];

    }

    public function getCollection(array $args = []): array
    {
        // TODO: Implement getCollection() method.
        return [];
    }

    public function get(string $id, array $cols = ['*']): array
    {
        // TODO: Implement get() method.
        return [];
    }

    public function update(string $id, array $attrs): void
    {
        // TODO: Implement update() method.
        return;
    }

    public function delete(string $id): void
    {
        // TODO: Implement delete() method.
        return;
    }
}