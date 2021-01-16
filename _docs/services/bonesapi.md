# BonesAPI service

This service aids in handling some repetitive API-related tasks and ensure the proper headers are returned with each HTTP response.

## Usage

This service can be added to the container by creating a configuration array located at `config/api.php` and passing it to the constructor.

**Example `config/api.php`:**

```
return [
    'maintenance_mode' => false, // Boolean (optional)
    'maintenance_until' => new DateTime('2020-11-04 17:00:00'), // DateTimeInterface (optional)
    'allow_http' => 'development', // app.environments to allow http (string|array) (optional)
    'accept_header' => 'application/vnd.api+json', // Required Accept header (optional)
    'content_type' => 'application/vnd.api+json', // Required Content-Type header to exist with request body (optional)
    'buckets_path' => '/app/buckets', // Directory in which to store rate limit buckets from the default filesystem disk root
    'auth_rate_limit' => 5, // Per minute rate limit for failed authentication
    'rate_limit' => 50, // Per minute rate limit for authenticated user
    'webhook_rate_limit' => 100, // Per minute rate limit for public webhoooks
    'access_token_lifetime' => 86400, // 24 hours
    'refresh_token_lifetime' => 604800 // e.g.: 604800 (7 days), 2592000 (30 days)
];
```

**Example:**

```
// Place the BonesApi service into the services container

get_service('BonesApi', [
    'config' => get_config('api')
]);
```

## Public methods

- [start](#start)
- [allowedMethods](#allowedmethods)
- [authenticateJwt](#authenticatejwt)
- [enforceRateLimit](#enforceratelimit)
- [resetRateLimit](#resetratelimit)
- [getBody](#getbody)
- [parseQuery](#parsequery)

<hr />

### start

**Description:**

Initialize the API environment.

Removes the `X-Powered-By` header, and sets `X-Content-Type-Options`, `X-XSS-Protection` and `X-Frame-Options` headers to increase security.

Checks for maintenance mode and aborts with a `503 Service Unavailable` HTTP status and sets `Retry-After` header if `api.maintenance_until` config is set.

Forces HTTPS and proper `Accept` header, or aborts with a `406 Not Acceptable` HTTP status.

Defines the `IS_API` constant which can be used throughout the app, if needed.

**Parameters:**

- (None)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Bones\Exceptions\HttpException`
- `Bayfront\HttpResponse\InvalidStatusCodeException`
- `Bayfront\Container\NotFoundException`

<hr />

### allowedMethods

**Description:**

Checks request method is allowed or aborts with a `405 Method Not Allowed` HTTP status.

**Parameters:**

- `$methods` (string|array): Allowed request methods

**Returns:**

- (void)

**Throws:**

- `Bayfront\Bones\Exceptions\HttpException`
- `Bayfront\HttpResponse\InvalidStatusCodeException`
- `Bayfront\Container\NotFoundException`

<hr />

### authenticateJwt

**Description:**

Checks that a valid JWT exists in the `Authorization` header or enforces the `api.auth_rate_limit` config and aborts with a `401 Unauthorized` HTTP status.

**Parameters:**

- (None)

**Returns:**

- (array): JWT contents

**Throws:**

- `Bayfront\LeakyBucket\AdapterException`
- `Bayfront\LeakyBucket\BucketException`
- `Bayfront\Bones\Exceptions\HttpException`
- `Bayfront\HttpResponse\InvalidStatusCodeException`
- `Bayfront\Container\NotFoundException`

<hr />

### enforceRateLimit

**Description:**

Enforce rate limit using the leaky bucket algorithm or aborts with a `429 Too Many Requests` HTTP status.

This method sets the following header values:

- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`
- `Retry-After` (When bucket is full)

**Parameters:**

- `$bucket_id` (string)
- `$limit` (int): Limit per minute

**Returns:**

- (void)

**Throws:**

- `Bayfront\LeakyBucket\AdapterException`
- `Bayfront\LeakyBucket\BucketException`
- `Bayfront\Bones\Exceptions\HttpException`
- `Bayfront\HttpResponse\InvalidStatusCodeException`
- `Bayfront\Container\NotFoundException`

<hr />

### resetRateLimit

**Description:**

Delete bucket used for rate limiting.

**Parameters:**

- `$bucket_id` (string)
- `$limit` (int): Limit per minute

**Returns:**

- (void)

**Throws:**

- `Bayfront\LeakyBucket\AdapterException`
- `Bayfront\LeakyBucket\BucketException`
- `Bayfront\Container\NotFoundException`

<hr />

### getBody

**Description:**

Checks optional required `Content-Type` header, and aborts with a `415 Unsupported Media Type` HTTP status if is missing or invalid.

Checks request body is valid JSON with optional required properties, or aborts with a `400 Bad Request` HTTP status if invalid.

**Parameters:**

- `$required_properties = []` (array)

**Returns:**

- (array): Request body

**Throws:**

- `Bayfront\Bones\Exceptions\HttpException`
- `Bayfront\Container\NotFoundException`
- `Bayfront\HttpResponse\InvalidStatusCodeException`

<hr />

### parseQuery

**Description:**

Parse the query string from the request to extract values needed to build a database query.

The query string is parsed according to the [JSON:API v1.0 spec](https://jsonapi.org/format/#fetching).

The following parameters are analyzed from the query:

- `fields`
- `filter`
- `sort`
- `page`

This method returns an array with the following keys:

- `fields`
- `filters`
- `order_by`
- `limit`
- `offset`

For more information, see: [Simple PDO query builder](https://github.com/bayfrontmedia/simple-pdo/blob/master/_docs/query-builder.md).

**Parameters:**

- `$query` (array)
- `$page_size = 10` (int)

**Returns:**

- (array)

**Throws:**

- `Bayfront\Bones\Exceptions\HttpException`

**Example:**

Request query string:

```
?fields[items]=name,color,quantity&filter[price][gt]=20.00&sort=-name&page[size]=5&page[number]=4
```

Example usage in the controller, assuming `$this->api` is an instance of the `BonesApi` service:

```
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\HttpRequest\Request;

try {
    $request = $this->api->parseQuery(Request::getQuery());
} catch (HttpException $e) {
    abort(400, $e->getMessage());
}
```

In the above example, the `$request` array would be:

```
[
    `fields` => [
        `items` => [
            'name',
            'color',
            'quantity'
        ]
    ],
    `filters` => [
        `price` => [
            'gt' => '20.00'
        ]
    ],
    'order_by' => [
        '-name'
    ],
    'limit' => '5',
    'offset' => '15'
]
```

That array can then be passed to the model and used to build the query:

```
$items = $this->model->getItems($request);
```

From the model, assuming `$this->db` is an instance of Simple PDO (see [Models](https://github.com/bayfrontmedia/bones/blob/master/_docs/models.md)):

```
use Bayfront\PDO\Query;

public function getItems(array $request): array
{

    $query = new Query($this->db);

    $query->table('items')
        ->select(Arr::get($request, 'fields.items', ['*']))
        ->limit($request['limit'])
        ->offset($request['offset'])
        ->orderBy($request['order_by']);

    foreach ($request['filters'] as $column => $filter) {

        foreach ($filter as $operator => $value) {

            $query->where($column, $operator, $value);

        }

    }

    return [
        'results' => $query->get(),
        'total' => $query->getTotalRows()
    ];

}
```