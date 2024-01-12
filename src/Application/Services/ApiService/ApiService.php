<?php /** @noinspection PhpUnused */

namespace Bayfront\Bones\Application\Services\ApiService;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiSpecificationInterface;
use Bayfront\Bones\Application\Services\Events\EventService;
use Bayfront\Bones\Application\Services\Filters\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\InvalidArgumentException;
use Bayfront\Bones\Exceptions\InvalidConfigurationException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class ApiService
{

    public EventService $events; // Needed by the Bones abstract controller (public)
    protected FilterService $filters;
    protected Response $response;
    public ApiSpecificationInterface $spec;
    protected array $config;

    /**
     * @throws InvalidConfigurationException
     * @throws NotFoundException
     */
    public function __construct(ApiSpecificationInterface $spec, array $config)
    {
        $this->events = App::get('events');
        $this->filters = App::get('filters');
        $this->response = App::get('response');

        $this->spec = $spec;
        $this->config = $config;

        // Validate config

        if (Arr::isMissing(Arr::dot($this->config), [
        ])) {
            throw new InvalidConfigurationException('Unable to start ApiService: invalid configuration');
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
     * @param array $spec (Api specification): TODO: Needs to accept interface
     * @param string $response
     * @return void
     * @throws InvalidArgumentException
     */
    public function respond(array $data, array $spec, string $response): void
    {

        $data = (array)$this->filters->doFilter('api.response', $data); // Ensure returned from filter as an array

        // Check properties and required

        //$schema = $this->spec->getSchema($schema_name);

        //$properties = (array)Arr::get($schema, 'properties', []);
        //$required = (array)Arr::get($schema, 'required', []);

        print_r($data);
        die;


        $response = $schema::create($response, $schema_config);

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