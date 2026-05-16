<?php

namespace Modules\Core\Services;

use Modules\Core\Models\AdminNote;
use Illuminate\Database\Eloquent\Model;

class AdminNoteService
{
    public function addNote(Model $noteable, array $data): AdminNote
    {
        return $noteable->notes()->create(array_merge($data, [
            'author_id' => auth()->id(),
        ]));
    }

    public function updateNote(AdminNote $note, array $data): AdminNote
    {
        $note->update($data);
        return $note;
    }

    public function togglePin(AdminNote $note): AdminNote
    {
        $note->update(['is_pinned' => !$note->is_pinned]);
        return $note;
    }

    public function deleteNote(AdminNote $note): void
    {
        $note->delete();
    }
}
