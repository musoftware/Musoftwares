<?php

namespace Modules\AppBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AppBuilder\Models\BuilderSiteTour;
use Modules\AppBuilder\Services\Chat\ProjectChat;
use Modules\AppBuilder\Services\SiteTours;
use Modules\AppBuilder\Services\WorkerRoles;

/** The worker takes a site the customer wants copied, walks through it, and sends back the pictures and notes. */
class SiteTourController extends Controller
{
    public function __construct(private readonly SiteTours $tours, private readonly ProjectChat $chat) {}

    public function pending(Request $request, WorkerRoles $roles): JsonResponse
    {
        // a backup worker waits while the main one is alive, so a site is walked through once
        return response()->json(['tours' => $roles->mayTakeNewWork($request) ? $this->tours->forWorker() : []]);
    }

    public function store(Request $request, BuilderSiteTour $tour): JsonResponse
    {
        $data = $request->validate([
            'ok' => ['required', 'boolean'],
            'logged_in' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:300'],
            'report' => ['nullable', 'string', 'max:200000'],
            'pictures' => ['nullable', 'array', 'max:'.max(1, (int) config('builder.tours.max_pages'))],
            'pictures.*' => ['file', 'mimes:jpg,jpeg,png', 'max:4096'],
        ]);

        if ($this->tours->complete($tour, $data, $request->file('pictures', []))) {
            $this->chat->continueAfterTour($tour->refresh());
        }

        return response()->json(['status' => 'ok']);
    }
}
