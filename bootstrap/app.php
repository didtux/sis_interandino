<?php

/*
|--------------------------------------------------------------------------
| Polyfills
|--------------------------------------------------------------------------
| `mime_content_type()` requires el módulo PHP `fileinfo`. Si XAMPP/PHP
| no lo tiene activo, librerías como PhpSpreadsheet rompen con:
|   "Call to undefined function ... mime_content_type()".
| Polyfill por extensión: detecta el MIME por la firma de los bytes y
| cubre los formatos que la app realmente necesita (imágenes y office).
*/
if (!function_exists('mime_content_type')) {
    function mime_content_type($filename)
    {
        if (!is_readable($filename) || !is_file($filename)) {
            return false;
        }
        $h = @fopen($filename, 'rb');
        if (!$h) return 'application/octet-stream';
        $bytes = fread($h, 12);
        fclose($h);
        if ($bytes === false || $bytes === '') return 'application/octet-stream';

        if (strncmp($bytes, "\xFF\xD8\xFF", 3) === 0) return 'image/jpeg';
        if (strncmp($bytes, "\x89PNG\r\n\x1A\n", 8) === 0) return 'image/png';
        if (strncmp($bytes, "GIF87a", 6) === 0 || strncmp($bytes, "GIF89a", 6) === 0) return 'image/gif';
        if (strncmp($bytes, "BM", 2) === 0) return 'image/bmp';
        if (strncmp($bytes, "RIFF", 4) === 0 && substr($bytes, 8, 4) === 'WEBP') return 'image/webp';
        if (strncmp($bytes, "%PDF-", 5) === 0) return 'application/pdf';
        if (strncmp($bytes, "PK\x03\x04", 4) === 0) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($ext === 'xlsx') return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            if ($ext === 'docx') return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            if ($ext === 'pptx') return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            return 'application/zip';
        }
        if (strncmp($bytes, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", 8) === 0) return 'application/vnd.ms-office';
        return 'application/octet-stream';
    }
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
