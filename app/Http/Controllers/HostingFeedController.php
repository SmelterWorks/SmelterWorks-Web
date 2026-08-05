<?php

namespace App\Http\Controllers;

use App\Support\Hosting\HostingFeedBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class HostingFeedController extends Controller
{
    public function __invoke(HostingFeedBuilder $feed): Response
    {
        $configPath = config_path('smelterworks/hosting.php');
        $mtime = is_file($configPath) ? (string) filemtime($configPath) : '0';
        $soon = config('smelterworks.hosting.coming_soon') ? '1' : '0';
        $cacheKey = "hosting.rss.{$mtime}.{$soon}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($feed): array {
            return $feed->build();
        });

        return response()
            ->view('feeds.hosting', [
                'feed' => $payload,
            ], 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300');
    }
}
