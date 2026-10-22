<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ClientSoftwareWinbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $clientFirstName;

    public function __construct(
        public readonly User $user,
        public readonly int $reminderNumber,
        public readonly string $couponCode,
        public readonly string $whatsappUrl,
        public readonly string $projectUrl,
        public readonly string $unsubscribeUrl,
    ) {
        $this->clientFirstName = explode(' ', trim($user->name ?: 'عميلنا العزيز'))[0];
    }

    public function envelope(): Envelope
    {
        $subjects = [
            1 => 'وحشتنا في Musoftwares — خصم 50% على مشروعك البرمجي القادم',
            2 => 'تذكير بخصم الـ 50% لمشروعك البرمجي — فريق Musoftwares في انتظارك',
            3 => 'هل تفكر في تطوير برمجيات جديدة؟ استمتع بخصم 50% الحصري',
            4 => 'فرصتك الأخيرة: خصم 50% على أي نظام برمجي جديد مع Musoftwares',
        ];

        $subject = $subjects[$this->reminderNumber] ?? 'عرض حصري: خصم 50% على مشروعك البرمجي من Musoftwares';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.winback.software_reactivation',
            with: [
                'clientFirstName' => $this->clientFirstName,
                'reminderNumber'  => $this->reminderNumber,
                'couponCode'      => $this->couponCode,
                'whatsappUrl'     => $this->whatsappUrl,
                'projectUrl'      => $this->projectUrl,
                'unsubscribeUrl'  => $this->unsubscribeUrl,
            ],
        );
    }

    /**
     * Dispatch email to queue.
     */
    public static function sendToUser(
        User $user,
        int $reminderNumber,
        string $couponCode,
        string $whatsappUrl,
        string $projectUrl,
        string $unsubscribeUrl,
    ): void {
        if (empty($user->email)) {
            return;
        }

        Mail::to($user->email)->queue(new self(
            $user,
            $reminderNumber,
            $couponCode,
            $whatsappUrl,
            $projectUrl,
            $unsubscribeUrl,
        ));
    }
}
