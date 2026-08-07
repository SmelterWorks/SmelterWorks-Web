<?php

namespace App\Http\Controllers;

use App\Support\Updates\UpdateManifestPresenter;
use App\Support\Updates\UpdateMirrorService;
use App\Support\Updates\UpdateProductRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateManifestController extends Controller
{
    public function __invoke(
        Request $request,
        string $product,
        string $channel,
        UpdateProductRegistry $registry,
        UpdateMirrorService $mirror,
        UpdateManifestPresenter $presenter,
    ): JsonResponse {
        if ($registry->channel($product, $channel) === null) {
            return response()
                ->json(['error' => 'not_found'], 404)
                ->header('Cache-Control', 'public, max-age=60');
        }

        $manifest = $mirror->getChannelManifest($product, $channel);

        if ($manifest === null) {
            return response()
                ->json(['error' => 'not_ready'], 503)
                ->header('Cache-Control', 'public, max-age=60');
        }

        $etag = $presenter->etag($manifest);

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()
                ->json(null, 304)
                ->withHeaders($this->responseHeaders($etag));
        }

        return response()
            ->json($presenter->present($manifest))
            ->withHeaders($this->responseHeaders($etag));
    }

    /**
     * @return array<string, string>
     */
    private function responseHeaders(string $etag): array
    {
        return [
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=300, stale-while-revalidate=3600',
        ];
    }
}
