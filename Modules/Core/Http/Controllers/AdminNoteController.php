<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\AdminNote;
use Modules\Core\Services\AdminNoteService;

class AdminNoteController extends Controller
{
    protected $service;

    public function __construct(AdminNoteService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $request->validate([
            'noteable_type' => 'required|string',
            'noteable_id' => 'required|integer',
        ]);

        $type = $request->input('noteable_type');
        $id = $request->input('noteable_id');

        // Resolve morph class mapping cleanly
        $modelClass = $this->resolveMorphModel($type);
        if (!$modelClass || !class_exists($modelClass)) {
            return response()->json(['error' => 'Invalid noteable model classification.'], 422);
        }

        $notes = AdminNote::where('noteable_type', $modelClass)
            ->where('noteable_id', $id)
            ->with('author:id,name')
            ->latest()
            ->get();

        return response()->json(['data' => $notes]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'noteable_type' => 'required|string',
            'noteable_id' => 'required|integer',
            'content' => 'required|string|min:1',
            'type' => 'required|string|in:general,warning,fraud_risk,accounting,moderation,legal,support',
            'visibility' => 'required|string|in:staff_only,admins_only,private',
            'risk_level' => 'required|string|in:none,low,medium,high,critical',
        ]);

        $type = $request->input('noteable_type');
        $id = $request->input('noteable_id');

        $modelClass = $this->resolveMorphModel($type);
        if (!$modelClass || !class_exists($modelClass)) {
            return response()->json(['error' => 'Invalid morph target.'], 422);
        }

        $noteable = $modelClass::findOrFail($id);

        $note = $this->service->addNote($noteable, $request->only([
            'content',
            'type',
            'visibility',
            'risk_level',
        ]));

        // Reload author relationship for UI integration
        $note->load('author:id,name');

        return response()->json(['note' => $note]);
    }

    public function togglePin(AdminNote $note)
    {
        $this->service->togglePin($note);
        return response()->json(['note' => $note]);
    }

    public function destroy(AdminNote $note)
    {
        $this->service->deleteNote($note);
        return response()->json(['success' => true]);
    }

    protected function resolveMorphModel(string $type): ?string
    {
        // Handle short names or direct full classes safely
        if (str_contains($type, '\\')) {
            return $type;
        }

        $mappings = [
            'client' => \Modules\ERP\Models\Client::class,
            'invoice' => \Modules\Core\Models\Invoice::class,
        ];

        return $mappings[strtolower($type)] ?? null;
    }
}
