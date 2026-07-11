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

        if ($type === 'client') {
            $query->where('noteable_type', 'Modules\\ERP\\Models\\Client')
                ->where('noteable_id', $id);
        } else {
            $query->where('noteable_type', $type)
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
            'type' => 'required|string',
            'visibility' => 'required|string',
            'risk_level' => 'required|string',
        ]);

        if ($data['noteable_type'] === 'client') {
            $data['noteable_type'] = 'Modules\\ERP\\Models\\Client';
        }

        $data['author_id'] = auth()->id() ?? 1;

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
}
