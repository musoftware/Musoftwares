<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, Searchable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'phone',
        'onboarding_completed',
        'country',
        'city',
        'mobile_1',
        'mobile_2',
        'telegram_username',
        'whatsapp_number',
        'preferred_currency',
        'preferred_currency_locked_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_completed' => 'boolean',
            'preferred_currency_locked_at' => 'datetime',
        ];
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(\Modules\Core\Models\SupportTicket::class, 'client_id');
    }

    public function conversationParticipations(): HasMany
    {
        return $this->hasMany(\Modules\Core\Models\ConversationParticipant::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(\Modules\Core\Models\Message::class, 'sender_id');
    }

    public function freelanceSkills()
    {
        return $this->belongsToMany(\Modules\Freelance\Models\Skill::class, 'freelance_user_skills')
            ->withTimestamps();
    }

    public function client()
    {
        return $this->hasOne(\Modules\ERP\Models\TenantClient::class, 'email', 'email');
    }

    public function pointTransactions()
    {
        return $this->hasMany(\Modules\Freelance\Models\PointTransaction::class);
    }

    public function getPointsBalanceAttribute()
    {
        $earned = $this->pointTransactions()->whereIn('type', ['earned', 'credit'])->sum('points');
        $spent = $this->pointTransactions()->whereIn('type', ['spent', 'debit'])->sum('points');
        return $earned - $spent;
    }
}
