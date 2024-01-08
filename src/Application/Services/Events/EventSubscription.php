<?php

namespace Bayfront\Bones\Application\Services\Events;

class EventSubscription {

    protected string $event_name;
    protected $function;
    protected int $priority;

    public function __construct(string $event_name, callable $function, int $priority = 10)
    {
        $this->event_name = $event_name;
        $this->function = $function;
        $this->priority = $priority;
    }

    public function getName(): string
    {
        return $this->event_name;
    }

    public function getFunction(): callable
    {
        return $this->function;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

}