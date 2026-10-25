<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent after a guest pays for a plan and an account was opened for them.
 * Carries only the one-time signed set-password link — never a password.
 */
class PlanAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $setLink,
        public readonly string $planTitle,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your {$this->planTitle} is active - set your password");
    }

    public function content(): Content
    {
        $name = e($this->user->name);
        $plan = e($this->planTitle);
        $link = e($this->setLink);

        return new Content(htmlString: <<<HTML
        <div dir="auto" style="font-family:Arial,sans-serif;line-height:1.6">
        <p>Hello {$name},</p>
        <p>Thank you for your payment. Your <strong>{$plan}</strong> is now active.</p>
        <p>We opened an account for you with this email. Choose a password to sign in. You will be taken straight to your account:</p>
        <p><a href="{$link}">Set my password and open my account</a></p>
        <p>The link works once and expires in 24 hours.</p>
        <hr>
        <p>مرحبًا {$name}،</p>
        <p>شكرًا على الدفع. تم تفعيل <strong>{$plan}</strong>.</p>
        <p>أنشأنا لك حسابًا بهذا البريد. اختر كلمة مرور لتدخل مباشرةً إلى حسابك:</p>
        <p><a href="{$link}">اختيار كلمة المرور وفتح حسابي</a></p>
        <p>الرابط يُستخدم مرة واحدة وينتهي خلال 24 ساعة.</p>
        </div>
        HTML);
    }
}
