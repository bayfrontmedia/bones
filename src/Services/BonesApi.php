<?php

/**
 * @package bones
 * @link https://github.com/bayfrontmedia/bones
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020-2021 Bayfront Media
 */

namespace Bayfront\Bones\Services;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Container\NotFoundException;
use Bayfront\Filesystem\Filesystem;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
use Bayfront\JWT\Jwt;
use Bayfront\JWT\TokenException;
use Bayfront\LeakyBucket\AdapterException;
use Bayfront\LeakyBucket\Adapters\Flysystem;
use Bayfront\LeakyBucket\Bucket;
use Bayfront\LeakyBucket\BucketException;
use Bayfront\Validator\Validate;
use DateTimeInterface;

class BonesApi
{

    protected $response;

    protected $config;

    public function __construct(Response $response, array $config)
    {
        $this->response = $response;

        $this->config = $config;
    }

    /**
     * Initialize the API environment.
     *
     * Removes the `X-Powered-By` header, and sets `X-Content-Type-Options`, `X-XSS-Protection` and `X-Frame-Options`
     * headers to increase security.
     *
     * Checks for maintenance mode and aborts with a "503 Service Unavailable" HTTP status
     * and sets "Retry-After" header if "api.maintenance_until" config is set.
     *
     * Forces HTTPS and proper "Accept" header (optional), or aborts with a
     * "406 Not Acceptable" HTTP status.
     *
     * Defines the "IS_API" constant which can be used throughout the app, if needed.
     *
     * @param bool $check_accept (Check for a valid Accept header)
     *
     * @return void
     *
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */

    public function start(bool $check_accept = true): void
    {

        header_remove('X-Powered-By');

        $this->response->setHeaders([
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'X-Frame-Options' => 'DENY'
        ]);

        // Check for maintenance mode

        if (Arr::get($this->config, 'maintenance_mode')) {

            $until = Arr::get($this->config, 'maintenance_until');

            if ($until instanceof DateTimeInterface && $until->getTimestamp() > time()) {

                abort(503, 'Server undergoing routine maintenance until ' . $until->format('Y-m-d H:i:s T'), [
                    'Retry-After' => $until->getTimestamp() - time()
                ]);

            } else {

                abort(503, 'Server undergoing routine maintenance');

            }

        }

        // Force https

        $environments = (array)Arr::get($this->config, 'allow_http', []);

        if (!Request::isHttps() && !in_array(get_config('app.environment'), $environments)) {

            abort(406, 'All requests must be made over HTTPS');

        }

        // Check valid Accept header

        if (true === $check_accept
            && Arr::get($this->config, 'accept_header')
            && Request::getHeader('Accept') != Arr::get($this->config, 'accept_header')) {

            abort(406, 'Required header is missing or invalid: Accept');

        }

        define('IS_API', true); // Define constant

    }

    /**
     * Checks request method is allowed or aborts with a "405 Method Not Allowed" HTTP status,
     * and a list of allowed methods in the "Allow" header.
     *
     * Always includes the "Allow" header when the "allow_method_discovery" config key is TRUE.
     *
     * @param string|array $methods (Allowed request methods)
     *
     * @return void
     *
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */

    public function allowedMethods($methods): void
    {

        $methods = (array)$methods;

        if (true === Arr::get($this->config, 'allow_method_discovery', false)) {

            $this->response->setHeaders([
                'Allow' => implode(', ', $methods)
            ]);

        }

        $request_method = Request::getMethod();

        if (!in_array($request_method, $methods)) {

            abort(405, 'Request method (' . $request_method . ') not allowed', [
                'Allow' => implode(', ', $methods)
            ]);

        }

    }

    /**
     * Checks that a valid JWT exists in the "Authorization" header or
     * enforces the "auth_rate_limit" and aborts with a "401 Unauthorized" HTTP status.
     *
     * @return array (JWT contents)
     *
     * @throws AdapterException
     * @throws BucketException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */

    public function authenticateJwt(): array
    {

        // Validate token

        $jwt = new Jwt(get_config('app.key'));

        try {

            $token = $jwt->decode(Request::getHeader('Authorization', ''));

        } catch (TokenException $e) {

            $this->enforceRateLimit('auth-' . Request::getIp(), get_config('api.auth_rate_limit', 5));

            abort(401, 'Missing or invalid Bearer token');

            die; // Prevent "$token probably undefined" inspection

        }

        return $token;

    }

    /**
     * Enforce rate limit using the leaky bucket algorithm or aborts with a "429 Too Many Requests" HTTP status.
     *
     * This method sets the following header values:
     *
     * - X-RateLimit-Limit
     * - X-RateLimit-Remaining
     * - X-RateLimit-Reset
     * - Retry-After (When bucket is full)
     *
     * @param string $bucket_id
     * @param int $limit (Limit per minute)
     *
     * @return void
     *
     * @throws AdapterException
     * @throws BucketException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */

