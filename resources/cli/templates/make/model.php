<?php

namespace _namespace_\Models;

use Bayfront\Bones\Abstracts\Model;
use Bayfront\Bones\Application\Services\EventService;

/**
 * _model_name_ model.
 *
 * Created with Bones v_bones_version_
 */
class _model_name_ extends Model
{

    protected $events;

    /**
     * The container will resolve any dependencies.
     * EventService is required by the abstract model.
     *
     * @param EventService $events
     */

    public function __construct(EventService $events)
    {
        $this->events = $events;

        parent::__construct($events);
    }

}