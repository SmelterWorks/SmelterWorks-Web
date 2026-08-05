<?php

declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__).'/public';
$path = $documentRoot.$uri;

if ($uri !== '/' && is_file($path)) {
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $cacheable = in_array($extension, [
        'css',
        'js',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'ico',
        'svg',
        'webp',
        'woff',
        'woff2',
    ], true);

    if ($cacheable) {
        header('Cache-Control: public, max-age=31536000, immutable');
    }

    $mimeType = match ($extension) {
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'webp' => 'image/webp',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        default => (string) (mime_content_type($path) ?: 'application/octet-stream'),
    };

    header('Content-Type: '.$mimeType);

    $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    $compressible = in_array($extension, ['css', 'js', 'svg', 'json', 'xml', 'txt', 'html'], true);

    if ($compressible && str_contains($acceptEncoding, 'gzip')) {
        $contents = file_get_contents($path);

        if ($contents !== false) {
            $encoded = gzencode($contents, 9);

            if ($encoded !== false) {
                header('Content-Encoding: gzip');
                header('Vary: Accept-Encoding');
                header('Content-Length: '.(string) strlen($encoded));
                echo $encoded;

                return true;
            }
        }
    }

    header('Content-Length: '.(string) filesize($path));
    readfile($path);

    return true;
}

require $documentRoot.'/index.php';
