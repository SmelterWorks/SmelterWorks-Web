<?php

namespace App\Http\Controllers;

use App\Support\Servers\MasterServerListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerListApiController extends Controller
{
    public function __invoke(Request $request, MasterServerListService $servers): JsonResponse
    {
        $payload = $servers->payload();
        $meta = $payload['meta'];
        $etag = $meta['etag'] ?? null;

        if (is_string($etag) && $etag !== '' && $request->headers->get('If-None-Match') === $etag) {
            return response()
                ->json(null, 304)
                ->withHeaders($this->responseHeaders($meta['cache'], $etag));
        }

        return response()
            ->json([
                'status' => $payload['status'],
                'data' => $payload['data'],
                'meta' => $payload['meta'],
            ], $payload['status'] === 'ok' ? 200 : 503)
            ->withHeaders($this->responseHeaders($meta['cache'], $etag));
    }

    /**
     * @return array<string, string>
     */
    private function responseHeaders(string $cacheState, ?string $etag): array
    {
        $headers = [
            'X-Cache' => $cacheState,
            'Cache-Control' => 'public, max-age=60',
            'Access-Control-Allow-Origin' => '*',
        ];

        if (is_string($etag) && $etag !== '') {
            $headers['ETag'] = $etag;
        }

        return $headers;
    }
}
