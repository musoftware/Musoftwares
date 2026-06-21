<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MessageActivityRead extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function thread()
    {
        return $this->morphTo('thread')->with('user');
    }

    // public static function heard($activity)
    // {
    //     $usr = Auth::user();

    //     if (
    //         MessageActivityRead::query()
    //         ->where('message_activity_id', $activity->id)
    //         ->where('user_id', $usr->id)->count() > 0
    //     ) {
    //         $find = MessageActivityRead::query()->where('message_activity_id', $activity->id)->first();
    //         $find->heard = 1;
    //         $find->save();
    //     } else {
    //         $new_read = new MessageActivityRead();
    //         $new_read->message_activity_id = $activity->id;
    //         $new_read->user_id = $usr->id;
    //         $new_read->heard = 1;
    //         $new_read->save();
    //     }
    // }
    public static function read($activity)
    {
        $usr = Auth::user();
        if ($activity->user_id == $usr->id) return;
        if (
            MessageActivityRead::query()
            ->where('message_activity_id', $activity->id)
            ->where('user_id', $usr->id)->count() > 0
        ) {
            $find = MessageActivityRead::query()->where('message_activity_id', $activity->id)->first();
            if ($find != null) {
                if ($find->read != 1) {
                    $find->read = 1;
                    $find->save();
                }
            }
        } else {
            $new_read = new MessageActivityRead();

            $new_read->thread()->associate($activity->thread);
            $new_read->message_activity_id = $activity->id;
            $new_read->user_id = $usr->id;
            $new_read->read = 1;
            $new_read->save();
        }
    }

    public static function MarkAsRead($activities)
    {
        foreach ($activities as $activity) {
            static::read($activity);
        }
    }
}
