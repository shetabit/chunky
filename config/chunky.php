<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | This value determines which of the following driver to use.
    | You can switch to a different driver at runtime.
    |
    */
    'inputStream' => [
        'default' => 'filesystem',

        'filesystem' => [
            'inputName' => 'media',
            'uniqueName' => true,
            'type' => 'monolithic', // can be: monolithic, polylithic
        ],
    ],

    'outputStream' => [
        'default' => 'http',

        'http' => [
            'resumable' => true,
            'speed' => 1000, // byte per second (b/s)
            'chunked' => true,
            'type' => 'web', // can be: web, api
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | List of Drivers
    |--------------------------------------------------------------------------
    |
    | This is the array of Classes that maps to Drivers above.
    | You can create your own driver if you like and add the
    | config in the drivers array and the class to use for
    | here with the same name. You will have to implement
    |
    | for input streams: Shetabit\Chunky\Contracts\InputStreamInterface
    | for output streams: Shetabit\Chunky\Contracts\OutputStreamInterface
    |
    | in your driver.
    |
    */
    'drivers' => [
        'inputStream' => [
            'filesystem' => \Shetabit\Chunky\Drivers\InputStream\FileSystem::class,
        ],
        'outputStream' => [
            'http' => \Shetabit\Chunky\Drivers\OutputStream\Http\Http::class,
        ]
    ]
];
