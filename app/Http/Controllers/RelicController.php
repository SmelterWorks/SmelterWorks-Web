<?php

namespace App\Http\Controllers;

use App\Support\Platform\PlatformDetector;
use App\Support\Relic\RelicCatalog;
use App\Support\Updates\UpdateMirrorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RelicController extends Controller
{
    public function __construct(
        private readonly PlatformDetector $platforms,
        private readonly RelicCatalog $relic,
        private readonly UpdateMirrorService $updates,
    ) {}

    public function show(): View
    {
        $relic = $this->relic->forView();

        if ($relic['stable_tag'] === null) {
            $this->queueMirrorWarmIfIdle();
        }

        return view('pages.relic', [
            'relic' => $relic,
        ]);
    }

    public function download(Request $request): View
    {
        $page = $this->relic->forDownloadPage();
        $detected = $this->platforms->detect($request->userAgent());

        if (! $page['stable']['available']) {
            $this->queueMirrorWarmIfIdle();
        }

        $suggested = collect($page['downloads'])->firstWhere('id', $detected['id']);

        return view('pages.relic.download', [
            'relic' => $page['relic'],
            'downloads' => $page['downloads'],
            'stable' => $page['stable'],
            'nightly' => $page['nightly'],
            'detected' => $detected,
            'suggested' => $suggested,
        ]);
    }

    private function queueMirrorWarmIfIdle(): void
    {
        app()->terminating(function (): void {
            $this->updates->warmProduct('relic');
        });
    }
}
