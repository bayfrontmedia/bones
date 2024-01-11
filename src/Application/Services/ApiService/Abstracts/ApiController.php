<?php

namespace Bayfront\Bones\Application\Services\ApiService\Abstracts;

use Bayfront\Bones\Abstracts\Controller;
use Bayfront\Bones\Application\Services\ApiService\ApiService;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiControllerInterface;

abstract class ApiController extends Controller implements ApiControllerInterface
{

    protected ApiService $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;

        parent::__construct($this->apiService->events);
    }

}