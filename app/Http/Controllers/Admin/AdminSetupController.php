<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SetupChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AdminSetupController extends Controller
{
    public function index(SetupChecklist $checklist): Response
    {
        return Inertia::render('Admin/System/Setup', [
            'groups' => $checklist->groups(),
            'texts' => __('setup'),
        ]);
    }

    /** Tick or untick a step that is done outside the site. */
    public function mark(Request $request, string $key, SetupChecklist $checklist): RedirectResponse
    {
        abort_unless($checklist->isManual($key), 404);

        $done = $request->boolean('done');
        $checklist->markManual($key, $done);
        Log::info('Admin setup step marked', ['key' => $key, 'done' => $done, 'admin_id' => auth()->id()]);

        return back();
    }
}
