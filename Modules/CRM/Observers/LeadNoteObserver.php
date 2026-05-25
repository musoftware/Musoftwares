<?php

namespace Modules\CRM\Observers;

use Modules\CRM\Models\LeadNote;

class LeadNoteObserver
{
    /**
     * Handle the LeadNote "created" event.
     */
    public function created(LeadNote $note): void
    {
        // Log the activity against the Lead, not the Note itself, so it shows up in the Lead Timeline
        activity()->log('note.created', $note->lead, null, ['note_id' => $note->id, 'note' => $note->note, 'is_pinned' => $note->is_pinned]);
    }

    /**
     * Handle the LeadNote "updated" event.
     */
    public function updated(LeadNote $note): void
    {
        $dirty = $note->getDirty();
        $original = array_intersect_key($note->getOriginal(), $dirty);
        
        activity()->log('note.updated', $note->lead, $original, $dirty);
    }

    /**
     * Handle the LeadNote "deleted" event.
     */
    public function deleted(LeadNote $note): void
    {
        activity()->log('note.deleted', $note->lead, $note->toArray(), null);
    }
}
