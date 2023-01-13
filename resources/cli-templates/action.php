<?php

namespace _namespace_\Actions;

use Bayfront\Bones\Action;
use Bayfront\Bones\Interfaces\ActionInterface;

/**
 * _action_name_ action.
 *
 * Created for Bones v_bones_version_
 */
class _action_name_ extends Action implements ActionInterface
{

    /**
     * @inheritDoc
     */

    public function isActive(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */

    public function getEvents(): array
    {

        return [
            'bones.init' => 5
        ];
    }

    /**
     * @inheritDoc
     */

    public function action(...$arg)
    {
        // Do something
    }

}