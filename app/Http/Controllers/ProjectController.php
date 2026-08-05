<?php

namespace App\Http\Controllers;

use App\Support\Content\ProjectCatalog;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectCatalog $projects,
    ) {}

    public function index(): View
    {
        return view('pages.projects.index', [
            'projects' => $this->projects->all(),
        ]);
    }

    public function show(string $slug): View
    {
        return view('pages.projects.show', [
            'project' => $this->projects->findOrFail($slug),
        ]);
    }
}
