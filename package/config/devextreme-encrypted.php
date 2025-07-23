<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DevExtreme Encrypted Fields Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the DevExtreme encrypted
    | fields integration package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching encrypted field search results to improve
    | performance when dealing with large datasets.
    |
    */
    'cache' => [
        'enabled' => env('DEVEXTREME_CACHE_ENABLED', true),
        'duration' => env('DEVEXTREME_CACHE_DURATION', 5), // minutes
        'store' => env('DEVEXTREME_CACHE_STORE', null), // null = default cache store
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings that affect performance and optimization strategies.
    |
    */
    'performance' => [
        // Memory limit warning threshold (in MB)
        'memory_warning_threshold' => env('DEVEXTREME_MEMORY_WARNING', 128),

        // Maximum records to process in memory before warning
        'max_in_memory_records' => env('DEVEXTREME_MAX_MEMORY_RECORDS', 10000),

        // Enable debug logging for optimization insights
        'debug_logging' => env('DEVEXTREME_DEBUG_LOGGING', false),

        // Auto-optimize query strategy based on field types
        'auto_optimize' => env('DEVEXTREME_AUTO_OPTIMIZE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security-related settings for encrypted fields handling.
    |
    */
    'security' => [
        // Fields that should never be searchable (security)
        'protected_fields' => [
            'password',
            'remember_token',
            'api_token',
        ],

        // Enable audit logging for encrypted field access
        'audit_logging' => env('DEVEXTREME_AUDIT_LOGGING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for DevExtreme pagination.
    |
    */
    'pagination' => [
        'default_page_size' => env('DEVEXTREME_DEFAULT_PAGE_SIZE', 20),
        'max_page_size' => env('DEVEXTREME_MAX_PAGE_SIZE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Type Detection
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic field type detection.
    |
    */
    'field_detection' => [
        // Automatically detect date fields by name patterns
        'auto_detect_dates' => true,
        'date_field_patterns' => [
            '*_at',
            '*_date',
            'date_*',
        ],

        // Automatically detect encrypted fields
        'auto_detect_encrypted' => true,
    ],
];
