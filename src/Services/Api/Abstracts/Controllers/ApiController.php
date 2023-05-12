<?php

namespace Bayfront\Bones\Services\Api\Abstracts\Controllers;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Abstracts\Controller;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Container\ContainerException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
use Bayfront\LeakyBucket\AdapterException;
use Bayfront\LeakyBucket\Bucket;
use Bayfront\LeakyBucket\BucketException;
use Bayfront\Validator\Validate;
use DateTimeInterface;
use Exception;

abstract class ApiController extends Controller
{

    protected EventService $events;
    protected FilterService $filters;
    protected Response $response;

    /**
     * @param EventService $events
     * @param FilterService $filters
     * @param Response $response
     */
    public function __construct(EventService $events, FilterService $filters, Response $response)
    {
        $this->events = $events;
        $this->filters = $filters;
        $this->response = $response;

        parent::__construct($events);
    }

    /**
     * Check if maintenance mode is enabled and abort with "503 Service Unavailable"
     * with optional "Retry-After" header.
     *
     * @return void
     * @throws HttpException
     * @throws NotFoundException
     * @throws InvalidStatusCodeException
     */
    private function checkMaintenanceMode(): void
    {

        if (App::getConfig('api.maintenance.enabled')) {

            $until = App::getConfig('api.maintenance.until');

            if ($until instanceof DateTimeInterface && $until->getTimestamp() > time()) {

                App::abort(503, 'API undergoing routine maintenance until ' . $until->format('Y-m-d H:i:s T'), [
                    'Retry-After' => $until->getTimestamp() - time()
                ]);

            } else {

                App::abort(503, 'API undergoing routine maintenance');

            }

        }

    }

    /**
     * Check request is made via HTTPS or abort with "406 Not Acceptable".
     *
     * @return void
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */
    private function checkHttps(): void
    {

        if (!Request::isHttps() && in_array(App::environment(), App::getConfig('api.https_env'))) {
            App::abort(406, 'All requests must be made over HTTPS');
        }

    }

    /**
     * Check Accepts header exists and is valid or abort with "406 Not Acceptable".
     *
     * @return void
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */
    private function checkAcceptsHeader(): void
    {

        if (Request::getHeader('Accept') !== App::getConfig('api.request.header.accept')) {
            App::abort(406, 'Required header is missing or invalid: Accept');
        }

    }

    /**
     * Check valid Content-Type header if request has body or abort with "406 Not Acceptable".
     *
     * @return void
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */
    private function checkContentType(): void
    {

        if (Request::hasBody() && Request::getHeader('Content-Type') !== App::getConfig('api.request.header.content_type')) {
            App::abort(406, 'Required header is missing or invalid: Content-Type');
        }

    }

