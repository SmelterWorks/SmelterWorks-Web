<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ServersPageController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.servers', [
            'apiUrl' => url('/api/v1/servers/list'),
            'officialListUrl' => config('smelterworks.servers.official_list_url'),
        ]);
    }
}
