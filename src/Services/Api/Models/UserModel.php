<?php

namespace Bayfront\Bones\Services\Api\Models;

use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Services\Api\Abstracts\Models\ApiModel;
use Bayfront\PDO\Db;
use Monolog\Logger;

/**
 * This should only be instantiated after the user ID
 * has been confirmed to exist.
 */
class UserModel extends ApiModel
{

    private string $user_id;
    private int $rate_limit;

    public function __construct(EventService $events, Db $db, Logger $log, string $user_id, int $rate_limit = 0)
    {
        $this->user_id = $user_id;
        $this->rate_limit = $rate_limit;

        parent::__construct($events, $db, $log);
    }

    public function getId(): string
    {
        return $this->user_id;
    }

    public function getRateLimit(): int
    {
        return $this->rate_limit;
    }



}