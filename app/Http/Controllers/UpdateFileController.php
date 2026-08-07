<?php

namespace App\Http\Controllers;

use App\Support\Updates\UpdateFileServer;
use Symfony\Component\HttpFoundation\Response;

class UpdateFileController extends Controller
{
    public function __invoke(
        string $product,
        string $version,
        string $filename,
        UpdateFileServer $files,
    ): Response {
        return $files->serve($product, $version, $filename);
    }
}
