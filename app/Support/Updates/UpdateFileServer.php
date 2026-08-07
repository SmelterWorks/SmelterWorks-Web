<?php

namespace App\Support\Updates;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class UpdateFileServer
{
    public function __construct(
        private readonly UpdateProductRegistry $registry,
        private readonly UpdatePathValidator $paths,
        private readonly UpdateMirrorService $mirror,
    ) {}

    public function serve(string $product, string $version, string $filename): Response
    {
        if (
            ! $this->paths->isValidProduct($product)
            || ! $this->paths->isValidVersion($version)
            || ! $this->paths->isValidFilename($filename)
        ) {
            abort(404);
        }

        if (! $this->mirror->assetExists($product, $version, $filename)) {
            abort(404);
        }

        $disk = $this->disk();
        $relativePath = $this->mirror->assetRelativePath($product, $version, $filename);

        if ($this->registry->useAccelRedirect()) {
            return response('', 200, [
                'X-Accel-Redirect' => '/internal-updates/'.$relativePath,
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        $absolutePath = $disk->path($relativePath);

        return new BinaryFileResponse(
            $absolutePath,
            200,
            [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ],
            true,
        );
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->registry->diskName());
    }
}
