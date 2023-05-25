<?php /** @noinspection DuplicatedCode */

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Models\Relationships\TenantRolePermissionsModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantMetaModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantPermissionsModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantRolesModel;
use Bayfront\Bones\Services\Api\Models\Resources\TenantsModel;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

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
    private const OPTION_DELETE = 'Delete tenant';

    /**
     * @return void
     */
    protected function configure(): void
    {

        $this->setName('api:manage:tenant')
            ->setDescription('Manage API tenant');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */

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
                self::OPTION_DELETE
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
        } else if ($option == self::OPTION_DELETE) {
            return $this->deleteTenant($input, $output);
        } else {
            $output->writeln('<error>Unknown option</error>');
            return Command::FAILURE;
        }

    }

    /**
     * Create new tenant.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    private function createTenant(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new Question('Enter owner ID: ');
        $owner = $helper->ask($input, $output, $question);

        $question = new Question('Enter tenant name: ');
        $name = $helper->ask($input, $output, $question);

        $meta = [];

        foreach (App::getConfig('api.required_meta.tenants') as $k => $v) {

            $question = new Question('Enter required meta (' . $k . '): ');
            $meta[$k] = $helper->ask($input, $output, $question);

        }

        $enabled = false;

        $question = new ConfirmationQuestion('Enable tenant? [y/n] ', false);

        if ($helper->ask($input, $output, $question)) {
            $enabled = true;
        }

        $question = new ChoiceQuestion(
            'Choose a plan: ', array_keys(App::getConfig('api-plans')),
            0
        );

        $question->setErrorMessage('Plan %s is invalid.');
        $plan = $helper->ask($input, $output, $question);

        $output->writeLn('<info>Ready to create tenant:</info>');
        $output->writeLn('<question>Owner ID:</question> ' . $owner);
        $output->writeLn('<question>Name:</question> ' . $name);

        foreach ($meta as $k => $v) {
            $output->writeLn('<question>Meta (' . $k . '):</question> ' . $v);
        }

        if ($enabled) {
            $output->writeLn('<question>Enabled:</question> True');
        } else {
            $output->writeLn('<question>Enabled:</question> False');
        }

        $output->writeLn('<question>Plan:</question> ' . $plan);

        $question = new ConfirmationQuestion('Are you sure you want to proceed? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        // Create tenant

        $attrs = [
            'owner' => $owner,
            'name' => $name,
            'enabled' => $enabled
        ];

        if (!empty($meta)) {
            $attrs['meta'] = $meta;
        }

        try {

            $tenant_id = $this->tenantsModel->create($attrs);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $set_plan = $this->setTenantPlan($output, $tenant_id, $plan);

        if ($set_plan === Command::FAILURE) {
            return Command::FAILURE;
        }

        /*
         * Create permissions and roles based on plan
         */

        /*

        $permission_ids = [];

        $plan = App::getConfig('api-plans.' . $plan, []);

        foreach (Arr::get($plan, 'permissions', []) as $permission => $description) {

            try {

                $permission_ids[$permission] = $this->tenantPermissionsModel->create($tenant_id, [
                    'name' => $permission,
                    'description' => $description
                ]);

            } catch (Exception $e) {

                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;

            }

        }

        $role_ids = [];

        foreach (Arr::get($plan, 'roles', []) as $role) {

            try {

                $id = $this->tenantRolesModel->create($tenant_id, [
                    'name' => $role['name'],
                    'description' => $role['description']
                ]);

                $role_ids[$role['name']] = $id;

                $this->tenantRolePermissionsModel->add($tenant_id, $id, Arr::only($permission_ids, array_keys($role['permissions'])));

            } catch (Exception $e) {

                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;

            }

        }

        /*
         * Save plan-defined tenant meta
         * /

        foreach (Arr::get($plan, 'tenant_meta', []) as $meta_id => $meta_value) {

            try {

                $this->tenantMetaModel->create($tenant_id, [
                    'id' => $meta_id,
                    'metaValue' => $meta_value
                ], true);

            } catch (Exception $e) {

                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;

            }

        }

        /*
         * Save plan permission and role ID's
         * /

        try {

            $this->tenantMetaModel->create($tenant_id, [
                'id' => '00-plan-permissions',
                'metaValue' => json_encode($permission_ids)
            ], true);

            $this->tenantMetaModel->create($tenant_id, [
                'id' => '00-plan-roles',
                'metaValue' => json_encode($role_ids)
            ], true);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        */

        $output->writeLn('<info>Tenant successfully created!</info>');
        $output->writeLn('<info>ID: ' . $tenant_id . '</info>');

        return Command::SUCCESS;

    }

    /**
     * Disable tenant.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    private function disableTenant(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new Question('Enter tenant ID: ');
        $tenant_id = $helper->ask($input, $output, $question);

        try {

            $tenant = $this->tenantsModel->get($tenant_id, [
                'name'
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $question = new ConfirmationQuestion('Are you sure you want to disable this tenant (' . $tenant['name'] . ')? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        try {

            $this->tenantsModel->update($tenant_id, [
                'enabled' => false
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $output->writeLn('<info>Tenant (' . $tenant['name'] . ') successfully disabled</info>');
        return Command::SUCCESS;

    }

    /**
     * Enable tenant.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    private function enableTenant(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new Question('Enter tenant ID: ');
        $tenant_id = $helper->ask($input, $output, $question);

        try {

            $tenant = $this->tenantsModel->get($tenant_id, [
                'name'
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $question = new ConfirmationQuestion('Are you sure you want to enable this tenant (' . $tenant['name'] . ')? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        try {

            $this->tenantsModel->update($tenant_id, [
                'enabled' => true
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $output->writeLn('<info>Tenant (' . $tenant['name'] . ') successfully enabled</info>');
        return Command::SUCCESS;

    }

    private function changeTenantPlan(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new Question('Enter tenant ID: ');
        $tenant_id = $helper->ask($input, $output, $question);

        try {

            $tenant = $this->tenantsModel->get($tenant_id, [
                'name'
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $question = new ConfirmationQuestion('Are you sure you want to change the plan of this tenant (' . $tenant['name'] . ')? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $question = new ChoiceQuestion(
            'Choose a plan: ', array_keys(App::getConfig('api-plans')),
            0
        );

        $question->setErrorMessage('Plan %s is invalid.');
        $plan = $helper->ask($input, $output, $question);

        $output->writeLn('<info>Ready to change tenant plan:</info>');
        $output->writeLn('<question>Tenant ID:</question> ' . $tenant_id);
        $output->writeLn('<question>Tenant name:</question> ' . $tenant['name']);

        // Current plan

        // New plan

    }

    /**
     * Delete tenant.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    private function deleteTenant(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new Question('Enter tenant ID: ');
        $tenant_id = $helper->ask($input, $output, $question);

        try {

            $tenant = $this->tenantsModel->get($tenant_id, [
                'name'
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $question = new ConfirmationQuestion('Are you sure you want to delete this tenant (' . $tenant['name'] . ')? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        try {

            $this->tenantsModel->delete($tenant_id);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $output->writeLn('<info>Tenant (' . $tenant['name'] . ') successfully deleted</info>');
        return Command::SUCCESS;

    }

    /**
     * Set/update tenant plan.
     *
     * @param OutputInterface $output
     * @param string $tenant_id
     * @param string $plan
     * @return int
     */
    private function setTenantPlan(OutputInterface $output, string $tenant_id, string $plan): int
    {

        // TODO: Get current permissions/roles and update as necessary

        /*
         * Create permissions and roles based on plan
         */

        $permission_ids = [];

        $plan = App::getConfig('api-plans.' . $plan, []);

        foreach (Arr::get($plan, 'permissions', []) as $permission => $description) {

            try {

                $permission_ids[$permission] = $this->tenantPermissionsModel->create($tenant_id, [
                    'name' => $permission,
                    'description' => $description
                ]);

            } catch (Exception $e) {

                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;

            }

        }

        $role_ids = [];

        foreach (Arr::get($plan, 'roles', []) as $role) {

            try {

                $id = $this->tenantRolesModel->create($tenant_id, [
                    'name' => $role['name'],
                    'description' => $role['description']
                ]);

                $role_ids[$role['name']] = $id;

                $this->tenantRolePermissionsModel->add($tenant_id, $id, Arr::only($permission_ids, array_keys($role['permissions'])));

            } catch (Exception $e) {

                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;

            }

        }

        /*
         * Save plan-defined tenant meta
         */

        foreach (Arr::get($plan, 'tenant_meta', []) as $meta_id => $meta_value) {

            try {

                $this->tenantMetaModel->create($tenant_id, [
                    'id' => $meta_id,
                    'metaValue' => $meta_value
                ], true, true);

            } catch (Exception $e) {

                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;

            }

        }

        /*
         * Save plan permission and role ID's
         */

        try {

            $this->tenantMetaModel->create($tenant_id, [
                'id' => '00-plan-permissions',
                'metaValue' => json_encode($permission_ids)
            ], true, true);

            $this->tenantMetaModel->create($tenant_id, [
                'id' => '00-plan-roles',
                'metaValue' => json_encode($role_ids)
            ], true, true);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        return Command::SUCCESS;

    }

}