<?php

namespace Bayfront\Bones\Console\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class About extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('about')
            ->setDescription('Retrieve information about this Bones application')
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
            'Bones version' => BONES_VERSION,
            'PHP version' => phpversion(),
            'POST max size' => ini_get('post_max_size'),
            'Debug mode' => get_env('APP_DEBUG_MODE') ? 'True' : 'False',
            'Environment' => get_env('APP_ENVIRONMENT'),
            'Timezone' => date_default_timezone_get(),
            'Cache actions' => get_config('app.actions.cache') ? 'True' : 'False',
            'Autoload actions' => get_config('app.actions.autoload') ? 'True' : 'False',
            'Cache filters' => get_config('app.filters.cache') ? 'True' : 'False',
            'Autoload filters' => get_config('app.filters.autoload') ? 'True' : 'False',
            'Base path' => base_path()

        ];

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