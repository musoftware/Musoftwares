<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'anonymous_name',
        'anonymous_email',
        'ticket_subject',
        'ticket_message',
        'ticket_status',
        'priority',
        'rate',
        'assigned_employee_id',
        'closed_at',
    ];

    public function close(): void
    {
        $this->ticket_status = 'closed';
        $this->closed_at     = now();
        $this->save();
    }

    public function reopen(): void
    {
        $this->ticket_status = 'open';
        $this->closed_at     = null;
        $this->save();
    }

    public function status_color()
    {
        switch ($this->ticket_status) {
            case 'open':
                return 'badge bg-primary';
            case 'agent_replied':
                return 'badge bg-warning text-dark';
            case 'user_replied':
                return 'badge bg-info';
            case 'closed':
                return 'badge bg-success';
            default:
                return 'badge bg-secondary';
        }
    }

    public function client_status_color()
    {
        switch ($this->ticket_status) {
            case 'open':
                return 'badge bg-info';
            case 'agent_replied':
                return 'badge bg-warning text-dark';
            case 'user_replied':
                return 'badge bg-primary';
            case 'closed':
                return 'badge bg-success';
            default:
                return 'badge bg-secondary';
        }
    }

    public function priority_badge()
    {
        switch ($this->priority) {
            case 'high':
                return 'badge bg-danger';
            case 'medium':
                return 'badge bg-warning text-dark';
            case 'low':
                return 'badge bg-success';
            default:
                return 'badge bg-secondary';
        }
    }

    public function status_text()
    {
        switch ($this->ticket_status) {
            case 'open':
                return 'Open';
            case 'agent_replied':
                return 'Agent Replied';
            case 'user_replied':
                return 'You Replied';
            case 'closed':
                return 'Closed';
            default:
                return ucfirst(str_replace('_', ' ', $this->ticket_status));
        }
    }

    public function client_status_text()
    {
        switch ($this->ticket_status) {
            case 'open':
                return 'Awaiting Support';
            case 'agent_replied':
                return 'Action Required';
            case 'user_replied':
                return 'Waiting for Reply';
            case 'closed':
                return 'Resolved';
            default:
                return ucfirst(str_replace('_', ' ', $this->ticket_status));
        }
    }

    public function priority_text()
    {
        return ucfirst($this->priority);
    }

    public function is_urgent()
    {
        return $this->priority === 'high' && $this->ticket_status !== 'closed';
    }

    public function is_awaiting_reply()
    {
        return in_array($this->ticket_status, ['agent_replied', 'user_replied']);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

    public function isAnonymous()
    {
        return is_null($this->user_id);
    }

    public function getDisplayName()
    {
        if ($this->isAnonymous()) {
            return $this->anonymous_name ?? 'Anonymous';
        }
        return $this->user->name ?? 'Unknown';
    }

    public function getDisplayEmail()
    {
        if ($this->isAnonymous()) {
            return $this->anonymous_email;
        }
        return $this->user->email ?? 'Unknown';
    }

    /**
     * Scope to get tickets awaiting admin reply
     */
    public function scopeAwaitingAdminReply($query)
    {
        return $query->where('ticket_status', 'user_replied');
    }

    /**
     * Scope to get tickets with no response within specified hours
     */
    public function scopeNoResponse($query, $hours = 24)
    {
        return $query->where('ticket_status', 'open')
            ->where('created_at', '<=', now()->subHours($hours));
    }

    /**
     * Check if ticket needs immediate attention
     */
    public function needsAttention()
    {
        if ($this->priority === 'high' && $this->ticket_status !== 'closed') {
            return true;
        }

        if ($this->ticket_status === 'user_replied') {
            return true;
        }

        if ($this->ticket_status === 'open' && $this->created_at <= now()->subHours(24)) {
            return true;
        }

        return false;
    }

    /**
     * Link to the modern Conversation model in the new architecture.
     */
    public function conversation()
    {
        return $this->morphOne(Conversation::class, 'conversable');
    }
}
