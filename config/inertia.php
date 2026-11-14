<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    */
    'ssr' => [
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', false),
        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
        'ensure_bundle_exists' => (bool) env('INERTIA_SSR_ENSURE_BUNDLE_EXISTS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Đăng ký cả 'js/pages' (chữ thường) và 'js/Pages' (chữ hoa) để tương thích
    | 100% trên cả Windows và Linux (Ubuntu GitHub Actions CI/CD).
    |
    */
    'ensure_pages_exist' => false,

    'page_paths' => [
        resource_path('js/pages'),
        resource_path('js/Pages'),
    ],

    'page_extensions' => [
        'js',
        'jsx',
        'svelte',
        'ts',
        'tsx',
        'vue',
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    */
    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [
            resource_path('js/pages'),
            resource_path('js/Pages'),
        ],
        'page_extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],

];
