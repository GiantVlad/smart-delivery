<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Temporal\Client\GRPC\ServiceClient;
use Temporal\Client\WorkflowClient;
use Temporal\Client\WorkflowClientInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register ()
    {
        $this->app->singleton(
            WorkflowClientInterface::class,
            fn () => WorkflowClient::create(
                ServiceClient::create('temporal:7233')
            )
        );
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
