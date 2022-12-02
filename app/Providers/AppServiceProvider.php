<?php

namespace App\Providers;

use App\Services\ActivationService;
use App\Services\ActivationServiceInterface;
use App\Services\Auth\AuthManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //AUTHORIZATION TOKEN MANAGER INITIALIZATION
        $this->app->bind(
            AuthManager::class, function ($app) {
            $arrParams = [];
            $arrParams['env'] = $app->config['app.env'] ?? 'local';
            return new AuthManager($arrParams);
        }
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(ActivationServiceInterface::class, ActivationService::class);
    }
}
