<?php

namespace App\Http\Controllers;

use App\Support\Content\ProjectCatalog;
use App\Support\Relic\RelicCatalog;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(ProjectCatalog $projects, RelicCatalog $relic): View
    {
        return view('pages.home', [
            'featuredProjects' => $projects->featured(),
            'relic' => $relic->forView(),
            'hostingComingSoon' => (bool) config('smelterworks.hosting.coming_soon'),
            'hostingHighlights' => config('smelterworks.hosting.home_highlights', []),
            'hostingNote' => (string) config('smelterworks.hosting.home_note', ''),
        ]);
    }
}
