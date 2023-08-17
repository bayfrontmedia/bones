<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Utilities\App;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClear extends Command
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('cache:clear')
            ->setDescription('Clear cache')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Clear all cache')
            ->addOption('commands', null, InputOption::VALUE_NONE, 'Clear console commands cache')
            ->addOption('events', null, InputOption::VALUE_NONE, 'Clear events cache')
            ->addOption('filters', null, InputOption::VALUE_NONE, 'Clear filters cache');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        // ------------------------- Commands -------------------------

        if ($input->getOption('all') || $input->getOption('commands')) {
            unlink(App::storagePath('/bones/cache/commands.json'));
            $output->writeln('<info>Successfully cleared console commands cache!</info>');
        }

        // ------------------------- Events -------------------------

        if ($input->getOption('all') || $input->getOption('events')) {
            unlink(App::storagePath('/bones/cache/events.json'));
            $output->writeln('<info>Successfully cleared events cache!</info>');
        }

        // ------------------------- Filters -------------------------

        if ($input->getOption('all') || $input->getOption('filters')) {
            unlink(App::storagePath('/bones/cache/filters.json'));
            $output->writeln('<info>Successfully cleared filters cache!</info>');
        }

        return Command::SUCCESS;

    }

}