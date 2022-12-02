<?php
namespace App\Providers;

use App\Services\AWS\Cognito\CognitoClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

class CognitoAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(CognitoClient::class, function (Application $app) {
            $config = [
                'credentials' => config('cognito.credentials'),
                'region'      => config('cognito.region'),
                'version'     => config('cognito.version')
            ];

            return new CognitoClient(
                new CognitoIdentityProviderClient($config),
                config('cognito.app_client_id'),
                config('cognito.app_client_secret'),
                config('cognito.user_pool_id')
            );
        });
    }
}
