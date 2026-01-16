<?php

/**
 * M'Manager - Shared Hosting Compatibility
 *
 * This file allows M'Manager to work on shared hosting environments
 * where users cannot configure the document root to point to /public.
 *
 * SECURITY NOTE: For better security and performance, configure your
 * server to use the /public directory as the document root instead.
 */

// Block access to sensitive paths even if .htaccess fails
$requestUri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$blockedPaths = [
    '.env', '.git', 'artisan', 'composer.json', 'composer.lock',
    'package.json', 'package-lock.json', 'phpunit.xml', 'webpack.mix.js',
    'vite.config.js', '.editorconfig', '.gitignore', '.gitattributes',
];
$blockedDirs = [
    'app', 'bootstrap', 'config', 'database', 'resources', 'routes',
    'storage', 'tests', 'vendor', 'node_modules',
];

// Check blocked files
foreach ($blockedPaths as $blocked) {
    if ($requestUri === '/' . $blocked || str_starts_with($requestUri, '/' . $blocked)) {
        http_response_code(403);
        exit('Access Denied');
    }
}

// Check blocked directories
foreach ($blockedDirs as $dir) {
    if (preg_match('#^/' . preg_quote($dir, '#') . '(/|$)#', $requestUri)) {
        http_response_code(403);
        exit('Access Denied');
    }
}

// Handle direct file requests (css, js, images) from public folder only
if ($requestUri !== '/' && file_exists(__DIR__ . '/public' . $requestUri)) {
    $extension = strtolower(pathinfo($requestUri, PATHINFO_EXTENSION));

    // Only serve whitelisted static file types
    $allowedExtensions = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'png'   => 'image/png',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'webp'  => 'image/webp',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'map'   => 'application/json',
        'pdf'   => 'application/pdf',
        'txt'   => 'text/plain',
    ];

    if (isset($allowedExtensions[$extension])) {
        // Prevent path traversal
        $realPath = realpath(__DIR__ . '/public' . $requestUri);
        $publicPath = realpath(__DIR__ . '/public');

        if ($realPath && str_starts_with($realPath, $publicPath)) {
            header('Content-Type: ' . $allowedExtensions[$extension]);
            header('X-Content-Type-Options: nosniff');
            readfile($realPath);
            exit;
        }
    }
}

// Change to public directory context for Laravel
chdir(__DIR__ . '/public');

// Load Laravel from the public directory
require_once __DIR__ . '/public/index.php';
