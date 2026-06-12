<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

$publicPath = __DIR__.'/public'.$uri;

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && is_file($publicPath)) {
    $extension = pathinfo($publicPath, PATHINFO_EXTENSION);
    $mime = match ($extension) {
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'woff2' => 'font/woff2',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'json' => 'application/json; charset=UTF-8',
        default => mime_content_type($publicPath) ?: 'application/octet-stream',
    };
    header('Content-Type: '.$mime);
    header('Content-Length: '.filesize($publicPath));
    readfile($publicPath);

    return true;
}

require_once __DIR__.'/public/index.php';
