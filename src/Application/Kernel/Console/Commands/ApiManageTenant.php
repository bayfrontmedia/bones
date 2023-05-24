<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Services\Api\Models\Relationships\TenantRolePermissionsModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantMetaModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantPermissionsModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantRolesModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantsModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ApiManageTenant extends Command
{

    protected TenantsModel $tenantsModel;
    protected TenantPermissionsModel $tenantPermissionsModel;
    protected TenantRolesModel $tenantRolesModel;
    protected TenantRolePermissionsModel $tenantRolePermissionsModel;
    protected TenantMetaModel $tenantMetaModel;

    public function __construct(TenantsModel $tenantsModel, TenantPermissionsModel $tenantPermissionsModel, TenantRolesModel $tenantRolesModel, TenantRolePermissionsModel $tenantRolePermissionsModel, TenantMetaModel $tenantMetaModel)
    {
        $this->tenantsModel = $tenantsModel;
        $this->tenantPermissionsModel = $tenantPermissionsModel;
        $this->tenantRolesModel = $tenantRolesModel;
        $this->tenantRolePermissionsModel = $tenantRolePermissionsModel;
        $this->tenantMetaModel = $tenantMetaModel;

        parent::__construct();
    }

    /*
     * Options
     */
    private const OPTION_CREATE = 'Create a new tenant';
    private const OPTION_DISABLE = 'Disable tenant';
    private const OPTION_ENABLE = 'Enable tenant';
    private const OPTION_CHANGE_PLAN = 'Change tenant plan';
    private const OPTION_REMOVE = 'Remove tenant';

    /**
     * @return void
     */
    protected function configure(): void
    {

        $this->setName('api:manage:tenant')
            ->setDescription('Manage API tenant');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion('Are you sure you want to manage an API tenant? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Choose an option: ', [
                self::OPTION_CREATE,
                self::OPTION_DISABLE,
                self::OPTION_ENABLE,
                self::OPTION_CHANGE_PLAN,
                self::OPTION_REMOVE
        ],
            0
        );

        $question->setErrorMessage('Option %s is invalid.');
        $option = $helper->ask($input, $output, $question);

        if ($option == self::OPTION_CREATE) {
            return $this->createTenant($input, $output);
        } else if ($option == self::OPTION_DISABLE) {
            return $this->disableTenant($input, $output);
        } else if ($option == self::OPTION_ENABLE) {
            return $this->enableTenant($input, $output);
        } else if ($option == self::OPTION_CHANGE_PLAN) {
            return $this->changeTenantPlan($input, $output);
        } else if ($option == self::OPTION_REMOVE) {
            return $this->removeTenant($input, $output);
        } else {
            $output->writeln('<error>Unknown option</error>');
            return Command::FAILURE;
        }

    }

    private function createTenant(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln('Create tenant');

        return Command::SUCCESS;

    }

    private function disableTenant(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln('Disable tenant');

        return Command::SUCCESS;

    }

    private function enableTenant(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln('Enable tenant');

        return Command::SUCCESS;

    }

    private function changeTenantPlan(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln('Change tenant plan');

        return Command::SUCCESS;

    }

    private function removeTenant(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln('Remove tenant');

        return Command::SUCCESS;

    }

}