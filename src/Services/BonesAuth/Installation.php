<?php

/**
 * @package bones
 * @link https://github.com/bayfrontmedia/bones
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2021 Bayfront Media
 */

namespace Bayfront\Bones\Services\BonesAuth;

use Bayfront\StringHelpers\Str;

trait Installation
{

    /**
     * @return void
     */

    public function installBonesAuth(): void
    {

        $new_permissions = [

            // Permissions

            [
                'name' => 'global.permissions.create',
                'description' => 'Create permissions'
            ],
            [
                'name' => 'global.permissions.read',
                'description' => 'Read permissions'
            ],
            [
                'name' => 'global.permissions.update',
                'description' => 'Update permissions'
            ],
            [
                'name' => 'global.permissions.delete',
                'description' => 'Delete permissions'
            ],
            [
                'name' => 'global.permissions.roles.read',
                'description' => 'Read roles with permission'
            ],
            [
                'name' => 'self.permissions.read',
                'description' => 'Read own permissions'
            ],

            // Roles

            [
                'name' => 'global.roles.create',
                'description' => 'Create roles'
            ],
            [
                'name' => 'global.roles.read',
                'description' => 'Read roles'
            ],
            [
                'name' => 'global.roles.update',
                'description' => 'Update roles'
            ],
            [
                'name' => 'global.roles.delete',
                'description' => 'Delete roles'
            ],
            [
                'name' => 'global.roles.permissions.read',
                'description' => 'Read permissions of role'
            ],
            [
                'name' => 'global.roles.users.read',
                'description' => 'Read users with role'
            ],
            [
                'name' => 'self.roles.read',
                'description' => 'Read own roles'
            ],
            [
                'name' => 'self.roles.permissions.read',
                'description' => 'Read permissions of own role'
            ],

            // Groups

            [
                'name' => 'global.groups.create',
                'description' => 'Create groups'
            ],
            [
                'name' => 'global.groups.read',
                'description' => 'Read groups'
            ],
            [
                'name' => 'global.groups.update',
                'description' => 'Update groups'
            ],
            [
                'name' => 'global.groups.delete',
                'description' => 'Delete groups'
            ],
            [
                'name' => 'global.groups.users.read',
                'description' => 'Read users in group'
            ],
            [
                'name' => 'self.groups.read',
                'description' => 'Read own groups'
            ],
            [
                'name' => 'self.groups.users.read',
                'description' => 'Read users of own group'
            ],

            // Users

            [
                'name' => 'global.users.create',
                'description' => 'Create users'
            ],
            [
                'name' => 'global.users.read',
                'description' => 'Read users'
            ],
            [
                'name' => 'global.users.update',
                'description' => 'Update users'
            ],
            [
                'name' => 'global.users.delete',
                'description' => 'Delete users'
            ],
            [
                'name' => 'global.users.permissions.read',
                'description' => 'Read permissions of user'
            ],
            [
                'name' => 'global.users.roles.read',
                'description' => 'Read roles of user'
            ],
            [
                'name' => 'global.users.groups.read',
                'description' => 'Read groups of user'
            ],
            [
                'name' => 'group.users.read',
                'description' => 'Read users in own groups'
            ],
            [
                'name' => 'group.users.update',
                'description' => 'Update users in own groups'
            ],
            [
                'name' => 'group.users.delete',
                'description' => 'Delete users in own groups'
            ],
            [
                'name' => 'group.users.permissions.read',
                'description' => 'Read permissions of users in own groups'
            ],
            [
                'name' => 'group.users.roles.read',
                'description' => 'Read roles of users in own groups'
            ],
            [
                'name' => 'group.users.groups.read',
                'description' => 'Read groups of users in own groups'
            ],
            [
                'name' => 'self.users.read',
                'description' => 'Read self'
            ],
            [
                'name' => 'self.users.update',
                'description' => 'Update self'
            ],
            [
                'name' => 'self.users.delete',
                'description' => 'Delete self'
            ],
            [
                'name' => 'self.users.permissions.read',
                'description' => 'Read permissions of self'
            ],
            [
                'name' => 'self.users.roles.read',
                'description' => 'Read roles of self'
            ],
            [
                'name' => 'self.users.groups.read',
                'description' => 'Read groups of self'
            ],

            // User meta

            [
                'name' => 'global.users.meta.read',
                'description' => 'Read user meta'
            ],
            [
                'name' => 'global.users.meta.update',
                'description' => 'Update user meta'
            ],
            [
                'name' => 'global.users.meta.delete',
                'description' => 'Delete user meta'
            ],
            [
                'name' => 'group.users.meta.read',
                'description' => 'Read user meta of users in own groups'
            ],
            [
                'name' => 'group.users.meta.update',
                'description' => 'Update user meta of users in own groups'
            ],
            [
                'name' => 'group.users.meta.delete',
                'description' => 'Delete user meta of users in own groups'
            ],
            [
                'name' => 'self.users.meta.read',
                'description' => 'Read user meta of self'
            ],
            [
                'name' => 'self.users.meta.update',
                'description' => 'Update user meta of self'
            ],
            [
                'name' => 'self.users.meta.delete',
                'description' => 'Delete user meta of self'
            ],

            // Grants

            [
                'name' => 'global.groups.users.grant',
                'description' => 'Grant users to groups'
            ],
            [
                'name' => 'global.groups.users.revoke',
                'description' => 'Revoke users from groups'
            ],
            [
                'name' => 'global.users.groups.grant',
                'description' => 'Grant groups to users'
            ],
            [
                'name' => 'global.users.groups.revoke',
                'description' => 'Revoke groups from users'
            ],
            [
                'name' => 'self.groups.users.grant',
                'description' => 'Grant self to groups'
            ],
            [
                'name' => 'self.groups.users.revoke',
                'description' => 'Revoke self from groups'
            ],
            [
                'name' => 'self.users.groups.grant',
                'description' => 'Grant groups to self'
            ],
            [
                'name' => 'self.users.groups.revoke',
                'description' => 'Revoke groups from self'
            ],
            [
                'name' => 'global.roles.permissions.grant',
                'description' => 'Grant permissions to roles'
            ],
            [
                'name' => 'global.roles.permissions.revoke',
                'description' => 'Revoke permissions from roles'
            ],
            [
                'name' => 'global.permissions.roles.grant',
                'description' => 'Grant roles to permissions'
            ],
            [
                'name' => 'global.permissions.roles.revoke',
                'description' => 'Revoke roles from permissions'
            ],
            [
                'name' => 'global.roles.users.grant',
                'description' => 'Grant roles to users'
            ],
            [
                'name' => 'global.roles.users.revoke',
                'description' => 'Revoke roles from users'
            ],
            [
                'name' => 'global.users.roles.grant',
                'description' => 'Grant users to roles'
            ],
            [
                'name' => 'global.users.roles.revoke',
                'description' => 'Revoke users from roles'
            ],

        ];

        // Create permissions

        foreach ($new_permissions as $permission) {

            $this->createPermission($permission);

        }

        // Create roles

        $global = $this->createRole([
            'name' => 'Global Administrator',
            'enabled' => 1
        ]);

        $group = $this->createRole([
            'name' => 'Group Administrator',
            'enabled' => 1
        ]);

        $user = $this->createRole([
            'name' => 'User',
            'enabled' => 1
        ]);

        // Role permissions

        $permissions = $this->getPermissions();

        $global_grants = [];

        $group_grants = [];

        $user_grants = [];

        foreach ($permissions as $permission) {

            if (Str::startsWith($permission['name'], 'global.')) {
                $global_grants[] = $permission['id'];
            } else if (Str::startsWith($permission['name'], 'group.')) {
                $group_grants[] = $permission['id'];
            } else if (Str::startsWith($permission['name'], 'self.')) {
                $user_grants[] = $permission['id'];
            }
        }

        $this->grantRolePermissions($global, $global_grants);

        $this->grantRolePermissions($group, $group_grants);

        $this->grantRolePermissions($user, $user_grants);

        // User

        $admin = $this->createUser([
            'login' => 'admin',
            'password' => 'admin',
            'enabled' => 1
        ]);

        // User grant

        $this->grantUserRoles($admin, $global);

    }

}