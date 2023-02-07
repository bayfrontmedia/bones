<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Container\Container;
use Bayfront\PDO\Db;
use DirectoryIterator;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateUp extends Command
{

    protected Container $container;
    protected Db $db;

    public function __construct(Container $container, Db $db)
    {
        $this->container = $container;
        $this->db = $db;

        parent::__construct();
    }

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('migrate:up')
            ->setDescription('Run database migrations')
            ->addOption('file', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $opt_files = $input->getOption('file');

        $dir = App::basePath('/database/migrations');

        $migrations = [];

        if (is_dir($dir)) {

            $list = new DirectoryIterator($dir);

            foreach ($list as $item) {

                if ($item->isFile() && $item->getExtension() == 'php') {

                    $file_exp = explode('_', $item->getFileName(), 2);

                    if (isset($file_exp[1])) { // Valid filename format

                        if (empty($opt_files) || in_array($item->getFileName(), $opt_files)) {

                            $migrations[] = [
                                'class' => basename($file_exp[1], '.php'),
                                'file' => $item->getFileName()
                            ];

                        }

                    }

                }

            }

            if (empty($migrations)) {
                $output->writeln('<info>No migrations found.</info>');
                return Command::SUCCESS;
            }

            $migrations = Arr::multisort($migrations, 'file'); // Sort by filename

            foreach ($migrations as $migration) {

                $output->writeln('Running migration: ' . $migration['file']);

                $class = $this->container->make($migration['class']);
                $class->up();

            }

            $output->writeln('<info>Migration complete!</info>');
            return Command::SUCCESS;

        }

        $output->writeln('<info>No migrations found.</info>');
        return Command::SUCCESS;

    }

}