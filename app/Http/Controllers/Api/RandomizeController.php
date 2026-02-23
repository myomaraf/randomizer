<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RandomizeRequest;
use App\Services\RandomizerService;
use Illuminate\Http\JsonResponse;

class RandomizeController extends Controller
{
    public function __invoke(RandomizeRequest $request, RandomizerService $randomizerService): JsonResponse
    {
        $result = $randomizerService->pick($request->validated('uuids'));

        return response()->json($result);
    }
}
