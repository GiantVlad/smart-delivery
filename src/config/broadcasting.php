<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "reverb", "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over WebSockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT', 443),
                'scheme' => env('REVERB_SCHEME', 'https'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

        'centrifugo' => [
            'driver' => 'centrifugo',
            'secret'  => env('CENTRIFUGO_SECRET'),
            'apikey'  => env('CENTRIFUGO_APIKEY'),
            'api_path' => env('CENTRIFUGO_API_PATH', '/api'), // Centrifugo api endpoint (default '/api')
            'url'     => env('CENTRIFUGO_URL', 'http://centrefugo:8010'), // centrifugo api url
            'verify'  => env('CENTRIFUGO_VERIFY', false), // Verify host ssl if centrifugo uses this
            'ssl_key' => env('CENTRIFUGO_SSL_KEY', null), // Self-Signed SSl Key for Host (require verify=true),
            'show_node_info' => env('CENTRIFUGO_SHOW_NODE_INFO', false), // Show node info in response with auth token
            'timeout' => env('CENTRIFUGO_TIMEOUT', 3), // Float describing the total timeout of the request to centrifugo api in seconds. Use 0 to wait indefinitely (the default is 3)
            'tries' => env('CENTRIFUGO_TRIES', 1), //Number of times to repeat the request, in case of failure (the default is 1)
            'token_expire_time' => env('CENTRIFUGO_TOKEN_EXPIRE', 120), //Default token expire time. Used in channel subscriptions /broadcasting/auth
        ],

    ],

];
