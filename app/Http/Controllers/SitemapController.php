<?php

namespace App\Http\Controllers;

use App\Support\Seo\SitemapBuilder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(SitemapBuilder $sitemap): Response
    {
        return response()
            ->view('seo.sitemap', [
                'urls' => $sitemap->urls(),
            ], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300');
    }
}
