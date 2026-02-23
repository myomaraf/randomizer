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
        $validated = $request->validated();

        $result = $randomizerService->pick(
            $validated['raffle_id'],
            $validated['ticket_uuids']
        );

        return response()->json($result);
    }
}
