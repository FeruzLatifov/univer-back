<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    |
    | The DSN tells Sentry where to send the events. If this value is not
    | provided, Sentry will try to read it from the SENTRY_LARAVEL_DSN
    | environment variable. If that variable also does not exist,
    | SDK will just not send any events.
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Breadcrumbs provide context to errors. They are small pieces of
    | information that can help you understand what happened before an
    | error occurred.
    |
    */

    'breadcrumbs' => [
        // Capture Laravel logs as breadcrumbs
        'logs' => true,

        // Capture SQL queries as breadcrumbs
        'sql_queries' => env('SENTRY_BREADCRUMBS_SQL_QUERIES_ENABLED', true),

        // Capture SQL bindings (may contain sensitive data)
        'sql_bindings' => env('SENTRY_BREADCRUMBS_SQL_BINDINGS_ENABLED', false),

        // Capture queue job information
        'queue_info' => true,

        // Capture command information
        'command_info' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable performance monitoring to track slow database queries,
    | HTTP requests, and other performance issues.
    |
    */

    // Sample rate for performance monitoring (0.0 to 1.0)
    // 1.0 = 100% of transactions are sent to Sentry
    // 0.2 = 20% of transactions are sent to Sentry
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.2),

    // Sample rate for profiling (0.0 to 1.0)
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Set the environment for Sentry. This is typically set to the same
    | value as your APP_ENV environment variable.
    |
    */

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Release
    |--------------------------------------------------------------------------
    |
    | Set the release version for Sentry. This is useful for tracking which
    | version of your application is throwing errors.
    |
    */

    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Server Name
    |--------------------------------------------------------------------------
    |
    | Set the server name for Sentry. This is useful for tracking which
    | server is throwing errors.
    |
    */

    'server_name' => env('SENTRY_SERVER_NAME', gethostname()),

    /*
    |--------------------------------------------------------------------------
    | Send Default PII
    |--------------------------------------------------------------------------
    |
    | If this option is enabled, certain personally identifiable information
    | (PII) is added by active integrations. By default, no such data is sent.
    |
    | WARNING: Enabling this may expose sensitive user data!
    |
    */

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    /*
    |--------------------------------------------------------------------------
    | Context Lines
    |--------------------------------------------------------------------------
    |
    | The number of lines of code context to capture around the line where
    | an error occurred.
    |
    */

    'context_lines' => 5,

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    |
    | Configure Sentry integrations. These provide additional context and
    | functionality to Sentry error reporting.
    |
    */

    'integrations' => [
        // Integration to capture unhandled promise rejections
        Sentry\Integration\IgnoreErrorsIntegration::class => [
            'ignore_exceptions' => [
                // Add exception classes to ignore here
                // Example: Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Before Send Callback
    |--------------------------------------------------------------------------
    |
    | This callback is called before sending an event to Sentry. You can use
    | this to modify the event or return null to prevent sending it.
    |
    */

    'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        // Example: Don't send 404 errors to Sentry
        if ($hint !== null) {
            $exception = $hint->exception;
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return null;
            }
        }

        return $event;
    },

    /*
    |--------------------------------------------------------------------------
    | Before Send Transaction Callback
    |--------------------------------------------------------------------------
    |
    | This callback is called before sending a transaction to Sentry. You can
    | use this to modify the transaction or return null to prevent sending it.
    |
    */

    'before_send_transaction' => null,

    /*
    |--------------------------------------------------------------------------
    | Tracing
    |--------------------------------------------------------------------------
    |
    | Configure tracing options for performance monitoring.
    |
    */

    'tracing' => [
        // Trace database queries
        'sql_queries' => env('SENTRY_TRACING_SQL_QUERIES_ENABLED', true),

        // Trace SQL query origins (file and line number)
        'sql_origin' => env('SENTRY_TRACING_SQL_ORIGIN_ENABLED', true),

        // Trace queue jobs
        'queue_job_transactions' => env('SENTRY_TRACING_QUEUE_JOBS_ENABLED', true),

        // Trace queue info
        'queue_info' => env('SENTRY_TRACING_QUEUE_INFO_ENABLED', true),

        // Trace HTTP client requests
        'http_client_requests' => env('SENTRY_TRACING_HTTP_CLIENT_REQUESTS_ENABLED', true),

        // Trace Redis commands
        'redis_commands' => env('SENTRY_TRACING_REDIS_COMMANDS_ENABLED', false),

        // Trace Livewire components
        'livewire' => env('SENTRY_TRACING_LIVEWIRE_ENABLED', false),

        // Continue trace from incoming request headers
        'continue_after_response' => env('SENTRY_TRACING_CONTINUE_AFTER_RESPONSE', false),

        // Maximum number of spans in a transaction
        'max_spans' => env('SENTRY_TRACING_MAX_SPANS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions
    |--------------------------------------------------------------------------
    |
    | List of exception classes that should not be reported to Sentry.
    |
    */

    'ignore_exceptions' => [
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored HTTP Status Codes
    |--------------------------------------------------------------------------
    |
    | List of HTTP status codes that should not trigger error reporting.
    |
    */

    'ignore_http_codes' => [
        404, // Not Found
        // 401, // Unauthorized
        // 403, // Forbidden
    ],

];
