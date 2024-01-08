<?php

namespace Bayfront\Bones\Application\Services\Filters;

class FilterSubscription {

    protected string $filter_name;
    protected $function;
    protected int $priority;

    public function __construct(string $filter_name, callable $function, int $priority = 10)
    {
        $this->filter_name = $filter_name;
        $this->function = $function;
        $this->priority = $priority;
    }

    public function getName(): string
    {
        return $this->filter_name;
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