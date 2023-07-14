<?php

/*
 * API service configuration.
 *
 * For more information, see:
 * https://github.com/bayfrontmedia/bones/services/api/README.md
 */

use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Utilities\Api;

// Variables

$protocol = App::environment() == App::ENV_DEV ? 'http://' : 'https://';

return [
    'version' => '1.0.0', // API version
    'maintenance' => [
        'enabled' => false,
        'until' => new DateTime('2099-01-01 12:00:00') // DateTimeInterface
    ],
    'https_env' => [ // App environments to force HTTPS
        //App::ENV_DEV,
        App::ENV_STAGING,
        App::ENV_QA,
        App::ENV_PROD
    ],
    'request' => [
        'header' => [
            'accept' => 'application/vnd.api+json', // Required Accept header for all requests
            'content_type' => 'application/vnd.api+json' // Required header if request has body
        ]
    ],
    'rate_limit' => [ // Endpoint types, per minute
        'auth' => 5, // Auth-related endpoints (AuthApiController)
        'public' => 25, // Public endpoint (PublicApiController)
        'private' => 250 // Authenticated user (PrivateApiController)
    ],
    'log_actions' => [ // Actions to log
        Api::ACTION_CREATE,
        Api::ACTION_READ,
        Api::ACTION_UPDATE,
        Api::ACTION_DELETE
    ],
    'required_meta' => [ // Validate required meta keys in dot notation. Empty array for none.
        'tenants' => [],
        'users' => [
            'name' => 'string'
        ]
    ],
    'registration' => [
        'tenants' => [
            'open' => false // Enable open registrations by all users?
        ],
        'users' => [
            'public' => true, // Enable public registrations?
            'enabled' => false // User accounts enabled by default? False will create a verification ID.
        ]
    ],
    'duration' => [ // Validity durations (in minutes)
        'access_token' => App::environment() == App::ENV_DEV ? 10080 : 20, // (1200 = 20 minutes, 10080 = 7 days)
        'refresh_token' => 10080, // 10080 = 7 days
        'invitation' => 10080, // (10080 = 7 days)
        'password_token' => 120 // (120 = 2 hours)
    ],
    'auth' => [
        'methods' => [ // Methods by which a user can be authenticated
            Api::AUTH_PASSWORD,
            Api::AUTH_REFRESH_TOKEN,
            Api::AUTH_ACCESS_TOKEN,
            Api::AUTH_KEY
        ],
        'domains' => App::environment() == App::ENV_DEV ? [] : [ // Restrict AuthApiController to referring domains (blank for none)
            'app.example.com'
        ],
        'ips' => App::environment() == App::ENV_DEV ? [] : [ // Restrict AuthApiController to IP addresses (blank for none)
            '127.0.0.1'
        ]
    ],
    'keys' => [ // User (API) keys
        'enabled' => false, // Enable users to make keys?
        'total' => 10, // Total number of user keys allowed per user
        'domains' => 20, // Total number of allowedDomains allowed per key
        'ips' => 20, // Total number of allowedIps allowed per key
        'max_duration' => 180, // Maximum duration of user key validity (in days)
    ],
    'tenants' => [
        'max_users' => 50 // Default max number of users allowed
    ],
    'response' => [
        'base_url' => $protocol . App::getEnv('ROUTER_HOST') . App::getEnv('ROUTER_ROUTE_PREFIX') . '/v1', // Base URL to the API (No trailing slash)
        'absolute_uri' => true, // Absolute vs relative URI's returned in schema
        'collection_size' => [ // Results returned per page
            'default' => 20,
            'max' => 500
        ]
    ],
];