    /**
     * Initialize the API environment.
     *
     * @return void
     * @throws UnexpectedApiException
     */
    protected function initApi(): void
    {
        try {
            $this->checkMaintenanceMode();
            $this->checkHttps();
            $this->checkAcceptsHeader();
            $this->checkContentType();
        } catch (Exception $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

    }

    /**
     * Fills bucket and checks rate limit or aborts with "429 Too Many Requests" HTTP status,
     * and sets X-RateLimit headers.
     *
     * @param string $id
     * @param int $limit
     * @return void
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    protected function rateLimitOrAbort(string $id, int $limit): void
    {

        try {

            /**
             * @var Bucket $bucket
             */

            $bucket = App::make('Bayfront\LeakyBucket\Bucket', [
                'id' => $id,
                'settings' => [
                    'capacity' => $limit,
                    'leak' => 1
                ]
            ]);

        } catch (ContainerException|NotFoundException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        try {

            try {

                $bucket->leak()->fill()->save();

            } catch (BucketException) {

                $wait = round($bucket->getSecondsUntilCapacity());

                App::abort(429, 'Rate limit exceeded. Try again in ' . $wait . ' seconds', [
                    'X-RateLimit-Limit' => $limit,
                    'X-RateLimit-Remaining' => floor($bucket->getCapacityRemaining()),
                    'X-RateLimit-Reset' => round($bucket->getSecondsUntilEmpty()),
                    'Retry-After' => $wait
                ]);

            }

        } catch (AdapterException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

        // Set headers

        $this->response->setHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => floor($bucket->getCapacityRemaining()),
            'X-RateLimit-Reset' => round($bucket->getSecondsUntilEmpty())
        ]);

    }

    /**
     * Reset rate limit by deleting bucket.
     *
     * @param string $bucket_name
     * @return void
     * @throws UnexpectedApiException
     */
    protected function resetRateLimit(string $bucket_name): void
    {

        try {

            /**
             * @var Bucket $bucket
             */
            $bucket = App::make('Bayfront\LeakyBucket\Bucket', [
                'id' => $bucket_name
            ]);

            $bucket->delete();

        } catch (Exception $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

    }

    /**
     * Checks current request method is allowed or aborts with a "405 Method Not Allowed"
     * HTTP status, and a list of allowed methods in the "Allow" header.
     *
     * This is only needed if the router dispatches multiple request methods to the same endpoint.
     *
     * @param array $methods
     * @param bool $enable_discovery (Always add allowed methods to Allow header)
     * @return void
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */
    public function acceptMethodsOrAbort(array $methods, bool $enable_discovery = true): void
    {

        $method = Request::getMethod();

        if (!in_array($method, $methods)) {

            App::abort(405, 'Request method (' . $method . ') not allowed', [
                'Allow' => implode(', ', $methods)
            ]);

        }

        if ($enable_discovery) {

            $this->response->setHeaders([
                'Allow' => implode(', ', $methods)
            ]);

        }

    }

    /**
     * Checks request body is valid JSON with optional required and allowed members,
     * or aborts with a "400 Bad Request" HTTP status.
     *
     * @param array $required
     * @param array $allowed
     * @return array
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */
    public function getBodyOrAbort(array $required = [], array $allowed = []): array
    {

        $body = Request::getBody();

        if (!Validate::json($body)) {
            App::abort(400, 'Invalid content body');
        }

        $body = json_decode($body, true);

        if (Arr::isMissing($body, $required)) {
            App::abort(400, 'Content body missing required members');
        }

        if (!empty($allowed) && !empty(Arr::except($body, $allowed))) {
            App::abort(400, 'Content body contains invalid members');
        }

        return $body;

    }

    /**
     * Checks request body is valid JSON:API resource object with optional required and allowed
     * attributes, or aborts with a "400 Bad Request" or "409 Conflict" HTTP status.
     *
     * Returns "attributes" member.
     *
     * @param string $type
     * @param array $required (In dot notation)
     * @param array $allowed (In standard notation)
     * @return array
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */
    public function getResourceAttributesOrAbort(string $type, array $required = [], array $allowed = []): array
    {

        $body = $this->getBodyOrAbort([
            'data'
        ]);

        if (Arr::isMissing($body['data'], [
                'type',
                'attributes'
            ]) || Arr::isMissing(Arr::dot($body['data']['attributes']), $required)) {
            App::abort(400, 'Content body missing required members');
        }

        if (!empty(Arr::except($body['data']['attributes'], $allowed))) {
            App::abort(400, 'Content body contains invalid members');
        }

        if ($body['data']['type'] !== $type) {
            App::abort(409, 'Invalid resource object type');
        }

        return $body['data']['attributes'];

    }

    /**
     * Get requested fields from URL request query parameter, or abort if invalid.
     * Used when fetching a single resource.
     *
     * See: https://jsonapi.org/format/#fetching-sparse-fieldsets
     *
     * @param array $query (Array of query string parameters)
     * @param string $fields_key (Return only specific key from fields parameter)
     * @param array $allowed_fields (Aborts with 400 if any other fields exist)
     * @param array $default (Default array to return if no valid fields exist in the request)
     * @return array
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */
    public function parseFieldsQueryOrAbort(array $query, string $fields_key, array $allowed_fields = [], array $default = []): array
    {

        $fields = explode(',', Arr::get($query, 'fields.' . $fields_key, ''));

        $fields = array_filter($fields); // Remove empty values

        if (!empty($allowed_fields)) {

            foreach ($fields as $v) { // Drop all not allowed

                if (!in_array($v, $allowed_fields)) {
                    App::abort(400, 'Malformed request: Invalid field value(s)');
                }

            }

        }

        if (empty($fields)) {
            return $default;
        }

        return $fields;

    }

    /**
     * Parse the query string from the request to extract values needed to build a database query for a collection.
     *
     * The query string is parsed according to the JSON:API v1.1 spec
     * https://jsonapi.org/format/#fetching
     *
     * The following parameters are analyzed from the query:
     *
     * - fields => select
     * - filter => where
     * - include => include
     * - sort => orderBy
     * - page => offset/limit
     *
     * This method returns an array with the following keys:
     *
     * - select
     * - where
     * - include
     * - orderBy (if specified)
     * - limit
     * - offset
     *
     * The resulting array can be used with the queryCollection method of the ApiModel.
     *
     * For more information, see:
     * https://github.com/bayfrontmedia/simple-pdo/blob/master/_docs/query-builder.md
     *
     * @param array $query (Array of query string parameters)
     * @param string $fields_key (Return only specific key from fields parameter)
     * @return array
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     */
    public function parseCollectionQueryOrAbort(array $query, string $fields_key = ''): array
    {

        // Fields

        $fields = Arr::get($query, 'fields', ['*']);

        if (!is_array($fields)) {
            App::abort(400, 'Malformed request: Invalid field key(s)');
        }

        foreach ($fields as $k => $v) {

            if ($v == '') { // No fields specified

                $fields[$k] = [];

                continue;

            }

            if (!is_string($v)) {
                App::abort(400, 'Malformed request: Invalid field value(s)');
            }

            if (str_contains($v, ' ')) { // Contains space
                App::abort(400, 'Malformed request: Invalid field value(s)');
            }

            $fields[$k] = array_unique(explode(',', $v)); // Remove duplicate values

        }

        if ($fields_key !== '') {
            $fields = Arr::get($fields, $fields_key, []);
        }

        // Filter

        $filters = Arr::get($query, 'filter', []);

        if (!is_array($filters)) {
            App::abort(400, 'Malformed request: Invalid filter type');
        }

        foreach ($filters as $filter) {

            if (!is_array($filter)) {
                App::abort(400, 'Malformed request: Invalid filter value');
            }

        }

        // Include

        $include = Arr::get($query, 'include', '');

        if (!is_string($include)) {
            App::abort(400, 'Malformed request: Invalid include value');
        }

        if ($include != '') {

            if (str_contains($include, ' ')) { // Contains space
                App::abort(400, 'Malformed request: Invalid include value(s)');
            }

            $include_arr = explode(',', $include);

        } else {
            $include_arr = [];
        }

        // Sort

        $sort = Arr::get($query, 'sort', '');

        if (!is_string($sort)) {
            App::abort(400, 'Malformed request: Invalid sort type');
        }

        if ($sort != '') {

            if (str_contains($sort, ' ')) { // Contains space
                App::abort(400, 'Malformed request: Invalid sort value(s)');
            }

            $order = explode(',', $sort);

        } else {
            $order = [];
        }

        // Page

        $limit = (int)Arr::get($query, 'page.size', App::getConfig('api.response.collection_size.default'));

        $page_number = (int)Arr::get($query, 'page.number', 1);

        if (
            ($limit < 1) || $page_number < 1) {
            App::abort(400, 'Malformed request: Invalid page value(s)');
        }

        if ($limit > App::getConfig('api.response.collection_size.max')) {
            App::abort(400, 'Malformed request: Page size (' . $limit . ') exceeds maximum (' . App::getConfig('api.response.collection_size.max') . ')');
        }

        if (empty($order)) {

            return [
                'select' => $fields,
                'where' => $filters,
                'include' => $include_arr,
                'limit' => $limit,
                'offset' => $limit * ($page_number - 1)
            ];

        }

        return [
            'select' => $fields,
            'where' => $filters,
            'include' => $include_arr,
            'orderBy' => $order,
            'limit' => $limit,
            'offset' => $limit * ($page_number - 1)
        ];

    }

}