<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Utilities\App;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployApp extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('deploy:app')
            ->setDescription('Deploy application')
            ->addArgument('target', InputArgument::REQUIRED, 'ie: origin/master (branch), v1.0.0 (tag), or commit hash')
            ->addOption('backup', null, InputOption::VALUE_NONE);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln('<info>Beginning deployment...</info>');

        if ($input->getOption('backup')) {

            if (!App::getConfig('app.deploy.backup_path')
                || App::getConfig('app.deploy.backup_path') == ''
                || App::getConfig('app.deploy.backup_path') == '/') {

                $output->writeln('<error>Unable to create backup: No backup path specified in app config file</error>');
                $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/blob/master/docs/usage/config.md#deploy</info>');
                
            } else {

                $output->writeln('Backing up...');

                $git_path = App::basePath('/.git');

                if (file_exists($git_path)) {

                    $git_head = trim(substr(file_get_contents($git_path . '/HEAD'), 4));
                    $git_hash = substr(trim(file_get_contents($git_path . '/' . $git_head)), 0, 7);

                    $backup_name = date('Y-m-d_H-i-s') . '_' . $git_hash;
                    $backup_location = rtrim(App::getConfig('app.deploy.backup_path'), '/') . '/' . $backup_name;

                    if (!is_dir($backup_location)) {
                        mkdir($backup_location, 0755, true);
                    }

                    shell_exec('rsync -ar ' . App::basePath('/') . ' ' . $backup_location);

                    $output->writeln('Backed up to: ' . $backup_location);

                }

            }

        }

        $target = $input->getArgument('target');

        $output->writeln('Pulling from Git target: ' . $target . '...');

        //shell_exec('git pull ' . $target);

        //shell_exec('git fetch --all');
        //shell_exec('git checkout --force ' . $target);

        shell_exec('git fetch --all');
        shell_exec('git reset --hard HEAD');
        shell_exec('git merge ' . $target);

        //shell_exec('git fetch --all');
        //shell_exec('git reset --hard ' . $target);
        //shell_exec('git clean -f -d');
        //shell_exec('git pull');

        $output->writeln('Updating dependencies...');

        //shell_exec('composer update');

        $output->writeln('<info>Deployment complete!</info>');

        return Command::SUCCESS;
    }


}