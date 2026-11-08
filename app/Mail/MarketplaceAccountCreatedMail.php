<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent when a visitor orders a service without an account. Carries a link to choose a password, never a password. */
class MarketplaceAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $setPasswordLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'اتعملك حساب على موسوفت / Your Musoftware account');
    }

    public function content(): Content
    {
        $name = e($this->user->name);
        $link = e($this->setPasswordLink);
        $forgot = e(route('password.request'));

        return new Content(htmlString: <<<HTML
        <div dir="auto" style="font-family:Arial,sans-serif;line-height:1.7">
        <p>أهلًا {$name}،</p>
        <p>عملنالك حساب بالإيميل ده عشان تتابع طلبك. اختار كلمة سر عشان تقدر تدخل بعد كده.</p>
        <p><a href="{$link}">اختار كلمة السر</a></p>
        <p>لو اللينك انتهى، استخدم <a href="{$forgot}">نسيت كلمة السر</a>.</p>
        <hr>
        <p>Hello {$name},</p>
        <p>We opened an account with this email so you can follow your order. Choose a password to sign in later.</p>
        <p><a href="{$link}">Choose my password</a></p>
        <p>If the link has expired, use <a href="{$forgot}">forgot password</a>.</p>
        </div>
        HTML);
    }
}
