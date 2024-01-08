<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\PDO\Db;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationList extends Command
{

    protected Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
        parent::__construct();
    }

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('migration:list')
            ->setDescription('List all migrations which have ran')
            ->addOption('sort', null, InputOption::VALUE_REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        try {
            $return = $this->db->select("SELECT id, migration, batch FROM `migrations` ORDER BY batch, migration");
        } catch (Exception) {
            $output->writeln('<info>No migrations found: Valid migrations table does not exist.</info>');
            return Command::SUCCESS;
        }

        $sort = strtolower((string)$input->getOption('sort'));

        if ($sort == 'id') {
            $return = Arr::multisort($return, 'id');
        } else if ($sort == 'migration') {
            $return = Arr::multisort($return, 'migration');
        }

        // Return

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($return));
        } else {

            if (empty($return)) {
                $output->writeln('<info>No migrations found.</info>');
            } else {

                $rows = [];

                foreach ($return as $v) {

                    $rows[] = [
                        $v['id'],
                        $v['migration'],
                        $v['batch']
                    ];

                }

                $table = new Table($output);
                $table->setHeaders(['ID', 'Migration', 'Batch'])->setRows($rows);
                $table->render();

            }

        }

        return Command::SUCCESS;

    }

}