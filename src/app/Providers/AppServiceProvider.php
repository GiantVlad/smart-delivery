<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Rules\OrdersCanBeAddedRule;
use App\Rules\FirstLastRouteRule;
use Temporal\Client\GRPC\ServiceClient;
use Temporal\Client\WorkflowClient;
use Temporal\Client\WorkflowClientInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(
            WorkflowClientInterface::class,
            function () {
                if (! extension_loaded('grpc')) {
                    throw new \RuntimeException('The gRPC extension is required to use the Temporal Client.');
                }

                return WorkflowClient::create(
                    ServiceClient::create('temporal:7233')
                );
            }
        );

        $this->app->bind(OrdersCanBeAddedRule::class, function ($app) {
            return new OrdersCanBeAddedRule(
                $app->make(WorkflowClientInterface::class)
            );
        });

        $this->app->bind(FirstLastRouteRule::class, function ($app) {
            return new FirstLastRouteRule(
                $app->make(WorkflowClientInterface::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
