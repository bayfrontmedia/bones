<?php

namespace Bayfront\Bones\Services\Api\Migrations;

use Bayfront\Bones\Interfaces\MigrationInterface;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\LeakyBucket\AdapterException;
use Bayfront\LeakyBucket\Adapters\PDO;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\InvalidDatabaseException;

class CreateApiTables implements MigrationInterface
{

    protected Db $db;
    protected PDO $bucketAdapter;


    /**
     * @param Db $db
     * @throws UnexpectedApiException
     */
    public function __construct(Db $db)
    {
        $this->db = $db;

        try {
            $this->bucketAdapter = new PDO($this->db->get(), 'api_buckets');
        } catch (InvalidDatabaseException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

    }

    /**
     * @inheritDoc
     * @throws AdapterException
     */
    public function up(): void
    {

        $this->bucketAdapter->up();

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_users` (
            `id` binary(16) NOT NULL,
            `email` varchar(255) NOT NULL,
            `password` varchar(255) NOT NULL,
            `salt` varchar(32) NOT NULL,
            `meta` JSON NULL DEFAULT NULL,
            `enabled` tinyint NOT NULL DEFAULT '0',
            `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`),
            UNIQUE (`email`)) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_user_keys` (
            `id` varchar(7) NOT NULL,
            `userId` binary(16) NOT NULL,
            `keyValue` varchar(255) NOT NULL,
            `description` text DEFAULT NULL,
            `allowedDomains` JSON NULL DEFAULT NULL,
            `allowedIps` JSON NULL DEFAULT NULL,
            `expiresAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `lastUsed` datetime NULL DEFAULT NULL,
            `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`,`userId`),
            CONSTRAINT `fk_ruk_userId__ru_id` FOREIGN KEY (`userId`) REFERENCES `api_users` (`id`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_user_meta` (
            `id` varchar(32) NOT NULL,
            `userId` binary(16) NOT NULL,
            `metaValue` longtext DEFAULT NULL,
            `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`,`userId`),
            CONSTRAINT `fk_rum_userId__ru_id` FOREIGN KEY (`userId`) REFERENCES `api_users` (`id`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenants` (
            `id` binary(16) NOT NULL,
            `owner` binary(16) NOT NULL,
            `name` varchar(255) NOT NULL,
            `meta` JSON NULL DEFAULT NULL,
            `enabled` tinyint NOT NULL DEFAULT '0',
            `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`), 
            UNIQUE (`name`),
            CONSTRAINT `fk_rt_owner__ru_id` FOREIGN KEY (`owner`) REFERENCES `api_users` (`id`) ON DELETE CASCADE)
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenant_meta` (
            `id` varchar(32) NOT NULL,        
            `tenantId` binary(16) NOT NULL,
            `metaValue` longtext DEFAULT NULL,
            `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`,`tenantId`),
            CONSTRAINT `fk_rtm_tenantId__rt_id` FOREIGN KEY (`tenantId`) REFERENCES `api_tenants` (`id`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenant_users` (
            `tenantId` binary(16) NOT NULL,
            `userId` binary(16) NOT NULL,
            PRIMARY KEY (`tenantId`,`userId`),
            CONSTRAINT `fk_rtu_tenantId__rt_id` FOREIGN KEY (`tenantId`) REFERENCES `api_tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rtu_userId__ru_id` FOREIGN KEY (`userId`) REFERENCES `api_users` (`id`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenant_user_meta` (
            `id` varchar(32) NOT NULL,
            `tenantId` binary(16) NOT NULL,        
            `userId` binary(16) NOT NULL,
            `metaValue` longtext DEFAULT NULL,
            `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`,`tenantId`,`userId`),
            CONSTRAINT `fk_rtum_tenantId__rt_id` FOREIGN KEY (`tenantId`) REFERENCES `api_tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rtum_userId__rtu_userId` FOREIGN KEY (`userId`) REFERENCES `api_tenant_users` (`userId`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenant_groups` (
            `id` binary(16) NOT NULL,
            `tenantId` binary(16) NOT NULL,
            `name` varchar(255) NOT NULL,
            `description` text DEFAULT NULL,
            `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`),
            UNIQUE `uq_rtg_tenantId__name` (`tenantId`, `name`),
            CONSTRAINT `fk_rtg_tenantId__rt_id` FOREIGN KEY (`tenantId`) REFERENCES `api_tenants` (`id`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenant_group_users` (
            `tenantId` binary(16) NOT NULL,
            `groupId` binary(16) NOT NULL,
            `userId` binary(16) NOT NULL,
            PRIMARY KEY (`tenantId`,`groupId`,`userId`),
            CONSTRAINT `fk_rtgu_tenantId__rt_id` FOREIGN KEY (`tenantId`) REFERENCES `api_tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rtgu_groupId__rtg_id` FOREIGN KEY (`groupId`) REFERENCES `api_tenant_groups` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rtgu_userId__ru_id` FOREIGN KEY (`userId`) REFERENCES `api_users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rtgu_userId__rtu_userId` FOREIGN KEY (`tenantId`, `userId`) REFERENCES `api_tenant_users` (`tenantId`, `userId`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenant_permissions` (
            `id` binary(16) NOT NULL,
            `tenantId` binary(16) NOT NULL,
            `name` varchar(255) NOT NULL,
            `description` varchar(255) NULL DEFAULT NULL,
            `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`),
            UNIQUE uq_rtp_tenantId__name(`tenantId`, `name`),
            CONSTRAINT `fk_rtp_tenantId__rt_id` FOREIGN KEY (`tenantId`) REFERENCES `api_tenants` (`id`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenant_roles` (
            `id` binary(16) NOT NULL,
            `tenantId` binary(16) NOT NULL,
            `name` varchar(255) NOT NULL,
            `description` varchar(255) NULL DEFAULT NULL,
            `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`),
            UNIQUE uq_rtr_tenantId__name(`tenantId`,`name`),
            CONSTRAINT `fk_rtr_tenantId__rt_id` FOREIGN KEY (`tenantId`) REFERENCES `api_tenants` (`id`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenant_role_permissions` (
            `tenantId` binary(16) NOT NULL,
            `roleId` binary(16) NOT NULL,
            `permissionId` binary(16) NOT NULL,
            PRIMARY KEY (`tenantId`,`roleId`,`permissionId`),
            CONSTRAINT `fk_rtrp_tenantId__rt_id` FOREIGN KEY (`tenantId`) REFERENCES `api_tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rtrp_roleId__rtr_id` FOREIGN KEY (`roleId`) REFERENCES `api_tenant_roles` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rtrp_permissionId__rtr_id` FOREIGN KEY (`permissionId`) REFERENCES `api_tenant_permissions` (`id`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenant_user_roles` (
            `tenantId` binary(16) NOT NULL,        
            `userId` binary(16) NOT NULL,
            `roleId` binary(16) NOT NULL,
            PRIMARY KEY (`tenantId`,`userId`,`roleId`),
            CONSTRAINT `fk_rtur_tenantId__rt_id` FOREIGN KEY (`tenantId`) REFERENCES `api_tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rtur_userId__rtu_userId` FOREIGN KEY (`userId`) REFERENCES `api_tenant_users` (`userId`) ON DELETE CASCADE,
            CONSTRAINT `fk_rtur_roleId__rtr_id` FOREIGN KEY (`roleId`) REFERENCES `api_tenant_roles` (`id`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `api_tenant_invitations` (
            `email` varchar(255) NOT NULL,        
            `tenantId` binary(16) NOT NULL,
            `roleId` binary(16) NOT NULL,
            `expiresAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (`email`, `tenantId`),
            CONSTRAINT `fk_rti_tenantId__rt_id` FOREIGN KEY (`tenantId`) REFERENCES `api_tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rti_roleId__rtr_id` FOREIGN KEY (`roleId`) REFERENCES `api_tenant_roles` (`id`) ON DELETE CASCADE) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    }

    /**
     * @inheritDoc
     * @throws AdapterException
     */
    public function down(): void
    {

        $this->bucketAdapter->down();

        $this->db->query("DROP TABLE IF EXISTS `api_tenant_invitations`");
        $this->db->query("DROP TABLE IF EXISTS `api_tenant_user_roles`");
        $this->db->query("DROP TABLE IF EXISTS `api_tenant_role_permissions`");
        $this->db->query("DROP TABLE IF EXISTS `api_tenant_roles`");
        $this->db->query("DROP TABLE IF EXISTS `api_tenant_permissions`");
        $this->db->query("DROP TABLE IF EXISTS `api_tenant_group_users`");
        $this->db->query("DROP TABLE IF EXISTS `api_tenant_groups`");
        $this->db->query("DROP TABLE IF EXISTS `api_tenant_user_meta`");
        $this->db->query("DROP TABLE IF EXISTS `api_tenant_users`");
        $this->db->query("DROP TABLE IF EXISTS `api_tenant_meta`");
        $this->db->query("DROP TABLE IF EXISTS `api_tenants`");
        $this->db->query("DROP TABLE IF EXISTS `api_user_meta`");
        $this->db->query("DROP TABLE IF EXISTS `api_user_keys`");
        $this->db->query("DROP TABLE IF EXISTS `api_users`");

    }

}