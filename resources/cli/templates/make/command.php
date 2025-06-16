<?php

namespace _namespace_\Console\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * _command_name_ command.
 *
 * Created with Bones v_bones_version_
 */
class _command_name_ extends Command
{

    /**
     * The container will resolve any dependencies.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {

        $this->setName('_command_command_')
            ->setDescription('');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $output->writeLn('_command_command_ ran successfully.');

        return Command::SUCCESS;
    }


}