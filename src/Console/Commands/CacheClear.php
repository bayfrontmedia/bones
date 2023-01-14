<?php

namespace Bayfront\Bones\Console\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClear extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('cache:clear')
            ->setDescription('Clear cache')
            ->addArgument('type', InputArgument::IS_ARRAY, 'Type(s) of cache to clear');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        /**
         * Key = action
         * Value = filename
         */

        $filenames = [
            'actions' => 'actions.ser',
            'filters' => 'filters.ser'
        ];

        $types = $input->getArgument('type');

        foreach ($types as $type) {

            if (!isset($filenames[$type]) && strtolower($type) != 'all') {

                $output->writeln('<error>Unable to clear cache: Unknown type (' . $type . ')</error>');

                return Command::FAILURE;

            }

            if (strtolower($type) == 'all') {

                $files = glob(storage_path('/app/cache/*'));

                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

            } else {

                $deleted = unlink(storage_path('/app/cache/' . $filenames[$type]));

                if (!$deleted) {

                    $output->writeln('<error>Unable to clear cache: Unable to remove cache (' . $type . ')</error>');

                    return Command::FAILURE;

                }

            }

            $output->writeln('<info>Cache successfully cleared for type: ' . $type . '</info>');

        }

        return Command::SUCCESS;

    }

}