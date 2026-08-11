<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Node & NPM Binaries
    |--------------------------------------------------------------------------
    |
    | Path to Node and NPM binaries used by Browsershot to execute Puppeteer.
    |
    */
    'node_binary' => env('BROWSERSHOT_NODE_BINARY', '/usr/bin/node'),
    'npm_binary'  => env('BROWSERSHOT_NPM_BINARY', '/usr/bin/npm'),

    /*
    |--------------------------------------------------------------------------
    | Chromium / Chrome Binary Path
    |--------------------------------------------------------------------------
    |
    | Path to the Chromium or Google Chrome binary installed on the system.
    |
    */
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', '/usr/bin/chromium'),

    /*
    |--------------------------------------------------------------------------
    | Default Browser Arguments
    |--------------------------------------------------------------------------
    |
    | Command line flags passed to Chromium when launching headless browser.
    | In containerized and Linux environments, --no-sandbox is essential.
    |
    */
    'chromium_arguments' => [
        'no-sandbox',
        'disable-setuid-sandbox',
        'disable-dev-shm-usage',
        'disable-gpu',
    ],

    /*
    |--------------------------------------------------------------------------
    | Node Modules Path
    |--------------------------------------------------------------------------
    |
    | Optional custom path where puppeteer is installed.
    |
    */
    'node_modules_path' => env('BROWSERSHOT_NODE_MODULES_PATH', base_path('node_modules')),

    /*
    |--------------------------------------------------------------------------
    | Execution Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for PDF generation.
    |
    */
    'timeout' => env('BROWSERSHOT_TIMEOUT', 120),
];
