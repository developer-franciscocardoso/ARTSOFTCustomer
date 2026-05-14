<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\ServiceProviders\Laravel;

use FranciscoCardoso\ARTSOFTCustomer\Application\Services\InsertCustomerService;
use FranciscoCardoso\ARTSOFTCustomer\Console\Commands\PublishCustomerConfigCommand;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerConnectorInterface;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerProcessorInterface;
use FranciscoCardoso\ARTSOFTCustomer\Infrastructure\Connectors\ArtsoftCustomerConnectorAdapter;
use Illuminate\Support\ServiceProvider;

class ArtsoftCustomerLaravelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../../config/customer.php' => config_path('customer.php'),
        ], 'artsoft-customer-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishCustomerConfigCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/customer.php',
            'customer'
        );

        $this->app->bind(CustomerConnectorInterface::class, static fn() => new ArtsoftCustomerConnectorAdapter());

        $this->app->bind(InsertCustomerService::class, fn($app) => new InsertCustomerService(
            $app->make(CustomerConnectorInterface::class)
        ));

        $this->app->bind(CustomerProcessorInterface::class, fn($app) => $app->make(InsertCustomerService::class));
    }
}
