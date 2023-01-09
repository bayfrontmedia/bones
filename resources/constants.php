<?php

/*
 * Already defined by the app:
 *
 * APP_ROOT_PATH
 * APP_PUBLIC_PATH
 */

/**
 * Paths without trailing slashes
 */

// App

const APP_CONFIG_PATH = APP_ROOT_PATH . '/config'; // Path to the application's `config` directory
const APP_RESOURCES_PATH = APP_ROOT_PATH . '/resources'; // Path to the application's `resources` directory
const APP_STORAGE_PATH = APP_ROOT_PATH . '/storage'; // Path to the application's `storage` directory

// Bones

define('BONES_ROOT_PATH', rtrim(dirname(__FILE__, 2), '/')); // Root path to the Bones directory
const BONES_RESOURCES_PATH = BONES_ROOT_PATH . '/resources'; // Path to the Bones `resources` directory
const BONES_VERSION = '2.0.0'; // Current Bones version