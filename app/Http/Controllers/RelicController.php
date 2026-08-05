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
        $page = $this->relic->forDownloadPage();
        $detected = $this->platforms->detect($request->userAgent());

        $suggested = collect($page['downloads'])->firstWhere('id', $detected['id']);

        return view('pages.relic.download', [
            'relic' => $page['relic'],
            'downloads' => $page['downloads'],
            'nightly' => $page['nightly'],
            'detected' => $detected,
            'suggested' => $suggested,
        ]);
    }
}
