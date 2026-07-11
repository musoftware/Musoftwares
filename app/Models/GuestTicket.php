<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestTicket extends Model
{
    use HasFactory, SoftDeletes;

    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'subject',
        'body',
        'status',
        'last_message_at',
        'last_message_message_id',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(GuestTicketMessage::class)->orderBy('created_at')->orderBy('id');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(GuestTicketMessage::class)->latest('created_at')->latest('id');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function ($q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('subject', 'like', $like)
                ->orWhere('body', 'like', $like);
        });
    }

    public function scopeOfStatus($query, ?string $status)
    {
        if (! $status) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function replyEmail(): Attribute
    {
        return Attribute::get(function () {
            $domain = parse_url(config('app.url', 'https://www.musoftwares.com'), PHP_URL_HOST) ?: 'musoftwares.com';
            $domain = preg_replace('/^www\./', '', $domain);

            return "guest-tickets+{$this->id}@{$domain}";
        });
    }

    public function subjectTag(): string
    {
        return "[GuestTicket#{$this->id}]";
    }
}
