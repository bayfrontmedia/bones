<?php

/**
 * @package bones
 * @link https://github.com/bayfrontmedia/bones
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2021 Bayfront Media
 */

namespace Bayfront\Bones\Services\BonesAuth;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\PDO\Query;
use Bayfront\RBAC\Auth;
use Bayfront\RBAC\Exceptions\InvalidUserException;
use Bayfront\RBAC\Migrations\v1\Schema;
use PDO;

class BonesAuth extends Auth
{

    use Installation;

    protected $pdo;

    public function __construct(PDO $pdo, string $pepper)
    {

        $this->pdo = $pdo;

        parent::__construct($pdo, $pepper);

    }

    /**
     * Return collection results in a standardized format.
     *
     * @param Query $query
     * @param array $request
     *
     * @return array
     */

    protected function _getResults(Query $query, array $request): array
    {

        $results = $query->get();

        $total = $query->getTotalRows();

        // json_decode attributes column

        foreach ($results as $k => $v) {

            if (isset($v['attributes']) && NULL !== $v['attributes']) {

                $results[$k]['attributes'] = json_decode($v['attributes'], true);

            }

        }

        return [
            'results' => $results,
            'meta' => [
                'count' => count($results),
                'total' => $total,
                'pages' => ceil($total / $request['limit']),
                'pageSize' => $request['limit'],
                'pageNumber' => ($request['offset'] / $request['limit']) + 1
            ]
        ];

    }

    /*
     * ############################################################
     * Installation
     * ############################################################
     */

    /**
     * Create database tables, permissions, roles and grants to begin using the BonesAuth service.
     * A default user will be created with a login/password combo of: "admin".
     *
     * IMPORTANT: Change the default user's credentials before using in a production environment!
     *
     * @return void
     */

    public function install(): void
    {

        $schema = new Schema($this->pdo);

        $schema->up();

        $this->installBonesAuth();

    }

    /**
     * Uninstall database tables used by the BonesAuth service.
     */

    public function uninstall(): void
    {

        $schema = new Schema($this->pdo);

        $schema->down();

    }

    /*
     * ############################################################
     * Groups
     * ############################################################
     */

    /**
     * Get all groups using query builder.
     *
     * @param array $request
     * @param array|null $valid_group_ids (Restrict results to group ID(s))
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getGroupsCollection(array $request, array $valid_group_ids = NULL): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_groups')
            ->select(Arr::get($request, 'fields.groups', ['*']))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->orderBy(Arr::get($request, 'order_by', ['name']));

        if (is_array($valid_group_ids)) { // Restrict results to group ids

            $query->where('id', 'in', implode(',', $valid_group_ids));

        }

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /**
     * Get all user ID's in group.
     *
     * @param string $group_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getGroupUsersRelationships(string $group_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_group_users')
            ->select('userId')
            ->where('groupId', 'eq', $group_id)
            ->orderBy([
                'userId'
            ]);

        return $query->get();

    }

    /**
     * Get all users in group using a query builder.
     *
     * @param array $request
     * @param string $group_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getGroupUsersCollection(array $request, string $group_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_users')
            ->leftJoin('rbac_group_users', 'rbac_users.id', 'rbac_group_users.userId')
            ->select(Arr::get($request, 'fields.users', ['*']))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->where('rbac_group_users.groupId', 'eq', $group_id)
            ->orderBy(Arr::get($request, 'order_by', ['rbac_users.createdAt']));

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /*
     * ############################################################
     * Permissions
     * ############################################################
     */

