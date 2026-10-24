<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Mail\SoftwareOrderPaidAdminMail;
use App\Mail\SoftwareOrderReminderMail;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\SoftwareOrderService;
use Tests\TestCase;

class OrderRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private SoftwareProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::where('slug', 'pharmacy-system')->update(['is_active' => true]);
        $this->product = SoftwareProduct::where('slug', 'pharmacy-system')->firstOrFail();
    }

    private function order(array $attributes = [], int $hoursOld = 3): SoftwareOrder
    {
        $order = SoftwareOrder::create($attributes + [
            'software_product_id' => $this->product->id,
            'client_name' => 'Sara',
            'client_email' => 'sara@example.com',
            'client_whatsapp' => '+201000000000',
            'plan_key' => 'source',
            'plan_name' => 'Source code',
            'amount' => 5000,
            'currency' => 'EGP',
            'status' => SoftwareOrder::STATUS_PENDING,
        ]);
        $order->forceFill(['created_at' => now()->subHours($hoursOld)])->saveQuietly();

        return $order->fresh();
    }

    public function test_an_unpaid_order_gets_one_reminder_in_arabic_with_a_signed_resume_link(): void
    {
        Mail::fake();
        $order = $this->order();

        $this->assertSame(0, Artisan::call('software-orders:send-reminders'));

        Mail::assertSent(SoftwareOrderReminderMail::class, function (SoftwareOrderReminderMail $mail) use ($order) {
            $html = $mail->render();

            return $mail->hasTo('sara@example.com')
                && str_contains($mail->resumeUrl, '/software-orders/'.$order->uuid.'/resume')
                && str_contains($mail->resumeUrl, 'signature=')
                && str_contains($html, 'كمّل الدفع')
                && str_contains($html, 'ومش هنبعتلك تاني')
                && str_contains($html, 'خصم')
                && str_contains($html, '4,500.00 EGP');
        });
        $this->assertNotNull($order->fresh()->reminder_sent_at);

        Artisan::call('software-orders:send-reminders');
        Mail::assertSentCount(1);
    }

    public function test_the_reminder_follows_the_language_stored_on_the_order(): void
    {
        Mail::fake();
        $this->order(['locale' => 'en']);

        Artisan::call('software-orders:send-reminders');

        Mail::assertSent(SoftwareOrderReminderMail::class, fn (SoftwareOrderReminderMail $mail) => str_contains($mail->render(), 'Continue to payment'));
    }

    public function test_no_reminder_for_new_old_paid_inactive_or_later_paid_orders(): void
    {
        Mail::fake();
        $this->order(hoursOld: 1);
        $this->order(['client_email' => 'old@example.com'], hoursOld: 72);
        $this->order(['client_email' => 'paid@example.com', 'status' => SoftwareOrder::STATUS_PAID]);
        $this->order(['client_email' => 'retry@example.com']);
        $this->order(['client_email' => 'retry@example.com', 'status' => SoftwareOrder::STATUS_PAID], hoursOld: 1);
        $this->order(['client_email' => 'not-an-email']);

        Artisan::call('software-orders:send-reminders');
        Mail::assertNothingSent();

        $this->order(['client_email' => 'late@example.com']);
        SoftwareProduct::where('id', $this->product->id)->update(['is_active' => false]);
        Artisan::call('software-orders:send-reminders');
        Mail::assertNothingSent();
    }

    public function test_the_resume_link_needs_a_valid_signature_and_continues_the_payment(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $order = $this->order();
        $signed = URL::temporarySignedRoute('software.orders.resume', now()->addHour(), ['uuid' => $order->uuid]);

        $this->get('/software-orders/'.$order->uuid.'/resume')->assertForbidden();
        $this->get($signed)->assertRedirectContains('payments.kashier.io');
    }

    public function test_resuming_a_paid_order_goes_to_its_result_page(): void
    {
        $order = $this->order(['status' => SoftwareOrder::STATUS_PAID]);
        $signed = URL::temporarySignedRoute('software.orders.resume', now()->addHour(), ['uuid' => $order->uuid]);

        $this->get($signed)->assertRedirect(route('software.orders.success', $order->uuid));
    }

    public function test_the_owner_gets_a_mail_when_an_order_is_paid(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['email' => 'owner@example.com']);
        $admin->assignRole('admin');
        Mail::fake();
        $order = $this->order();

        app(SoftwareOrderService::class)->markPaid($order, 'TX-1');

        Mail::assertQueued(SoftwareOrderPaidAdminMail::class, fn (SoftwareOrderPaidAdminMail $mail) => $mail->hasTo('owner@example.com') && $mail->order->is($order));
    }

    public function test_new_orders_remember_the_language_they_were_made_in(): void
    {
        app()->setLocale('en');

        $order = app(SoftwareOrderService::class)->createPendingOrder($this->product, [
            'client_name' => 'Sara', 'client_email' => 'sara@example.com', 'client_whatsapp' => '+201000000000', 'plan_key' => 'source',
        ]);

        $this->assertSame('en', $order->fresh()->locale);
    }

    private function signedResume(SoftwareOrder $order): string
    {
        return URL::temporarySignedRoute('software.orders.resume', now()->addHour(), ['uuid' => $order->uuid]);
    }

    public function test_the_reminder_link_applies_a_real_discount_once(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        Mail::fake();
        $order = $this->order();
        Artisan::call('software-orders:send-reminders');
        $this->assertSame('5000.00', (string) $order->fresh()->amount);

        $this->get($this->signedResume($order))->assertRedirectContains('payments.kashier.io');
        $this->assertSame('4500.00', (string) $order->fresh()->amount);

        // opening the link again must not stack the discount
        $this->get($this->signedResume($order))->assertRedirectContains('payments.kashier.io');
        $this->assertSame('4500.00', (string) $order->fresh()->amount);
    }

    public function test_no_discount_without_a_reminder_after_the_window_or_when_switched_off(): void
    {
        $service = app(\Modules\SoftwareStore\Services\RecoveryDiscount::class);

        $this->assertNull($service->discountedAmount($this->order()), 'no reminder sent yet');

        $expired = $this->order(['reminder_sent_at' => now()->subHours(73)]);
        $this->assertNull($service->discountedAmount($expired), 'the 72 hours are over');

        $active = $this->order(['reminder_sent_at' => now()->subHour()]);
        $this->assertSame(4500.0, $service->discountedAmount($active));

        \App\Models\AdminSettings::SetValue(\Modules\SoftwareStore\Services\RecoveryDiscount::SETTING, 0);
        \Illuminate\Support\Facades\Cache::flush();
        $this->assertNull($service->discountedAmount($active), 'switched off with 0');
    }

    public function test_the_discount_percent_is_kept_between_zero_and_thirty(): void
    {
        $service = app(\Modules\SoftwareStore\Services\RecoveryDiscount::class);

        \App\Models\AdminSettings::SetValue(\Modules\SoftwareStore\Services\RecoveryDiscount::SETTING, 90);
        \Illuminate\Support\Facades\Cache::flush();
        $this->assertSame(30, $service->percent());
    }

    public function test_a_paid_order_never_gets_a_discount(): void
    {
        $order = $this->order(['status' => SoftwareOrder::STATUS_PAID, 'reminder_sent_at' => now()->subHour()]);

        $this->assertNull(app(\Modules\SoftwareStore\Services\RecoveryDiscount::class)->discountedAmount($order));
    }
}
