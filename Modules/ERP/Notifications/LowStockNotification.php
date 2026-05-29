<?php

namespace Modules\ERP\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\ERP\Models\Product;
use Illuminate\Notifications\Messages\DatabaseMessage;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $product;

    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // For ERP notifications, we typically use database for in-app notification
        // Mail can be added if the user has SMTP configured.
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('erp.low_stock_alert'))
            ->greeting(__('erp.hello', ['name' => $notifiable->name]))
            ->line(__('erp.low_stock_message', ['product' => $this->product->name, 'sku' => $this->product->sku]))
            ->action(__('erp.view_product'), url('/erp/inventory/products/' . $this->product->id))
            ->line(__('erp.thank_you_for_using_our_application'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'low_stock_alert',
            'product_id' => $this->product->id,
            'message' => __('erp.low_stock_message', ['product' => $this->product->name, 'sku' => $this->product->sku]),
            'url' => '/erp/inventory/products/' . $this->product->id,
        ];
    }
}