    /**
     * Get all permissions using query builder.
     *
     * @param array $request
     * @param array|null $valid_permission_ids (Restrict results to permission ID(s))
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getPermissionsCollection(array $request, array $valid_permission_ids = NULL): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_permissions')
            ->select(Arr::get($request, 'fields.permissions', ['*']))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->orderBy(Arr::get($request, 'order_by', ['name']));

        if (is_array($valid_permission_ids)) { // Restrict results to permission ids

            $query->where('id', 'in', implode(',', $valid_permission_ids));

        }

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /**
     * Get all role ID's with permission.
     *
     * @param string $permission_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getPermissionRolesRelationships(string $permission_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_role_permissions')
            ->select('roleId')
            ->where('permissionId', 'eq', $permission_id)
            ->orderBy([
                'roleId'
            ]);

        return $query->get();

    }

    /**
     * Get all roles with permission using a query builder.
     *
     * @param array $request
     * @param string $permission_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getPermissionRolesCollection(array $request, string $permission_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_roles')
            ->leftJoin('rbac_role_permissions', 'rbac_roles.id', 'rbac_role_permissions.roleId')
            ->select(Arr::get($request, 'fields.roles', ['*']))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->where('rbac_role_permissions.permissionId', 'eq', $permission_id)
            ->orderBy(Arr::get($request, 'order_by', ['rbac_roles.name']));

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /*
     * ############################################################
     * Roles
     * ############################################################
     */

    /**
     * Get all roles using query builder.
     *
     * @param array $request
     * @param array|null $valid_role_ids (Restrict results to role ID(s))
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getRolesCollection(array $request, array $valid_role_ids = NULL): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_roles')
            ->select(Arr::get($request, 'fields.roles', ['*']))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->orderBy(Arr::get($request, 'order_by', ['name']));

        if (is_array($valid_role_ids)) { // Restrict results to role ids

            $query->where('id', 'in', implode(',', $valid_role_ids));

        }

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /**
     * Get all permission ID's of role.
     *
     * @param string $role_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getRolePermissionsRelationships(string $role_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_role_permissions')
            ->select('permissionId')
            ->where('roleId', 'eq', $role_id)
            ->orderBy([
                'permissionId'
            ]);

        return $query->get();

    }

    /**
     * Get all permissions of role using a query builder.
     *
     * @param array $request
     * @param string $role_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getRolePermissionsCollection(array $request, string $role_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_permissions')
            ->leftJoin('rbac_role_permissions', 'rbac_permissions.id', 'rbac_role_permissions.permissionId')
            ->select(Arr::get($request, 'fields.permissions', ['*']))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->where('rbac_role_permissions.roleId', 'eq', $role_id)
            ->orderBy(Arr::get($request, 'order_by', ['rbac_permissions.name']));

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /**
     * Get all user ID's with role.
     *
     * @param string $role_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getRoleUsersRelationships(string $role_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_role_users')
            ->select('userId')
            ->where('roleId', 'eq', $role_id)
            ->orderBy([
                'userId'
            ]);

        return $query->get();

    }

    /**
     * Get all users with role using a query builder.
     *
     * @param array $request
     * @param string $role_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getRoleUsersCollection(array $request, string $role_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_users')
            ->leftJoin('rbac_role_users', 'rbac_users.id', 'rbac_role_users.userId')
            ->select(Arr::get($request, 'fields.users', ['*']))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->where('rbac_role_users.roleId', 'eq', $role_id)
            ->orderBy(Arr::get($request, 'order_by', ['rbac_users.createdAt']));

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /*
     * ############################################################
     * Users
     * ############################################################
     */

    /**
     * Get all users using query builder.
     *
     * @param array $request
     * @param array|null $valid_group_ids (Restrict results to users in group(s))
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getUsersCollection(array $request, array $valid_group_ids = NULL): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_users')
            ->select(Arr::get($request, 'fields.users', [
                'id',
                'login',
                'firstName',
                'lastName',
                'companyName',
                'email',
                'attributes',
                'enabled',
                'createdAt',
                'updatedAt'
            ]))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->orderBy(Arr::get($request, 'order_by', ['createdAt']));

        if (is_array($valid_group_ids)) { // Restrict results to users in groups

            $query->leftJoin('rbac_group_users', 'rbac_users.id', 'rbac_group_users.userId')
                ->where('rbac_group_users.groupId', 'in', implode(', ', $valid_group_ids));

        }

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /**
     * Get role ID's of user, checking user and roles exist and are active.
     *
     * @param string $user_id
     *
     * @return array
     */

