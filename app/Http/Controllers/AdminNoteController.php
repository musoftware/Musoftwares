<?php

namespace App\Http\Controllers;

use App\Models\AdminNote;
use Illuminate\Http\Request;

class AdminNoteController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('noteable_type');
        $id = $request->query('noteable_id');

        $query = AdminNote::query();

        if ($type) {
            $query->where('noteable_type', $this->resolveNoteableType($type))
                ->where('noteable_id', $id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'noteable_type' => 'required|string',
            'noteable_id' => 'required|integer',
            'content' => 'required|string',
            'type' => 'required|string|max:50',
            'visibility' => 'required|string|max:50',
            'risk_level' => 'required|string|max:50',
        ]);

        $data['noteable_type'] = $this->resolveNoteableType($data['noteable_type']);
        $data['author_id'] = $request->user()?->id ?? auth()->id();

        $note = AdminNote::create($data);

        return response()->json($note);
    }

    public function togglePin(AdminNote $note)
    {
        $note->update([
            'is_pinned' => ! $note->is_pinned,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(AdminNote $note)
    {
        $note->delete();

        return response()->json(['success' => true]);
    }

    private function resolveNoteableType(string $type): string
    {
        return $type === 'client' ? \App\Models\User::class : $type;
    }
}
