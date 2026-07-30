<?php

namespace Modules\Listing\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Listing\Models\Listing;

class ListingAuthorRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $password;
    public Listing $listing;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $password, Listing $listing)
    {
        $this->password = $password;
        $this->listing = $listing;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = url('/listing/dashboard');
        $listingUrl = url('/listing/' . $this->listing->id);

        return (new MailMessage)
            ->subject('تم نشر إعلانك وتفعيل حسابك على منصة Musoftwares')
            ->greeting('مرحبًا بك في منصة Musoftwares!')
            ->line("يسعدنا إبلاغك بأنه تم نشر إعلانك الوظيفي: **\"{$this->listing->title}\"** بنجاح على منصتنا.")
            ->line('لقد قمنا بإنشاء حساب مجاني لك لتتمكن من إدارة وتعديل إعلانك في أي وقت.')
            ->line('تفاصيل تسجيل الدخول الخاصة بك هي:')
            ->line("**البريد الإلكتروني:** {$notifiable->email}")
            ->line("**كلمة المرور المؤقتة:** {$this->password}")
            ->action('تسجيل الدخول إلى لوحة التحكم', $loginUrl)
            ->line('ننصحك بتغيير كلمة المرور المؤقتة فور تسجيل الدخول لضمان أمان حسابك.')
            ->line("لمشاهدة إعلانك الوظيفي مباشرة على المنصة، يمكنك زيارة الرابط التالي:")
            ->line("[رابط الإعلان الوظيفي]({$listingUrl})")
            ->line('شكراً لاستخدامك منصتنا!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'message' => "تم إنشاء حسابك التلقائي ونشر إعلانك: {$this->listing->title}",
        ];
    }
}
