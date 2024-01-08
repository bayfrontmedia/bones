<?php

namespace _namespace_\Controllers;

use Bayfront\Bones\Abstracts\Controller;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\HttpResponse\Response;
use Bayfront\Veil\FileNotFoundException;
use Bayfront\Veil\Veil;

/**
 * Veil example controller.
 *
 * Created with Bones v_bones_version_
 */
class VeilExample extends Controller
{

    protected EventService $events;
    protected FilterService $filters;
    protected Response $response;
    protected Veil $veil;

    /**
     * The container will resolve any dependencies.
     * EventService is required by the abstract controller.
     */

    public function __construct(EventService $events, FilterService $filters, Response $response, Veil $veil)
    {
        $this->events = $events;
        $this->filters = $filters;
        $this->response = $response;
        $this->veil = $veil;

        parent::__construct($events);
    }

    /**
     * @throws FileNotFoundException
     */

    public function index(array $params): void
    {

        $data = $this->filters->doFilter('veil.data', [
            'page' => [
                'title' => 'Homepage',
                'description' => 'This is a homepage example'
            ],
            'year' => date('Y'),
            'params' => $params
        ]);

        $this->response->setBody(
            $this->filters->doFilter('response.body', $this->veil->getView('examples/pages/home', $data))
        )->send();

    }

}