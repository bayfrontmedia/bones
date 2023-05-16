<?php

namespace Bayfront\Bones\Services\Api\Models\Relationships;

use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Interfaces\ScopedRelationshipInterface;
use Bayfront\Bones\Services\Api\Models\Resources\TenantGroupsModel;
use Bayfront\PDO\Db;
use Monolog\Logger;

class TenantGroupUsersModel extends ApiModel implements ScopedRelationshipInterface
{

    protected TenantGroupsModel $tenantGroupsModel;
    protected TenantUsersModel $tenantUsersModel;

    public function __construct(EventService $events, Db $db, Logger $log, TenantGroupsModel $tenantGroupsModel, TenantUsersModel $tenantUsersModel)
    {
        $this->tenantGroupsModel = $tenantGroupsModel;
        $this->tenantUsersModel = $tenantUsersModel;

        parent::__construct($events, $db, $log);
    }

    public function getSelectableCols(): array
    {
        return $this->tenantUsersModel->getSelectableCols();
    }

    public function getJsonCols(): array
    {
        return $this->tenantUsersModel->getJsonCols();
    }

    public function getCount(string $scoped_id, string $resource_id): int
    {
        // TODO: Implement getCount() method.
    }

    public function has(string $scoped_id, string $resource_id, string $relationship_id): bool
    {
        // TODO: Implement has() method.
    }

    public function add(string $scoped_id, string $resource_id, array $relationship_ids): void
    {
        // TODO: Implement add() method.
    }

    public function getCollection(string $scoped_id, string $resource_id, array $args = []): array
    {
        // TODO: Implement getCollection() method.
    }

    public function remove(string $scoped_id, string $resource_id, array $relationship_ids): void
    {
        // TODO: Implement remove() method.
    }
}