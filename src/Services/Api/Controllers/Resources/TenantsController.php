<?php

namespace Bayfront\Bones\Services\Api\Controllers\Resources;

use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Services\Api\Abstracts\Controllers\PrivateApiController;
use Bayfront\HttpResponse\Response;

class TenantsController extends PrivateApiController
{

    public function __construct(EventService $events, FilterService $filters, Response $response)
    {
        parent::__construct($events, $filters, $response);
    }

    public function create(): void
    {

    }

    public function getCollection(): void
    {

    }

    public function get(array $args): void
    {

    }

    public function update(array $args): void
    {

    }

    public function delete(array $args): void
    {

    }

}