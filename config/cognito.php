<?php

return [
    'credentials' => [
        'key'    => env('AWS_ACCESS_KEY_ID', ''),
        'secret' => env('AWS_SECRET_ACCESS_KEY', ''),
    ],
    'region' => env('AWS_DEFAULT_REGION', ''),
    'version' => '2016-04-18',
    'app_client_id' => env('AWS_COGNITO_CLIENT_ID', ''),
    'app_client_secret' => env('AWS_COGNITO_CLIENT_SECRET', ''),
    'user_pool_id' => env('AWS_COGNITO_USER_POOL_ID', ''),
];
