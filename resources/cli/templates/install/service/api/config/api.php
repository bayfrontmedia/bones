<?php

/*
 * API service configuration.
 */

use Bayfront\Bones\Services\Api\Utilities\Api;

return [
    'log_actions' => [ // Actions to log
        Api::ACTION_CREATE,
        Api::ACTION_READ,
        Api::ACTION_UPDATE,
        Api::ACTION_DELETE
    ],
    'required_meta' => [ // In dot notation. Empty array for none.
        'tenants' => [], // TODO
        'users' => []
    ]
];