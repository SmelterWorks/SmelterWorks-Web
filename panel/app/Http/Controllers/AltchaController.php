<?php

namespace App\Http\Controllers;

use App\Support\Altcha\AltchaService;
use Illuminate\Http\JsonResponse;

class AltchaController extends Controller
{
    public function challenge(AltchaService $altcha): JsonResponse
    {
        if (! $altcha->enabled() || $altcha->driver() !== 'standalone') {
            abort(404);
        }

        return response()->json($altcha->createChallenge());
    }
}
