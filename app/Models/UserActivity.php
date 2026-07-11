<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'activity_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function activity()
    {
        $ip = $this->ip;

        return 'User accessed the system from '.$this->iso_code.' ('.$ip.') for '.$this->total_seconds.' seconds.';
    }

    public static function TotalSecondsOfUser($users_id)
    {
        return static::query()->whereIn('user_id', $users_id)->sum('total_seconds');
    }

    public static function TotalSecondsOfUsers()
    {
        $admin = User::query()->whereHas('roles', function ($q) {
            $q->where('slug', 'admin');
        })->first();

        return static::query()->sum('total_seconds') - static::TotalSecondsOfUser([$admin->id]);
    }

    public static function latestActivities()
    {
        return static::query()->orderBy('id', 'desc')->limit(10)->get();
    }

    public static function RecordDate($user_id, $time, $ip)
    {
        $ipData = geoip()->getLocation($ip);

        $date = date('Y-m-d');

        $old_activities = static::query()
            ->where('activity_date', '<', date('Y-m-d', strtotime('-60 days')))->first();

        if ($old_activities != null) {
            $old_activities->delete();
        }

        $active = static::query()->where('user_id', $user_id)
            ->where('activity_date', $date)->first();

        if ($active == null) {
            static::create([
                'user_id' => $user_id,
                'activity_date' => $date,
                'total_seconds' => $time,
                'ip' => $ip,
                'iso_code' => $ipData->iso_code,
            ]);
        } else {
            $active->ip = $ip;
            $active->iso_code = $ipData->iso_code;
            $active->save();
            $active->increment('total_seconds', $time);
        }
    }
}
