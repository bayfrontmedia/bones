<?php

namespace Bayfront\Bones\Application\Services\ApiService\Abstracts;

use Bayfront\Bones\Abstracts\Model;
use Bayfront\Bones\Application\Services\ApiService\ApiService;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiModelInterface;

abstract class ApiModel extends Model implements ApiModelInterface
{

    protected ApiService $apiService;

    public function __construct(ApiService $apiService)
    {

        $this->apiService = $apiService;

        $this->apiService->events->doEvent('api.model', $this);

        parent::__construct($this->apiService->events);

    }

}