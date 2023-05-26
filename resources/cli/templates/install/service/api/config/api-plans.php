<?php

/*
 * Available API service tenant plans, along with their roles and permissions.
 *
 * For more information, see:
 * https://github.com/bayfrontmedia/bones/services/api/README.md
 */

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Services\Api\Utilities\Api;

/*
 * All available permissions
 */

$permissions = array_merge(Api::DEFAULT_PERMISSIONS, []); // Merge with app-specific permissions

/*
 * All available roles
 */

$roles = [
    'Administrator' => [
        'description' => 'Full administrative privileges',
        'permissions' => $permissions
    ],
    'Contributor' => [
        'description' => 'Full rights except ability to manage tenant or modify user access',
        'permissions' => Arr::except($permissions, [
            'tenant.invitations.create',
            'tenant.invitations.update',
            'tenant.invitations.delete',
            'tenant.permissions.create',
            'tenant.permissions.update',
            'tenant.permissions.delete',
            'tenant.roles.create',
            'tenant.roles.update',
            'tenant.roles.delete',
            'tenant.role.permissions.add',
            'tenant.role.permissions.remove',
            'tenant.user.roles.add',
            'tenant.user.roles.remove',
            'tenant.users.add',
            'tenant.users.remove'
        ])
    ],
    'Reader' => [
        'description' => 'Read-only access',
        'permissions' => Arr::only($permissions, [
            'tenant.groups.read',
            'tenant.invitations.read',
            'tenant.meta.read',
            'tenant.permissions.read',
            'tenant.roles.read',
            'tenant.user.meta.read',
            'tenant.group.users.read',
            'tenant.role.permissions.read',
            'tenant.user.roles.read',
            'tenant.users.read',
            'tenant.user.permissions.read'
        ])
    ]
];

/*
 * All available plans
 */

return [
    'None' => [
        'plan' => [
            'max_users' => 25
        ],
        'permissions' => [],
        'roles' => [],
        'meta' => []
    ],
    'Standard' => [
        'plan' => [
            'max_users' => 25
        ],
        'permissions' => Arr::except($permissions, [
            'tenant.permissions.create',
            'tenant.permissions.update',
            'tenant.permissions.delete',
            'tenant.roles.create',
            'tenant.roles.update',
            'tenant.roles.delete',
            'tenant.role.permissions.add',
            'tenant.role.permissions.remove'
        ]),
        'roles' => $roles,
        'meta' => []
    ],
    'Premium' => [
        'plan' => [
            'max_users' => 100
        ],
        'permissions' => Arr::except($permissions, [
            'tenant.permissions.create',
            'tenant.permissions.update',
            'tenant.permissions.delete',
            'tenant.roles.create',
            'tenant.roles.update',
            'tenant.roles.delete',
            'tenant.role.permissions.add',
            'tenant.role.permissions.remove'
        ]),
        'roles' => $roles,
        'meta' => []
    ]
];