    public function enforceRateLimit(string $bucket_id, int $limit): void
    {

        /** @var Filesystem $filesystem */

        $filesystem = get_from_container('filesystem');

        $bucket = new Bucket('api.ratelimit.' . $bucket_id, new Flysystem($filesystem->getDefaultDisk(), get_config('api.buckets_path', '/app/buckets')), [
            'capacity' => $limit, // Total drop capacity
            'leak' => $limit // Number of drops to leak per minute
        ]);

        try {

            $bucket->leak()->fill(1)->save();

        } catch (BucketException $e) { // Bucket is full

            abort(429, 'Rate limit exceeded. Try again in ' . round($bucket->getSecondsPerDrop()) . ' seconds', [
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => floor($bucket->getCapacityRemaining()), // Round down
                'X-RateLimit-Reset' => round($bucket->getSecondsUntilEmpty()),
                'Retry-After' => round($bucket->getSecondsPerDrop()) // Round
            ]);

        }

        // Set headers

        $this->response->setHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => floor($bucket->getCapacityRemaining()), // Round down
            'X-RateLimit-Reset' => round($bucket->getSecondsUntilEmpty())
        ]);

    }

    /**
     * Delete bucket used for rate limiting.
     *
     * @param string $bucket_id
     * @param int $limit (Limit per minute)
     *
     * @throws AdapterException
     * @throws BucketException
     * @throws NotFoundException
     */

    public function resetRateLimit(string $bucket_id, int $limit): void
    {

        /** @var Filesystem $filesystem */

        $filesystem = get_from_container('filesystem');

        $bucket = new Bucket('api.ratelimit.' . $bucket_id, new Flysystem($filesystem->getDefaultDisk(), get_config('api.buckets_path', '/app/buckets')), [
            'capacity' => $limit, // Total drop capacity
            'leak' => $limit // Number of drops to leak per minute
        ]);

        $bucket->delete();

        // Set headers

        $this->response->setHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => $limit,
            'X-RateLimit-Reset' => 0
        ]);

    }

    /**
     * Checks optional required Content-Type header, and aborts with a
     * "415 Unsupported Media Type" HTTP status if is missing or invalid.
     *
     * Checks request body is valid JSON with optional required properties,
     * or aborts with a "400 Bad Request" HTTP status if invalid.
     *
     * @param array $required_properties (Optional required properties)
     *
     * @return array (Request body)
     *
     * @throws HttpException
     * @throws NotFoundException
     * @throws InvalidStatusCodeException
     */

    public function getBody(array $required_properties = []): array
    {

        // Check Content-Type header

        if (Arr::get($this->config, 'content_type')
            && Request::getHeader('Content-Type') != Arr::get($this->config, 'content_type')) {

            abort(415, 'Content-Type header must be: ' . get_config('api.content_type'));

        }

        // Get body

        $body = Request::getBody();

        if (!Validate::json($body)) {

            abort(400, 'Invalid content body');

        }

        $body = json_decode($body, true);

        if (Arr::isMissing($body, $required_properties)) {

            abort(400, 'Content body missing required properties');

        }

        return $body;

    }

    /**
     * Parse the query string from the request to extract values needed to build a database query.
     *
     * The query string is parsed according to the JSON:API v1.0 spec
     * https://jsonapi.org/format/#fetching
     *
     * The following parameters are analyzed from the query:
     *
     * - fields
     * - filter
     * - sort
     * - page
     *
     * This method returns an array with the following keys:
     *
     * - fields
     * - filters
     * - order_by
     * - limit
     * - offset
     *
     * For more information, see:
     * https://github.com/bayfrontmedia/simple-pdo/blob/master/_docs/query-builder.md
     *
     * @param array $query (Query string as an array of values)
     * @param int $default_page_size (Default page size to return)
     * @param int $max_page_size (Max page size to return)
     *
     * @return array
     *
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */

    public function parseQuery(array $query, int $default_page_size = 10, int $max_page_size = 100): array
    {

        // Fields

        $fields = Arr::get($query, 'fields', []);

        if (!is_array($fields)) {

            abort(400, 'Malformed request: invalid field key(s)');

        }

        foreach ($fields as $k => $v) {

            if (!is_string($v)) {
                abort(400, 'Malformed request: invalid field value(s)');
            }

            if (strpos($v, ' ') !== false) { // Contains space
                abort(400, 'Malformed request: invalid field value(s)');
            }

            $fields[$k] = array_unique(explode(',', $v)); // Remove duplicate values

        }

        // Filter

        $filters = Arr::get($query, 'filter', []);

        if (!is_array($filters)) {
            abort(400, 'Malformed request: invalid filter type');
        }

        foreach ($filters as $filter) {

            if (!is_array($filter)) {
                abort(400, 'Malformed request: invalid filter value');
            }

        }

        // Sort

        $sort = Arr::get($query, 'sort', '');

        if (!is_string($sort)) {
            abort(400, 'Malformed request: invalid sort type');
        }

        if ($sort != '') {

            if (strpos($sort, ' ') !== false) { // Contains space
                abort(400, 'Malformed request: invalid sort value(s)');
            }

            $order = explode(',', $sort);

        } else {
            $order = [];
        }

        // Page

        $limit = (int)Arr::get($query, 'page.size', $default_page_size);

        $page_number = (int)Arr::get($query, 'page.number', 1);

        if (
            (!is_int($limit) || $limit < 1) ||
            (!is_int($page_number) || $page_number < 1)) {
            abort(400, 'Malformed request: invalid page value(s)');
        }

        if ($limit > $max_page_size) {
            abort(400, 'Malformed request: page size (' . $limit . ') exceeds maximum (' . $max_page_size . ')');
        }

        return [
            'fields' => $fields,
            'filters' => $filters,
            'order_by' => $order,
            'limit' => $limit,
            'offset' => $limit * ($page_number - 1)
        ];

    }

}