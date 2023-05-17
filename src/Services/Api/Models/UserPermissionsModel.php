<?php

namespace Bayfront\Bones\Services\Api\Models;

use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Services\Api\Controllers\Abstracts\PrivateApiController;
use Bayfront\HttpResponse\Response;

class UserPermissionsModel extends PrivateApiController
{

    public function __construct(EventService $events, FilterService $filters, Response $response)
    {
        parent::__construct($events, $filters, $response);
    }



}