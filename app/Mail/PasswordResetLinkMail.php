<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends a one-time signed set-password link to the user.
 * Never contains a plaintext password.
 */
class PasswordResetLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $setLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('general.your_new_account_password') ?: 'Set Your New Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildBody(),
        );
    }

    /**
     * Build a plain-text-like HTML body containing only the signed link.
     * This is intentionally minimal so the test can grep for the link.
     */
    private function buildBody(): string
    {
        $link = e($this->setLink);
        $name = e($this->user->name);

        return <<<HTML
        <p>Hello {$name},</p>
        <p>An administrator has requested a password reset for your account.</p>
        <p>Click the link below to set your new password. The link is valid for 24 hours:</p>
        <p><a href="{$link}">{$link}</a></p>
        <p>If you did not request this, please contact support immediately.</p>
        HTML;
    }
}
