<?php

namespace _namespace_\Controllers;

use Bayfront\Bones\Abstracts\Controller;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\HttpResponse\Response;

/**
 * Home controller.
 *
 * Created with Bones v_bones_version_
 */
class Home extends Controller
{

    protected $events;
    protected $filters;
    protected $response;

    /**
     * The container will resolve any dependencies.
     * EventService is required by the abstract controller.
     */

    public function __construct(EventService $events, FilterService $filters, Response $response)
    {
        $this->events = $events;
        $this->filters = $filters;
        $this->response = $response;

        parent::__construct($events);
    }

    public function index(array $params)
    {

        $body = '<h1>Controller: Home</h1><h2>Method: index</h2><h2>Parameters:</h2><ul>';

        if (empty($params)) {
            $body .= '<li><strong>None</strong>';
        } else {
            foreach ($params as $k => $v) {
                $body .= '<li><strong>' . $k . ':</strong> <code>' . $v . '</code></li>';
            }
        }
        $body .= '</ul>';

        $this->response->setBody($this->filters->doFilter('response.body', $body))->send();

    }

}