    protected function _getActiveUserRoles(string $user_id): array
    {

        // Does user exist and is enabled

        try {

            $user = $this->getUser($user_id);

        } catch (InvalidUserException $e) {

            return [];

        }

        if ($user['enabled'] != 1) {

            return [];

        }

        // Get user roles

        $valid_roles = [];

        $roles = $this->getUserRoles($user_id);

        foreach ($roles as $role) {

            if ($role['enabled'] == 1) {

                $valid_roles[] = $role['id'];

            }

        }

        return $valid_roles;

    }

    /**
     * Get all permission ID's of user.
     *
     * @param string $user_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getUserPermissionsRelationships(string $user_id): array
    {

        $valid_roles = $this->_getActiveUserRoles($user_id);

        $query = new Query($this->pdo);

        $query->table('rbac_role_permissions')
            ->select('permissionId')
            ->distinct()
            ->where('roleId', 'in', implode(', ', $valid_roles))
            ->orderBy([
                'permissionId'
            ]);

        return $query->get();

    }

    /**
     * Get all permissions of user using a query builder.
     *
     * @param array $request
     * @param string $user_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getUserPermissionsCollection(array $request, string $user_id): array
    {

        $valid_roles = $this->_getActiveUserRoles($user_id);

        $query = new Query($this->pdo);

        $query->table('rbac_permissions')
            ->leftJoin('rbac_role_permissions', 'rbac_permissions.id', 'rbac_role_permissions.permissionId')
            ->select(Arr::get($request, 'fields.permissions', ['*']))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->where('rbac_role_permissions.roleId', 'in', implode(', ', $valid_roles))
            ->orderBy(Arr::get($request, 'order_by', ['rbac_permissions.name']));

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /**
     * Get all role ID's of user.
     *
     * @param string $user_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getUserRolesRelationships(string $user_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_role_users')
            ->select('roleId')
            ->where('userId', 'eq', $user_id)
            ->orderBy([
                'roleId'
            ]);

        return $query->get();

    }

    /**
     * Get all roles of user using a query builder.
     *
     * @param array $request
     * @param string $user_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getUserRolesCollection(array $request, string $user_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_roles')
            ->leftJoin('rbac_role_users', 'rbac_roles.id', 'rbac_role_users.roleId')
            ->select(Arr::get($request, 'fields.roles', ['*']))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->where('rbac_role_users.userId', 'eq', $user_id)
            ->orderBy(Arr::get($request, 'order_by', ['rbac_roles.name']));

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /**
     * Get all group ID's of user.
     *
     * @param string $user_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getUserGroupsRelationships(string $user_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_group_users')
            ->select('groupId')
            ->where('userId', 'eq', $user_id)
            ->orderBy([
                'groupId'
            ]);

        return $query->get();

    }

    /**
     * Get all groups of user using a query builder.
     *
     * @param array $request
     * @param string $user_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getUserGroupsCollection(array $request, string $user_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_groups')
            ->leftJoin('rbac_group_users', 'rbac_groups.id', 'rbac_group_users.groupId')
            ->select(Arr::get($request, 'fields.groups', ['*']))
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->where('rbac_group_users.userId', 'eq', $user_id)
            ->orderBy(Arr::get($request, 'order_by', ['rbac_groups.name']));

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

    /**
     * Get all user meta using query builder.
     *
     * @param array $request
     * @param string $user_id
     *
     * @return array
     *
     * @throws QueryException
     */

    public function getUserMetaCollection(array $request, string $user_id): array
    {

        $query = new Query($this->pdo);

        $query->table('rbac_user_meta')
            ->select(Arr::get($request, 'fields.meta', [
                'metaKey',
                'metaValue'
            ]))
            ->where('userId', 'eq', $user_id)
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->orderBy(Arr::get($request, 'order_by', ['metaKey']));

        foreach ($request['filters'] as $column => $filter) {

            foreach ($filter as $operator => $value) {

                $query->where($column, $operator, $value);

            }

        }

        return $this->_getResults($query, $request);

    }

}