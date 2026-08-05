<?php

namespace App\Http\Controllers;

use App\Support\Content\BrandingCatalog;
use App\Support\Content\ProjectCatalog;
use App\Support\Url\SafeExternalUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about', [
            'about' => config('smelterworks.about'),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'contact' => config('smelterworks.contact'),
            'links' => config('smelterworks.links'),
        ]);
    }

    public function branding(BrandingCatalog $branding): View
    {
        return view('pages.branding', $branding->forPage());
    }

    public function contribute(): View
    {
        return view('pages.contribute');
    }

    public function donate(): View
    {
        return view('pages.donate', [
            'donate' => config('smelterworks.donate'),
        ]);
    }

    public function mods(ProjectCatalog $projects): View
    {
        return view('pages.mods', [
            'mods' => $projects->mods(),
        ]);
    }

    public function privacy(): View
    {
        return view('pages.legal.privacy', [
            'legal' => config('smelterworks.legal'),
        ]);
    }

    public function terms(): View
    {
        return view('pages.legal.terms', [
            'legal' => config('smelterworks.legal'),
        ]);
    }

    public function panel(): View|RedirectResponse
    {
        $external = SafeExternalUrl::httpsOrNull(config('smelterworks.links.panel'));

        if ($external !== null) {
            return redirect()->away($external);
        }

        return view('pages.panel');
    }
}
