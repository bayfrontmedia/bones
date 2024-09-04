<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Services\Filters\FilterService;
use Bayfront\Bones\Interfaces\MigrationInterface;
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
    protected FilterService $filters;
    protected Db $db;

    public function __construct(Container $container, FilterService $filters, Db $db)
    {
        $this->container = $container;
        $this->filters = $filters;
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

        $ran_migrations = $this->db->select("SELECT id, name, batch FROM `migrations` WHERE batch >= :batch ORDER BY id DESC", [
            'batch' => $batch
        ]);

        if (empty($ran_migrations)) {
            $output->writeln('<info>No migrations to run.</info>');
            return Command::SUCCESS;
        }

        $rows = [];

        foreach ($ran_migrations as $v) {

            $rows[] = [
                $v['id'],
                $v['name'],
                $v['batch']
            ];

        }

        $output->writeln('<info>Preparing to roll back the following migrations:</info>');

        $table = new Table($output);
        $table->setHeaders(['ID', 'Name', 'Batch'])->setRows($rows);
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

        $known_migrations = $this->filters->doFilter('bones.migrations', []);

        if (empty($known_migrations)) {
            $output->writeln('<info>No known migrations found.</info>');
            return Command::SUCCESS;
        }

        /**
         * Array key = name of migration
         * @var MigrationInterface $known_migration
         */
        foreach ($known_migrations as $k => $known_migration) {

            if (!$known_migration instanceof MigrationInterface) {
                $output->writeln('<error>All migrations must implement MigrationInterface</error>');
                return Command::FAILURE;
            }

            $known_migrations[$known_migration->getName()] = $known_migration;
            unset($known_migrations[$k]);

        }

        foreach ($ran_migrations as $ran) {

            if (!isset($known_migrations[$ran['name']])) {
                $output->writeln('<error>Unable to perform migration: Migration does not exist (' . $ran['name'] . ')</error>');
                continue;
            }

            $output->writeln('Running migration: ' . $ran['name']);

            // Run migration

            $known_migrations[$ran['name']]->down();

            // Remove from migrations table

            $this->db->delete('migrations', [
                'id' => $ran['id']
            ]);

        }

        $output->writeln('<info>*** NOTE: To prevent a migration from running in the future, it must be removed from the bones.migrations filter ***</info>');
        $output->writeln('<info>Migration complete!</info>');
        return Command::SUCCESS;

    }

}