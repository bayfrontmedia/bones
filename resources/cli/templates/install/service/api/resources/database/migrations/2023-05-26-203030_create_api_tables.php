<?php

use Bayfront\Bones\Interfaces\MigrationInterface;
use Bayfront\Bones\Services\Api\Migrations\CreateApiTables;
use Bayfront\LeakyBucket\AdapterException;

/**
 * Create tables necessary for the Bones API service.
 *
 * Created with Bones v_bones_version_
 */
class create_api_tables implements MigrationInterface
{

    protected CreateApiTables $apiTables;

    /**
     * The container will resolve any dependencies.
     */

    public function __construct(CreateApiTables $apiTables)
    {
        $this->apiTables = $apiTables;
    }

    /**
     * @inheritDoc
     * @throws AdapterException
     */
    public function up(): void
    {
        $this->apiTables->up();
    }

    /**
     * @inheritDoc
     * @throws AdapterException
     */
    public function down(): void
    {
        $this->apiTables->down();
    }

}