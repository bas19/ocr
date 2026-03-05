<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Vision Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Cloud Vision API. Requires a valid
    | Google Cloud Platform service account key file.
    |
    | Set GOOGLE_APPLICATION_CREDENTIALS in .env to the path of your
    | service account JSON key file.
    |
    */

    'google_vision' => [
        'enabled' => env('GOOGLE_VISION_ENABLED', true),
    ],

];
