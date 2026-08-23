<?php

namespace App\Http\Controllers;

use App\Support\Metrics\MetricsRecorder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function __invoke(MetricsRecorder $metrics): Response
    {
        $metrics->setAppInfo(
            (string) config('app.version', 'dev'),
            (string) config('panel.mode', 'managed'),
            (string) config('database.default', 'sqlite'),
        );

        if (config('queue.default') === 'database') {
            $metrics->setQueueDepth((int) DB::table('jobs')->count());
        }

        return response($metrics->render(), Response::HTTP_OK, [
            'Content-Type' => $metrics->contentType(),
        ]);
    }
}
