<?php

namespace App\Models;

use App\Trait\ChatModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class UserThread extends Model
{
    use ChatModelTrait;
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function ChatName()
    {
        return UserThread::class;
    }

    public function user()
    {
        if (Auth::check()) {
            if (Auth::id() == $this->user1_id) {
                return $this->belongsTo(User::class, 'user2_id');
            } else {
                return $this->belongsTo(User::class, 'user1_id');
            }
        }
    }

    public static function FindThreads($user1)
    {
        $threads = UserThread::query()->where('user1_id', $user1)->orWhere('user2_id', $user1)->get();

        return $threads;
    }

    public static function FindThread($user1, $user2)
    {
        $first_try = UserThread::query()->where('user1_id', $user1)->where('user2_id', $user2)->first();
        if ($first_try != null) {
            return $first_try;
        }
        $second_try = UserThread::query()->where('user2_id', $user1)->where('user1_id', $user2)->first();
        if ($second_try != null) {
            return $second_try;
        }
        if ($second_try == null) {
            return UserThread::create([
                'user1_id' => $user1,
                'user2_id' => $user2,
            ]);
        }

        return null;
    }

    public static function getUnreadMessages()
    {
        $user_id = Auth::user()->id;

        return MessageActivity::query()->where('user_id', '!=', $user_id)->whereHasMorph('thread', [UserThread::class], function ($query) use ($user_id) {

            $query->where('user1_id', $user_id)->orWhere('user2_id', $user_id);
        })->count() - MessageActivityRead::query()->where('thread_type', UserThread::class)->where('user_id', $user_id)->count();
    }
}
