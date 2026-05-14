<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Console\Commands;

use FranciscoCardoso\ARTSOFTCustomer\ServiceProviders\ArtsoftCustomerServiceProvider;
use Illuminate\Console\Command;

class PublishCustomerConfigCommand extends Command
{
    protected $signature = 'artsoft-customer:publish
                            {--force : Overwrite the config file if it already exists}';

    protected $description = 'Publish the ARTSOFT customer config file to config/customer.php';

    public function handle(): int
    {
        $targetPath = config_path('customer.php');

        $provider = new ArtsoftCustomerServiceProvider();

        try {
            $provider->publishConfig($targetPath, (bool) $this->option('force'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Config file published to ' . $targetPath);

        return self::SUCCESS;
    }
}
