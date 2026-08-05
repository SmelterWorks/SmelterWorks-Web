<?php

namespace App\Http\Controllers;

use App\Support\Content\ProjectCatalog;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(ProjectCatalog $projects): View
    {
        return view('pages.home', [
            'featuredProjects' => $projects->featured(),
        ]);
    }
}
