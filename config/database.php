<?php
/**
 * MongoDB connection for GUCAIR.
 *
 * Keep database access in one place so pages stay short and beginner-friendly.
 * Run `composer install` in the project root before using this file.
 */

// Composer creates this file. Without it, MongoDB\Client is not available.
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    die('<h1>Database setup incomplete</h1><p>Run <code>composer install</code> inside the <code>gucair</code> folder, then reload this page.</p>');
}
require_once $autoload;

if (!function_exists('get_mongo_client')) {
    /**
     * Return one shared MongoDB client for the current request.
     *
     * MongoDB connects lazily, so a bad server address may only appear
     * when a collection query is actually run.
     */
    function get_mongo_client(): MongoDB\Client
    {
        static $client = null;

        if ($client === null) {
            // MONGODB_URI is used by Docker/Render/Atlas. Local XAMPP uses the fallback.
            $uri = getenv('MONGODB_URI');
            if (!is_string($uri) || trim($uri) === '') {
                $uri = 'mongodb://127.0.0.1:27017';
            }

            try {
                $client = new MongoDB\Client($uri);
            } catch (Throwable $error) {
                error_log('GUCAIR MongoDB connection failed: ' . $error->getMessage());
                throw new RuntimeException('MongoDB could not be reached. Check that MongoDB is running.');
            }
        }

        return $client;
    }
}

if (!function_exists('get_gucair_database')) {
    /**
     * Select the GUCAIR database.
     */
    function get_gucair_database(): MongoDB\Database
    {
        return get_mongo_client()->selectDatabase('gucair_database');
    }
}

if (!function_exists('get_gucair_collections')) {
    /**
     * Return both collections used by GUCAIR.
     *
     * Example:
     *   $collections = get_gucair_collections();
     *   $universities = $collections['universities']->find();
     */
    function get_gucair_collections(): array
    {
        $database = get_gucair_database();

        return [
            'universities' => $database->selectCollection('universities'),
            'assessments'  => $database->selectCollection('assessments'),
        ];
    }
}

if (!function_exists('get_universities_collection')) {
    /**
     * Shortcut for pages that only need universities.
     */
    function get_universities_collection(): MongoDB\Collection
    {
        return get_gucair_database()->selectCollection('universities');
    }
}

if (!function_exists('get_assessments_collection')) {
    /**
     * Shortcut for pages that only need assessments.
     */
    function get_assessments_collection(): MongoDB\Collection
    {
        return get_gucair_database()->selectCollection('assessments');
    }
}
