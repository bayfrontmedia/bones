<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Utilities\App;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeKey extends Command
{

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('make:key')
            ->setDescription('Create a cryptographically secure key');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>' . App::createKey() . '</info>');
        return Command::SUCCESS;
    }


}