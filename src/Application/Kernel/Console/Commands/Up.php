<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Services\Events\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Up extends Command
{

    protected EventService $events;

    public function __construct(EventService $events, string $name = null)
    {
        $this->events = $events;
        parent::__construct($name);
    }

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('up')
            ->setDescription('Take Bones out of maintenance mode');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $dest = App::storagePath('/bones/down.json');

        if (is_file($dest)) {

            $deleted = unlink($dest);

            if (!$deleted) {
                $output->writeln('<error>Failed to exit maintenance mode: Unable to remove file (' . $dest . ') </error>');
                return Command::FAILURE;
            }

        }

        $this->events->doEvent('bones.up');

        $output->writeln('<info>Successfully exited maintenance mode!</info>');
        return Command::SUCCESS;

    }

}