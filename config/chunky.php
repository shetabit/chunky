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
    'storage' => 'monolithicFile',

    /*
    |--------------------------------------------------------------------------
    | List of Drivers
    |--------------------------------------------------------------------------
    |
    | This is the array of Classes that maps to Drivers above.
    | You can create your own driver if you like and add the
    | config in the drivers array and the class to use for
    | here with the same name. You will have to implement
    | Shetabit\Chunky\Contracts\UserAgentParser in your driver.
    |
    */
    'drivers' => [
        'storage' => [
            'monolithicFile' => \Shetabit\Chunky\Drivers\MonolithicFile::class,
            'polylithicFile' => \Shetabit\Chunky\Drivers\PolylithicFile::class,    
        ],
    ]
];
