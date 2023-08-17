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

        $clear_all = false;

        if (!$input->getOption('commands')
            && !$input->getOption('events')
            && !$input->getOption('filters')) {

            $clear_all = true;

        }

        // ------------------------- Commands -------------------------

        if ($clear_all || $input->getOption('commands')) {

            if (is_file(App::storagePath('/bones/cache/commands.json'))) {
                unlink(App::storagePath('/bones/cache/commands.json'));
            }

            $output->writeln('<info>Successfully cleared console commands cache!</info>');
        }

        // ------------------------- Events -------------------------

        if ($clear_all || $input->getOption('events')) {

            if (is_file(App::storagePath('/bones/cache/events.json'))) {
                unlink(App::storagePath('/bones/cache/events.json'));
            }

            $output->writeln('<info>Successfully cleared events cache!</info>');
        }

        // ------------------------- Filters -------------------------

        if ($clear_all || $input->getOption('filters')) {

            if (is_file(App::storagePath('/bones/cache/filters.json'))) {
                unlink(App::storagePath('/bones/cache/filters.json'));
            }

            $output->writeln('<info>Successfully cleared filters cache!</info>');
        }

        return Command::SUCCESS;

    }

}