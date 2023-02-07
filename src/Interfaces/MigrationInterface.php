<?php

namespace Bayfront\Bones\Interfaces;

interface MigrationInterface
{

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