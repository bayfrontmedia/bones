<?php /** @noinspection PhpUnused */

namespace Bayfront\Bones\Application\Services\ApiService;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Events\ApiEvents;
use Bayfront\Bones\Application\Services\ApiService\Exceptions\ApiServiceException;
use Bayfront\Bones\Application\Services\ApiService\Exceptions\Http\BadRequestException;
use Bayfront\Bones\Application\Services\ApiService\Filters\ApiFilters;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiSchemaInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiSpecInterface;
use Bayfront\Bones\Application\Services\Events\EventService;
use Bayfront\Bones\Application\Services\Filters\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\ServiceException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class ApiService
{

    public EventService $events; // Needed by the Bones abstract controller (public)
    protected FilterService $filters;
    protected Response $response;
    public ApiSpecInterface $spec;
    protected array $config;

    /**
     * @throws ApiExceptionInterface
     */
    public function __construct(ApiSpecInterface $spec, array $config)
    {

        try {
            $this->events = App::get('events');
            $this->filters = App::get('filters');
            $this->response = App::get('response');
        } catch (NotFoundException $e) {
            throw new ApiServiceException('Unable to start ApiService: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        $this->spec = $spec;
        $this->config = $config;

        // Validate config

        if (Arr::isMissing(Arr::dot($this->config), [
        ])) {
            throw new ApiServiceException('Unable to start ApiService: invalid configuration');
        }

        // Enqueue events

        try {
            $this->events->addSubscriptions(new ApiEvents());
        } catch (ServiceException $e) {
            throw new ApiServiceException('Unable to start ApiService: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        // Enqueue filters

        try {
            $this->filters->addSubscriptions(new ApiFilters($this));
        } catch (ServiceException $e) {
            throw new ApiServiceException('Unable to start ApiService: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        // Do event

        $this->events->doEvent('api.start', $this);

    }

    // Request methods
    public const METHOD_GET = 'get';
    public const METHOD_PUT = 'put';
    public const METHOD_POST = 'post';
    public const METHOD_DELETE = 'delete';
    public const METHOD_OPTIONS = 'options';
    public const METHOD_HEAD = 'head';
    public const METHOD_PATCH = 'patch';
    public const METHOD_TRACE = 'trace';

    /**
     * Send API response.
     *
     * @param array $data
     * @param string $path
     * @param string $http_method
     * @param string $http_status
     * @return void
     * @throws APiExceptionInterface
     */
    public function respond(array $data, string $path, string $http_method, string $http_status): void
    {

        $path = $this->spec->getPath($path, $http_method);
        $response = $path->getResponseObject($http_status);
        $schema = $response->getSchemaObject();

        // ------------------------- Filter -------------------------

        // Predefined meta: this can be filtered out later
        $data['meta']['schema'] = $schema->getName();
        $data = (array)$this->filters->doFilter('api.response', $data); // Ensure returned from filter as an array

        // ------------------------- Validate data against spec -------------------------

        $schema_properties = $schema->getProperties();

        // Allowed properties

        if (!empty(Arr::except($data, array_keys($schema_properties)))) {
            throw new BadRequestException('Unacceptable properties');
        }

        // Required properties

        if (Arr::isMissing($data, $schema->getRequiredProperties())) {
            throw new BadRequestException('Missing required properties');
        }

        // Validate properties

        foreach ($data as $k => $v) {

            if (Arr::has($schema_properties, $k . '.type')) {

                // Expected type

                $property_type = gettype($v);

                if ($property_type !== (string)Arr::get($schema_properties, $k . '.type')) {
                    throw new BadRequestException('Invalid property type');
                }

                // Integer: minimum/maximum

                if ($property_type == 'integer') {

                    if (Arr::has($schema_properties, $k . '.minimum') && $v < (int)Arr::get($schema_properties, $k . '.minimum')) {
                        throw new BadRequestException('Property (' . $k . ') value (' . $v . ') is below the minimum permitted (' . (int)Arr::get($schema_properties, $k . '.minimum') . ')');
                    }

                    if (Arr::has($schema_properties, $k . '.maximum') && $v > (int)Arr::get($schema_properties, $k . '.maximum')) {
                        throw new BadRequestException('Property (' . $k . ') value (' . $v . ') is above the maximum permitted (' . (int)Arr::get($schema_properties, $k . '.maximum') . ')');
                    }

                }

            }

        }

        $data = (array)$this->filters->doFilter('api.response.raw', $data); // Ensure returned from filter as an array

        // ------------------------- Create API schema -------------------------

        $schema_class = App::getConfig('app.namespace', '') . 'Schemas\\' . $schema->getName();

        if (!class_exists($schema_class)) {
            throw new ApiServiceException('Unable to respond: missing schema (' . $schema->getName() . ')');
        }

        $sc = new $schema_class;

        if (!$sc instanceof ApiSchemaInterface) {
            throw new ApiServiceException('Unable to respond: schema (' . $schema->getName() . ') does not inherit required interface (ApiSchemaInterface)');
        }

        /** @var ApiSchemaInterface $schema_class */
        $response = $schema_class::create($path, $response, $schema, $data);

        $this->events->doEvent('api.end', $response);

        $this->response->sendJson($response);

    }

    /**
     * Handle an API exception and abort script execution.
     *
     * @param ApiExceptionInterface $e
     * @return never-return
     */
    public function abort(ApiExceptionInterface $e): void
    {

        try {

            $this->response->setStatusCode($e->getHttpStatusCode());

            /*
             * Do api.exception event
             *
             * Pass the exception and response as arguments to the event.
             */

            $this->events->doEvent('api.exception', $this->response, $e);
            $this->events->doEvent('api.end', $this->response);

            throw $e;

        } catch (InvalidStatusCodeException) {
            throw $e;
        }

    }

}