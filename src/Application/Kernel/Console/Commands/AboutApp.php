<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Utilities\App;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AboutApp extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('about:app')
            ->setDescription('Information about this Bones application')
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

        $about = [
            'Bones version' => App::getBonesVersion(),
            'PHP version' => phpversion(),
            'POST max size' => ini_get('post_max_size'),
            'Debug mode' => App::getConfig('app.debug') ? 'True' : 'False',
            'Environment' => App::getConfig('app.environment'),
            'Timezone' => date_default_timezone_get(),
            'Autoload events' => App::getConfig('app.events.autoload') ? 'True' : 'False',
            'Autoload filters' => App::getConfig('app.filters.autoload') ? 'True' : 'False',
            'Autoload commands' => App::getConfig('app.commands.autoload') ? 'True' : 'False',
            'Base path' => App::basePath()

        ];

        if (App::getConfig('app.backup_path')) {
            $about['Deploy backup path'] = rtrim(App::getConfig('app.deploy.backup_path'), '/') . '/';
        }

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($about));
        } else {

            $rows = [];

            foreach ($about as $k => $v) {

                $rows[] = [
                    $k,
                    $v
                ];

            }

            $table = new Table($output);
            $table->setHeaders(['Title', 'Value'])->setRows($rows);
            $table->render();

        }

        return Command::SUCCESS;
    }


}