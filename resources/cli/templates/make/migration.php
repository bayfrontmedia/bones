<?php

use Bayfront\Bones\Interfaces\MigrationInterface;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\QueryException;

/**
 * _migration_name_ Migration.
 *
 * Created with Bones v_bones_version_
 */
class _migration_name_ implements MigrationInterface
{

    protected Db $db;

    /**
     * The container will resolve any dependencies.
     */

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */

    public function up(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `TABLE_NAME` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `dateAdded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `dateModified` datetime DEFAULT NULL
        )");
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */

    public function down(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `TABLE_NAME`");
    }
}