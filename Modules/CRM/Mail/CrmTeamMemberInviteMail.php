<?php

namespace Modules\CRM\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\CRM\Models\CrmTeamMember;
use Illuminate\Support\Facades\URL;

class CrmTeamMemberInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $member;
    public $inviteUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(CrmTeamMember $member)
    {
        $this->member = $member;
        
        // Generate a signed route that expires in 7 days
        $this->inviteUrl = URL::temporarySignedRoute(
            'crm.invite.accept',
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
            subject: __('crm.team_member_invite_subject', ['app_name' => config('app.name')]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'crm::emails.team_invite',
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
