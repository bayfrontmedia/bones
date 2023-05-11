<?php

namespace Bayfront\Bones\Services\Api;

use Bayfront\Bones\Abstracts\Service;
use Bayfront\Bones\Application\Services\EventService;

class ApiService extends Service
{

    public function __construct(EventService $events)
    {
        parent::__construct($events);
    }

}