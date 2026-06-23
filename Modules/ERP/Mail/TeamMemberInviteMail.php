<?php

namespace Modules\ERP\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\ERP\Models\TeamMember;
use Illuminate\Support\Facades\URL;

class TeamMemberInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $member;
    public $inviteUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(TeamMember $member)
    {
        $this->member = $member;
        
        // Generate a signed route that expires in 7 days
        $this->inviteUrl = URL::temporarySignedRoute(
            'erp.invite.accept',
            now()->addDays(7),
            ['id' => $member->id]
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('erp.team_member_invite_subject', ['app_name' => config('app.name')]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'erp::emails.team_invite',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
