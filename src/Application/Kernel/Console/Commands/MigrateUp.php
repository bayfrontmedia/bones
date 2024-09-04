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

class MigrateUp extends Command
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

        $this->setName('migrate:up')
            ->setDescription('Run all pending database migrations')
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

        $migrations = $this->filters->doFilter('bones.migrations', []);

        if (empty($migrations)) {
            $output->writeln('<info>No migrations found.</info>');
            return Command::SUCCESS;
        }

        // Migration files exist. Ensure database table exists.

        $this->db->query("CREATE TABLE IF NOT EXISTS `migrations` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` varchar(255) NOT NULL,
            `batch` int NOT NULL,
            UNIQUE (`name`)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Get all migrations which have not yet run

        /**
         * @var MigrationInterface $migration
         */
        foreach ($migrations as $k => $migration) {

            if (!$migration instanceof MigrationInterface) {
                $output->writeln('<error>All migrations must implement MigrationInterface</error>');
                return Command::FAILURE;
            }

            if ($this->db->exists('migrations', [
                'name' => $migration->getName()
            ])) {
                unset($migrations[$k]);
            }

        }

        // $migrations contain only migrations which have not yet run

        if (empty($migrations)) {
            $output->writeln('<info>No migrations to run.</info>');
            return Command::SUCCESS;
        }

        // Get next batch number

        $batch_num = $this->db->single("SELECT MAX(batch) AS max FROM `migrations`");

        if (!$batch_num) {
            $batch_num = 1;
        } else {
            $batch_num = $batch_num + 1;
        }

        $rows = [];

        foreach ($migrations as $migration) {

            $rows[] = [
                $migration->getName()
            ];

        }

        $output->writeln('<info>Preparing to run the following migrations:</info>');

        $table = new Table($output);
        $table->setHeaders(['Name'])->setRows($rows);
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

        foreach ($migrations as $migration) {

            $output->writeln('Running migration: ' . $migration->getName());

            // Run migration

            $migration->up();

            // Add to migrations table

            $this->db->insert('migrations', [
                'name' => $migration->getName(),
                'batch' => $batch_num
            ]);

        }

        $output->writeln('<info>Migration complete!</info>');
        return Command::SUCCESS;

    }

}