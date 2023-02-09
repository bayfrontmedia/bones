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

        $dir = App::resourcesPath('/database/migrations');

        if (is_dir($dir)) {

            $migrations = glob($dir . '/*.php');

            if (empty($migrations)) {
                $output->writeln('<info>No migrations found.</info>');
                return Command::SUCCESS;
            }

            foreach ($migrations as $k => $v) {
                $migrations[$k] = basename($v, '.php');
            }

            // Migration files exist. Ensure database table exists.

            $this->db->query("CREATE TABLE IF NOT EXISTS `migrations` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `migration` varchar(255) NOT NULL,
            `batch` int NOT NULL
            )");

            // Get all migrations which have not yet run

            foreach ($migrations as $k => $migration) {

                if ($this->db->exists('migrations', [
                    'migration' => $migration
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
                    $migration
                ];

            }

            $output->writeln('<info>Preparing to run the following migrations:</info>');

            $table = new Table($output);
            $table->setHeaders(['Migration'])->setRows($rows);
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

                $file_exp = explode('_', $migration, 2);

                if (isset($file_exp[1])) { // Valid filename format

                    $output->writeln('Running migration: ' . $migration);

                    // Run migration

                    $class = $this->container->make($file_exp[1]);
                    $class->up();

                    // Add to migrations table

                    $this->db->insert('migrations', [
                        'migration' => $migration,
                        'batch' => $batch_num
                    ]);

                }

            }

            $output->writeln('<info>Migration complete!</info>');
            return Command::SUCCESS;

        }

        $output->writeln('<info>Migrations directory does not exist. No migrations found.</info>');
        return Command::SUCCESS;

    }

}