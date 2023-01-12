<?php

/*
 * Already defined by the app:
 *
 * APP_BASE_PATH
 * APP_PUBLIC_PATH
 * BONES_BASE_PATH
 */

/**
 * Paths without trailing slashes
 */

// App

const APP_CONFIG_PATH = APP_BASE_PATH . '/config'; // Path to the application's `config` directory
const APP_RESOURCES_PATH = APP_BASE_PATH . '/resources'; // Path to the application's `resources` directory
const APP_STORAGE_PATH = APP_BASE_PATH . '/storage'; // Path to the application's `storage` directory

// Bones

const BONES_RESOURCES_PATH = BONES_BASE_PATH . '/resources'; // Path to the Bones `resources` directory
const BONES_VERSION = '2.0.0'; // Current Bones version