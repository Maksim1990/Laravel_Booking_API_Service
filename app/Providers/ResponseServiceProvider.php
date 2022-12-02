<?php

namespace App\Providers;

use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;

class ResponseServiceProvider extends ServiceProvider
{
    public const DEFAULT_RESPONSE_STRUCTURE = ['status' => 'ok'];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(ResponseFactory $factory)
    {
        $factory->macro('success', function ($message = null, $data = null, int $code = 200) use ($factory) {
            $format = ResponseServiceProvider::DEFAULT_RESPONSE_STRUCTURE;
            if ($message) {
                $format['message'] = $message;
            }

            if ($data) {
                $format['data'] = $data;
            }

            return $factory->make(
                content: $format,
                status: $code
            );
        });

        $factory->macro('error', function (string $message = '', $errors = [], int $code = 500) use ($factory) {
            $format = [
                'status' => 'error',
                'code' => $code,
                'message' => $message,
                'errors' => $errors,
            ];

            return $factory->make(
                content: $format,
                status: $code
            );
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
