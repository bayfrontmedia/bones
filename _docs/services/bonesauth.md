# BonesAuth service

This service adds role-based access control (RBAC) user authentication and authorization functionality to your application.

This service is built atop the [RBAC Auth](https://github.com/bayfrontmedia/rbac-auth) library.

## Usage

This service requires a `PDO` instance to be passed to the constructor, 
and can be added to the container in the application's `resources/bootstrap.php` file.

Before this service can be used, utilize the `install()` method to create the database tables, permissions, roles and grants.
In addition, a default user will be created with a login/password combo of: "admin".

**IMPORTANT: Change the default user's credentials before using in a production environment!**

**Example installation:**

```
/*
 * Place the BonesAuth service into the container
 */

/** @var Db $db */

$db = get_from_container('db');

$auth = get_service('BonesAuth\\BonesAuth', [
    'pdo' => $db->get('primary'), // PDO instance
    'pepper' => get_config('app.key', '')
]);

$auth->install();
```

To uninstall the database tables, use the `uninstall()` method.

**Example usage:**

```
/*
 * Place the BonesAuth service into the container
 */

/** @var Db $db */

$db = get_from_container('db');

get_service('BonesAuth\\BonesAuth', [
    'pdo' => $db->get('primary'), // PDO instance
    'pepper' => get_config('app.key', '')
]);
```

## Public methods

Every method requiring a `$request` array as a parameter expects an array generated from the BonesApi service [parseQuery](https://github.com/bayfrontmedia/bones/blob/master/_docs/services/bonesapi.md#parsequery) method.

In addition to all the public methods available in the [RBAC Auth](https://github.com/bayfrontmedia/rbac-auth) library, 
the following methods have been added:

- [install](#install)
- [uninstall](#uninstall)
- [getGroupsCollection](#getgroupscollection)
- [getGroupUsersRelationships](#getgroupusersrelationships)
- [getGroupUsersCollection](#getgroupuserscollection)
- [getPermissionsCollection](#getpermissionscollection)
- [getPermissionRolesRelationships](#getpermissionrolesrelationships)
- [getPermissionRolesCollection](#getpermissionrolescollection)
- [getRolesCollection](#getrolescollection)
- [getRolePermissionsRelationships](#getrolepermissionsrelationships)
- [getRolePermissionsCollection](#getrolepermissionscollection)
- [getRoleUsersRelationships](#getroleusersrelationships)
- [getRoleUsersCollection](#getroleuserscollection)
- [getUsersCollection](#getuserscollection)
- [getUserPermissionsRelationships](#getuserpermissionsrelationships)
- [getUserPermissionsCollection](#getuserpermissionscollection)
- [getUserRolesRelationships](#getuserrolesrelationships)
- [getUserRolesCollection](#getuserrolescollection)
- [getUserGroupsRelationships](#getusergroupsrelationships)
- [getUserGroupsCollection](#getusergroupscollection)
- [getUserMetaCollection](#getusermetacollection)

<hr />

### install

**Description:**

Create database tables, permissions, roles and grants to begin using the BonesAuth service.
A default user will be created with a login/password combo of: "admin".

**IMPORTANT: Change the default user's credentials before using in a production environment!**

**Parameters:**

- (None)

**Returns:**

- (void)

<hr />

### uninstall

**Description:**

Uninstall database tables used by the BonesAuth service.

**Parameters:**

- (None)

**Returns:**

- (void)

<hr />

### getGroupsCollection

**Description:**

Get all groups using query builder.

**Parameters:**

- `$request` (array)
- `$valid_group_ids = NULL` (array|null): Restrict results to group ID(s)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getGroupUsersRelationships

**Description:**

Get all user ID's in group.

**Parameters:**

- `$group_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getGroupUsersCollection

**Description:**

Get all users in group using a query builder.

**Parameters:**

- `$request` (array)
- `$group_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getPermissionsCollection

**Description:**

Get all permissions using query builder.

**Parameters:**

- `$request` (array)
- `$valid_permission_ids = NULL` (array|null): Restrict results to permission ID(s)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getPermissionRolesRelationships

**Description:**

Get all role ID's with permission.

**Parameters:**

- `$permission_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getPermissionRolesCollection

**Description:**

Get all roles with permission using a query builder.

**Parameters:**

- `$request` (array)
- `$permission_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getRolesCollection

**Description:**

Get all roles using query builder.

**Parameters:**

- `$request` (array)
- `$valid_role_ids = NULL` (array|null): Restrict results to role ID(s)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getRolePermissionsRelationships

**Description:**

Get all permission ID's of role.

**Parameters:**

- `$role_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getRolePermissionsCollection

**Description:**

Get all permissions of role using a query builder.

**Parameters:**

- `$request` (array)
- `$role_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getRoleUsersRelationships

**Description:**

Get all user ID's with role.

**Parameters:**

- `$role_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getRoleUsersCollection

**Description:**

Get all users with role using a query builder.

**Parameters:**

- `$request` (array)
- `$role_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getUsersCollection

**Description:**

Get all users using query builder.

**Parameters:**

- `$request` (array)
- `$valid_group_ids = NULL` (array|null): Restrict results to users in group(s)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getUserPermissionsRelationships

**Description:**

Get all permission ID's of user.

**Parameters:**

- `$user_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getUserPermissionsCollection

**Description:**

Get all permissions of user using a query builder.

**Parameters:**

- `$request` (array)
- `$user_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getUserRolesRelationships

**Description:**

Get all role ID's of user.

**Parameters:**

- `$user_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getUserRolesCollection

**Description:**

Get all roles of user using a query builder.

**Parameters:**

- `$request` (array)
- `$user_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getUserGroupsRelationships

**Description:**

Get all group ID's of user.

**Parameters:**

- `$user_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getUserGroupsCollection

**Description:**

Get all groups of user using a query builder.

**Parameters:**

- `$request` (array)
- `$user_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

<hr />

### getUserMetaCollection

**Description:**

Get all user meta using query builder.

**Parameters:**

- `$request` (array)
- `$user_id` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`