<?php

namespace Bayfront\Bones\Interfaces;

interface MigrationInterface
{

    /**
     * Get globally unique migration identifier name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Run migration.
     *
     * @return void
     */

    public function up(): void;

    /**
     * Reverse migration.
     *
     * @return void
     */

    public function down(): void;

}