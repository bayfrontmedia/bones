<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Container\Container;
use Bayfront\PDO\Db;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MigrateDown extends Command
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

        $this->setName('migrate:down')
            ->setDescription('Rollback database migrations')
            ->addOption('batch', null, InputOption::VALUE_REQUIRED)
            ->addOption('force', null, InputOption::VALUE_NONE);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $batch = (int)$input->getOption('batch');

        if ($batch == 0) {

            // No batch provided. Get last batch number.

            $batch = $this->db->single("SELECT MAX(batch) AS max FROM `migrations`");

            if (!$batch) {

                $output->writeln('<info>No migrations found.</info>');
                return Command::SUCCESS;

            }

        }

        $migrations = $this->db->select("SELECT id, migration, batch FROM `migrations` WHERE batch >= :batch", [
            'batch' => $batch
        ]);

        if (empty($migrations)) {
            $output->writeln('<info>No migrations to run.</info>');
            return Command::SUCCESS;
        }

        $rows = [];

        foreach ($migrations as $v) {

            $rows[] = [
                $v['id'],
                $v['migration'],
                $v['batch']
            ];

        }

        $output->writeln('<info>Preparing to roll back the following migrations:</info>');

        $table = new Table($output);
        $table->setHeaders(['ID', 'Migration', 'Batch'])->setRows($rows);
        $table->render();

        if (!$input->getOption('force')) {

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this action? [y/n]', false);

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            if (!$helper->ask($input, $output, $question)) {

                $output->writeln('<info>Migration aborted!</info>');
                return Command::SUCCESS;
            }

        }

        // Run migrations

        sort($migrations); // Ensure ordered by filename

        foreach ($migrations as $migration) {

            $file_exp = explode('_', $migration['migration'], 2);

            if (isset($file_exp[1])) { // Valid filename format

                if (!file_exists(App::resourcesPath('/database/migrations/' . $migration['migration'] . '.php'))) {

                    $output->writeln('<error>Unable to perform migration: File does not exist (' . $migration['migration'] . ')</error>');
                    continue;

                }

                $output->writeln('Running migration: ' . $migration['migration']);

                // Run migration

                $class = $this->container->make($file_exp[1]);
                $class->down();

                // Remove from migrations table

                $this->db->delete('migrations', [
                    'id' => $migration['id']
                ]);

            }

        }

        $output->writeln('<info>*** NOTE: To completely remove a migration, remove the migration file and run "composer install" ***</info>');
        $output->writeln('<info>Migration complete!</info>');
        return Command::SUCCESS;

    }

}