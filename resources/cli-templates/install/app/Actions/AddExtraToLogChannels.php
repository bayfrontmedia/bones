<?php

namespace App\Actions;

use Bayfront\Bones\Action;
use Bayfront\Bones\Interfaces\ActionInterface;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\MonologFactory\Exceptions\ChannelNotFoundException;

/**
 * AddExtraToLogChannels action.
 *
 * Adds IP and request URL when app interface is HTTP.
 */
class AddExtraToLogChannels extends Action implements ActionInterface
{

    /**
     * @inheritDoc
     */

    public function isActive(): bool
    {
        return $this->container->has('Bayfront\MonologFactory\LoggerFactory');
    }

    /**
     * @inheritDoc
     */

    public function getEvents(): array
    {

        return [
            'app.http' => 5
        ];
    }

    /**
     * @inheritDoc
     * @throws NotFoundException
     * @throws ChannelNotFoundException
     */

    public function action(...$arg)
    {

        if (!$this->container->has('Bayfront\MonologFactory\LoggerFactory')) {
            return;
        }

        $logs = $this->container->get('Bayfront\MonologFactory\LoggerFactory');

        $channels = $logs->getChannels();

        foreach ($channels as $channel) {

            $logs->getChannel($channel)->pushProcessor(function ($record) {

                $record['extra']['ip'] = Request::getIp();
                $record['extra']['url'] = Request::getUrl(true);

                return $record;

            });

        }

    }

}