<?php

namespace App\Trait;

use App\Models\MessageActivity;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait ChatModelTrait
{
    /**
     * Get all message activities for this thread model.
     */
    public function messageActivities(): MorphMany
    {
        return $this->morphMany(MessageActivity::class, 'thread');
    }
}
