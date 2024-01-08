<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Services\Filters\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AboutBones extends Command
{

    protected FilterService $filters;

    public function __construct(FilterService $filters)
    {
        $this->filters = $filters;
        parent::__construct();
    }

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('about:bones')
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

        $about = array_merge($this->filters->doFilter('about.bones', []), [
            'Bones version' => App::getBonesVersion(),
            'PHP version' => phpversion(),
            'POST max size' => ini_get('post_max_size'),
            'Debug mode' => App::getConfig('app.debug') ? 'True' : 'False',
            'Environment' => App::getConfig('app.environment'),
            'Timezone' => date_default_timezone_get(),
            'Base path' => App::basePath(),
            'Cached config' => is_file(App::storagePath('/bones/cache/config')) ? 'True' : 'False',
            'Cached commands' => is_file(App::storagePath('/bones/cache/commands.json')) ? 'True' : 'False',
            'Cached events' => is_file(App::storagePath('/bones/cache/events.json')) ? 'True' : 'False',
            'Cached filters' => is_file(App::storagePath('/bones/cache/filters.json')) ? 'True' : 'False'
        ]);

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