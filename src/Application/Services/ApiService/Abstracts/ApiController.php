<?php

namespace Bayfront\Bones\Application\Services\ApiService\Abstracts;

use Bayfront\Bones\Abstracts\Controller;
use Bayfront\Bones\Application\Services\ApiService\ApiService;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiControllerInterface;
use Bayfront\Bones\Application\Services\Events\EventService;

abstract class ApiController extends Controller implements ApiControllerInterface
{

    protected ApiService $apiService;

    public function __construct(EventService $events, ApiService $apiService)
    {
        $this->apiService = $apiService;

        parent::__construct($events);
    }

}