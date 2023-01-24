<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Utilities\App;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployPurge extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('deploy:purge')
            ->setDescription('Purge deployment backups')
            ->addOption('days', null, InputOption::VALUE_REQUIRED)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        if (!App::getConfig('app.deploy.backup_path')
            || App::getConfig('app.deploy.backup_path') == ''
            || App::getConfig('app.deploy.backup_path') == '/') {

            $output->writeln('<error>Unable to purge deployment backups: No backup path specified in app config file</error>');
            $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/blob/master/docs/usage/config.md#deploy</info>');

            return Command::FAILURE;

        }

        $output->writeln('<info>Beginning purge of deployment backups...</info>');

        $backup_dirs = glob(rtrim(App::getConfig('app.deploy.backup_path'), '/') . '/*', GLOB_ONLYDIR);

        if (empty($backup_dirs)) {
            $output->writeln('No existing backups to purge.');
            return Command::SUCCESS;
        }

        $backups = [];

        foreach ($backup_dirs as $dir) {

            $backups[] = [
                'path' => $dir,
                'modified' => filemtime($dir)
            ];

        }

        $backups = Arr::multisort($backups, 'modified', true); // Sort by newest first

        $days_removed = 0;
        $limit_removed = 0;

        if ($input->getOption('days') != null) {

            $days = (int)$input->getOption('days');

            $output->writeln('Purging backups older than ' . $days . ' days...');

            foreach ($backups as $backup) {

                if ($backup['modified'] < time() - ($days * 24 * 60 * 60)) {

                    shell_exec('rm -rf ' . $backup['path']);
                    $days_removed++;

                }

            }

            $output->writeln('Completed purging ' . $days_removed . ' backups older than ' . $days . ' days...');

        }

        if ($input->getOption('limit') !== null) {

            $limit = (int)$input->getOption('limit');

            $output->writeln('Purging oldest backups over limit of ' . $limit . '...');

            if ($limit <= 0) { // Remove all
                $over_limit = $backups;
            } else {
                $over_limit = array_slice($backups, $limit); // Slice array at the count
            }

            foreach ($over_limit as $over) {

                shell_exec('rm -rf ' . $over['path']);
                $limit_removed++;

            }

            $output->writeln('Completed purging ' . $limit_removed . ' backups over limit of ' . $limit . '...');

        }

        $removed = $days_removed + $limit_removed;

        $output->writeln('<info>Deployment backups purge complete! (Removed ' . $removed . ' backups)</info>');

        return Command::SUCCESS;

    }


}