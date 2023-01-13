<?php

namespace App\Actions;

use Bayfront\Bones\Action;
use Bayfront\Bones\Interfaces\ActionInterface;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\MonologFactory\Exceptions\ChannelNotFoundException;

class AddExtraToLogChannels extends Action implements ActionInterface
{

    /**
     * @inheritDoc
     */

    public function isActive(): bool
    {
        return is_http() && $this->container->has('logs');
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
     * @throws NotFoundException
     * @throws ChannelNotFoundException
     */

    public function action(...$arg)
    {

        $logs = $this->container->get('logs');

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