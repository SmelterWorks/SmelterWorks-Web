<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class PanelDemoController extends Controller
{
    public function mods(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $orderBy = (string) $request->query('orderby', 'downloads');
        $allowedOrder = ['downloads', 'trendingpoints', 'lastreleased', 'follows', 'asset.created'];

        if (! in_array($orderBy, $allowedOrder, true)) {
            $orderBy = 'downloads';
        }

        $params = [
            'orderby' => $orderBy,
            'orderdirection' => 'desc',
        ];

        if ($query !== '') {
            $params['text'] = $query;
        }

        $response = Http::timeout(12)
            ->acceptJson()
            ->get('https://mods.vintagestory.at/api/mods', $params);

        if (! $response->successful()) {
            return response()->json([
                'mods' => [],
                'error' => 'ModDB is unavailable right now.',
            ], 502);
        }

        $mods = collect($response->json('mods') ?? [])
            ->take(24)
            ->map(function (array $mod): array {
                $modid = '';

                if (isset($mod['modidstrs']) && is_array($mod['modidstrs']) && $mod['modidstrs'] !== []) {
                    $modid = (string) $mod['modidstrs'][0];
                } elseif (filled($mod['urlalias'] ?? null)) {
                    $modid = (string) $mod['urlalias'];
                } else {
                    $modid = (string) ($mod['modid'] ?? '');
                }

                $logo = null;

                if (filled($mod['logo'] ?? null)) {
                    $logo = (string) $mod['logo'];
                } elseif (filled($mod['logofile'] ?? null)) {
                    $logo = 'https://mods.vintagestory.at'.(string) $mod['logofile'];
                }

                $tags = [];

                if (isset($mod['tags']) && is_array($mod['tags'])) {
                    $tags = array_values(array_filter(array_map(
                        static fn ($tag): string => is_string($tag) ? $tag : '',
                        $mod['tags'],
                    )));
                }

                return [
                    'modid' => $modid,
                    'name' => (string) ($mod['name'] ?? 'Unknown mod'),
                    'author' => (string) ($mod['author'] ?? 'Unknown'),
                    'version' => (string) ($mod['version'] ?? $mod['modversion'] ?? ''),
                    'summary' => (string) ($mod['summary'] ?? ''),
                    'logo' => $logo,
                    'downloads' => (int) ($mod['downloads'] ?? 0),
                    'follows' => (int) ($mod['follows'] ?? 0),
                    'side' => (string) ($mod['side'] ?? ''),
                    'tags' => $tags,
                    'url' => filled($mod['urlalias'] ?? null)
                        ? 'https://mods.vintagestory.at/show/mod/'.(string) $mod['urlalias']
                        : null,
                ];
            })
            ->filter(fn (array $mod): bool => $mod['modid'] !== '')
            ->values()
            ->all();

        return response()->json(['mods' => $mods]);
    }
}
