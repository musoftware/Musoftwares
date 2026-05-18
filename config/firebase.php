<?php

declare(strict_types=1);

return [
    /*
     * ------------------------------------------------------------------------
     * Default Firebase project
     * ------------------------------------------------------------------------
     */

    'default' => env('FIREBASE_PROJECT', 'app'),

    /*
     * ------------------------------------------------------------------------
     * Firebase project configurations
     * ------------------------------------------------------------------------
     */

    'projects' => [
        'app' => [

            /*
             * ------------------------------------------------------------------------
             * Credentials / Service Account
             * ------------------------------------------------------------------------
             *
             * In order to access a Firebase project and its related services using a
             * server SDK, requests must be authenticated. For server-to-server
             * communication this is done with a Service Account.
             *
             * If you don't already have generated a Service Account, you can do so by
             * following the instructions from the official documentation pages at
             *
             * https://firebase.google.com/docs/admin/setup#initialize_the_sdk
             *
             * Once you have downloaded the Service Account JSON file, you can use it
             * to configure the package.
             *
             * If you don't provide credentials, the Firebase Admin SDK will try to
             * auto-discover them
             *
             * - by checking the environment variable FIREBASE_CREDENTIALS
             * - by checking the environment variable GOOGLE_APPLICATION_CREDENTIALS
             * - by trying to find Google's well known file
             * - by checking if the application is running on GCE/GCP
             *
             * If no credentials file can be found, an exception will be thrown the
             * first time you try to access a component of the Firebase Admin SDK.
             *
             */

            'credentials' => [
                'type' => 'service_account',
                'project_id' => 'musoftware-c0696',
                'private_key_id' => '46ff9bf1b96709bd382468d945191f0e6ae971e4',
                'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC9E8duWioY8Svi\nO8ua8Ofofa3Y5G41rWczN3+DqbiaXG0m8AeGimkbWhckoMWgMw8k9/3VkhLpMeZZ\nv57ZXsx99zXrROtHWZJdRY8Uo8055HDWKwQG43d2HL7Ld0GBlPCbMUIb4bYdgN7F\nU5k99lIsM0JyZ+4578epQ8Jiy6ZQU/LZqICghJJaa1mRdONvsOJxMb0EcMm89mz2\nPRo6jvw3EEnNESKwP6/MWvSOa3jvPm9dtcieFc6djWnKSveZUsQmvknOmwn0li4U\nc5+80DcUiZCmadzH8e2kYWSifqtEjgU4usMySpc7J5zQhBfz/sATeanfEhaw34LH\n7oHLrdHxAgMBAAECggEAPLkW8f5cCcGeldwdUifvWs/OHtuR94Q2ohC/T5IY2t43\nIeuxhwh5pBKlXxqRDZeIVBee+lGc6C6h643qXqYe54+WzfXG2BXQt0rJHlhS5zZR\n9L5A31N7QMAPmHm23H5wXW8O67RsEAKcyOPF0cwDbPWTb/mn3QyAY4CC88aypZYa\n8J032cUjY2C7TZNI6DLMu7a/uk7pudQmm+5qqAg3Y+GHklucEuFn87KwlOUyP5AS\nT1V3r/pASUNVIqVxWEGjCCJ8PcmpYlXFqbHrif2T6hA8hcgZuItmQQrgm4FgdlO4\nNxQ+BgabvfdFEmDCs+WKNcE1sfNCwjD9wSauB1B9pwKBgQDmPCu2Ys4GLaA9+xHR\nVHXnL072Htzo2849Ny/8mKzsogFBZnVJJRComZtHHpPcOq+RBv8UYkyZlBjM8577\nhIoQn6FSoLVWzwcCInFRRn2M0U3VpiCt5FDmwTrh/sV+0Asgfy+4yBD74Dsz9j8T\nP0qrDVN7RkGSQvpHe1jFMVuD6wKBgQDSPIKd0nKxzQhs1qF1+5X/bdlene4UHPIO\nVWjUW3FajCmY6Q3rxR9KkVBmtU0yIucWifJhoSJ2Pg8T1EAciR97A+3leQdxn/cn\nIqKyWHYfDLkhLQadf1Z7h+VsqZkmIkyzaydBJSAonvsv52CSlb7pdXKEdA7+9Bes\nD24pjwi2kwKBgQDiYn3067h7OLfc2hgOBgec19bDiVtcmG11oeNMc+9tCmtDnHRt\nBMYZklitmLJ5FTd22jNNHs8FICs2s8I6vHtplxzwe1dz0UOp5KYZ7b8cDN6E2sI9\nWkf2bj/w4ivd4sFeJdGN1yNkF3r/P/2Ldmt8QDE7AL0PDU69pNqHo13CGwKBgH+G\n6Qxm/d/QqY2r3CE/TUu2DKcSUbqtnD8JnB9EoMv2Pb1dkW41Erge0ZCb5YnGFjtb\nzkVegxMKrgavN3yXyRrL1WDfO563FCsPHXXlpzFKfHodX+fluEYStx5k+lthbvle\nxF8F8C3z0hbK2Y/Reg8PiTnDVN1en4VljsNy2iw1AoGAEoP/YSHpSUOMs7kfTylk\nO13wGqEVFl9iJa+o9Huwsmr2x0kCK+T9rAU0FB7T7HdcLt9rTGG74jy2eSvLbEUl\nRib9Xr50u6GpCjy6RwT+X4IKxQWI8R/Ns8zw6hq9A4z5JbUZwFbmcyW2a+jSgK5y\nXy4/ZVzqy5Vb558FxTYDVm4=\n-----END PRIVATE KEY-----\n",
                'client_email' => "firebase-adminsdk-1y1cf@musoftware-c0696.iam.gserviceaccount.com",
                'client_id' => "117518869550883213244",
                'auth_uri' => "https://accounts.google.com/o/oauth2/auth",
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_x509_cert_url' => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-1y1cf%40musoftware-c0696.iam.gserviceaccount.com",
                'universe_domain' => 'googleapis.com',
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Auth Component
             * ------------------------------------------------------------------------
             */

            'auth' => [
                'tenant_id' => env('FIREBASE_AUTH_TENANT_ID'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firestore Component
             * ------------------------------------------------------------------------
             */

            'firestore' => [

                /*
                 * If you want to access a Firestore database other than the default database,
                 * enter its name here.
                 *
                 * By default, the Firestore client will connect to the `(default)` database.
                 *
                 * https://firebase.google.com/docs/firestore/manage-databases
                 */

                // 'database' => env('FIREBASE_FIRESTORE_DATABASE'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Realtime Database
             * ------------------------------------------------------------------------
             */

            'database' => [

                /*
                 * In most of the cases the project ID defined in the credentials file
                 * determines the URL of your project's Realtime Database. If the
                 * connection to the Realtime Database fails, you can override
                 * its URL with the value you see at
                 *
                 * https://console.firebase.google.com/u/1/project/_/database
                 *
                 * Please make sure that you use a full URL like, for example,
                 * https://my-project-id.firebaseio.com
                 */

                'url' => env('FIREBASE_DATABASE_URL'),

                /*
                 * As a best practice, a service should have access to only the resources it needs.
                 * To get more fine-grained control over the resources a Firebase app instance can access,
                 * use a unique identifier in your Security Rules to represent your service.
                 *
                 * https://firebase.google.com/docs/database/admin/start#authenticate-with-limited-privileges
                 */

                // 'auth_variable_override' => [
                //     'uid' => 'my-service-worker'
                // ],

            ],

            'dynamic_links' => [

                /*
                 * Dynamic links can be built with any URL prefix registered on
                 *
                 * https://console.firebase.google.com/u/1/project/_/durablelinks/links/
                 *
                 * You can define one of those domains as the default for new Dynamic
                 * Links created within your project.
                 *
                 * The value must be a valid domain, for example,
                 * https://example.page.link
                 */

                'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Cloud Storage
             * ------------------------------------------------------------------------
             */

            'storage' => [

                /*
                 * Your project's default storage bucket usually uses the project ID
                 * as its name. If you have multiple storage buckets and want to
                 * use another one as the default for your application, you can
                 * override it here.
                 */

                'default_bucket' => env('FIREBASE_STORAGE_DEFAULT_BUCKET'),

            ],

            /*
             * ------------------------------------------------------------------------
             * Caching
             * ------------------------------------------------------------------------
             *
             * The Firebase Admin SDK can cache some data returned from the Firebase
             * API, for example Google's public keys used to verify ID tokens.
             *
             */

            'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),

            /*
             * ------------------------------------------------------------------------
             * Logging
             * ------------------------------------------------------------------------
             *
             * Enable logging of HTTP interaction for insights and/or debugging.
             *
             * Log channels are defined in config/logging.php
             *
             * Successful HTTP messages are logged with the log level 'info'.
             * Failed HTTP messages are logged with the log level 'notice'.
             *
             * Note: Using the same channel for simple and debug logs will result in
             * two entries per request and response.
             */

            'logging' => [
                'http_log_channel' => env('FIREBASE_HTTP_LOG_CHANNEL'),
                'http_debug_log_channel' => env('FIREBASE_HTTP_DEBUG_LOG_CHANNEL'),
            ],

            /*
             * ------------------------------------------------------------------------
             * HTTP Client Options
             * ------------------------------------------------------------------------
             *
             * Behavior of the HTTP Client performing the API requests
             */

            'http_client_options' => [

                /*
                 * Use a proxy that all API requests should be passed through.
                 * (default: none)
                 */

                'proxy' => env('FIREBASE_HTTP_CLIENT_PROXY'),

                /*
                 * Set the maximum amount of seconds (float) that can pass before
                 * a request is considered timed out
                 *
                 * The default time out can be reviewed at
                 * https://github.com/kreait/firebase-php/blob/6.x/src/Firebase/Http/HttpClientOptions.php
                 */

                'timeout' => env('FIREBASE_HTTP_CLIENT_TIMEOUT'),

                'guzzle_middlewares' => [],
            ],
        ],
    ],
];
