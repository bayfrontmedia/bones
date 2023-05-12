<?php

namespace Bayfront\Bones\Services\Api\Controllers\Resources;

use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Services\Api\Abstracts\Controllers\PrivateApiController;
use Bayfront\HttpResponse\Response;

class UsersController extends PrivateApiController
{

    public function __construct(EventService $events, FilterService $filters, Response $response)
    {
        parent::__construct($events, $filters, $response);
    }

    public function create(): void
    {
        die('create');
    }

    public function getCollection(): void
    {
        die('get collection');
    }

    public function get(array $args): void
    {
        echo 'get';
        print_r($args);
    }

    public function update(array $args): void
    {
        echo 'update';
        print_r($args);
    }

    public function delete(array $args): void
    {
        echo 'delete';
        print_r($args);
    }


}