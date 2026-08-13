<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserSubscription;
use App\Services\PricingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiredReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly UserSubscription $subscription,
        public readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        // Set local context inside envelope for correct subject translation
        $locale = $this->user->lang ?? $this->user->locale ?? 'ar';
        app()->setLocale($locale);

        return new Envelope(
            subject: __('billing.subscription_expired_subject'),
        );
    }

    public function content(): Content
    {
        $locale = $this->user->lang ?? $this->user->locale ?? 'ar';
        app()->setLocale($locale);

        // Fetch human-readable name for the expired object (module or addon)
        $pricingService = app(PricingService::class);
        $serviceItems = $pricingService->getServiceItems();
        $item = collect($serviceItems)->firstWhere('id', $this->subscription->object);
        
        $moduleName = $item['name'] ?? ucfirst(str_replace('-', ' ', $this->subscription->object));

        // Format expires_at in Cairo timezone
        $expiresAtFormatted = $this->subscription->expires_at
            ? $this->subscription->expires_at->timezone('Africa/Cairo')->format('Y-m-d H:i')
            : now('Africa/Cairo')->format('Y-m-d H:i');

        return new Content(
            view: 'emails.subscription_expired_reminder',
            with: [
                'userName' => $this->user->name ?? 'Customer',
                'moduleName' => $moduleName,
                'expiresAt' => $expiresAtFormatted,
                'renewUrl' => url('/subscriptions/manage'),
                'isArabic' => $locale === 'ar',
            ]
        );
    }
}
