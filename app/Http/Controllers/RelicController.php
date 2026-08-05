<?php

namespace App\Http\Controllers;

use App\Support\Platform\PlatformDetector;
use App\Support\Relic\RelicCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RelicController extends Controller
{
    public function __construct(
        private readonly PlatformDetector $platforms,
        private readonly RelicCatalog $relic,
    ) {}

    public function show(): View
    {
        return view('pages.relic', [
            'relic' => $this->relic->forView(),
        ]);
    }

    public function download(Request $request): View
    {
        $relic = $this->relic->forView();
        $detected = $this->platforms->detect($request->userAgent());

        /** @var list<array<string, mixed>> $downloads */
        $downloads = $relic['downloads'] ?? [];

        $suggested = collect($downloads)->firstWhere('id', $detected['id']);

        return view('pages.relic.download', [
            'relic' => $relic,
            'downloads' => $downloads,
            'detected' => $detected,
            'suggested' => $suggested,
        ]);
    }
}
