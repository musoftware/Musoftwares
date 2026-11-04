<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Models\AdminAuditLog;
use App\Models\AdminSettings;
use App\Models\DataSubjectRequest;
use App\Models\Invoice;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Models\UserConsentLog;
use App\Models\UserPaymentMethod;
use App\Services\Privacy\ConsentAuditService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PdplPrivacyComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_user_consents_log_is_strictly_append_only(): void
    {
        $user = User::factory()->create();

        $entry = app(ConsentAuditService::class)->record(
            $user,
            ConsentType::PrivacyPolicy,
            ConsentAction::Granted,
            request(),
            'v2.0-2026'
        );

        $this->assertDatabaseHas('user_consents_log', [
            'id' => $entry->id,
            'user_id' => $user->id,
            'consent_type' => 'privacy_policy',
            'action' => 'granted',
            'policy_version' => 'v2.0-2026',
        ]);

        $this->expectException(LogicException::class);
        $entry->update(['action' => ConsentAction::Withdrawn]);
    }

    public function test_user_consents_log_forbids_deletion(): void
    {
        $entry = UserConsentLog::create([
            'user_id' => null,
            'consent_type' => ConsentType::CookiesTracking,
            'action' => ConsentAction::Withdrawn,
            'policy_version' => 'v2.0-2026',
            'ip_address' => '197.55.10.20',
            'user_agent' => 'PDPLTestAgent/1.0',
        ]);

        $this->expectException(LogicException::class);
        $entry->delete();
    }

    public function test_no_tracking_scripts_load_automatically_before_explicit_consent(): void
    {
        AdminSettings::SetValue('google_analytics_id', 'G-PDPLTEST99');
        AdminSettings::SetValue('google_tag_manager_id', 'GTM-PDPL99');
        AdminSettings::SetValue('meta_pixel_id', '9876543210123');
        AdminSettings::SetValue('custom_head_scripts', '<script src="https://tracker.example/pixel.js"></script>');

        $registerHtml = $this->get('/register')->assertOk()->getContent();
        $this->assertStringNotContainsString('googletagmanager.com/gtag/js?id=G-PDPLTEST99', (string) $registerHtml);
        $this->assertStringNotContainsString('connect.facebook.net/en_US/fbevents.js', (string) $registerHtml);
        $this->assertStringNotContainsString('<script src="https://tracker.example/pixel.js"></script>', (string) $registerHtml);

        $homeHtml = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('googletagmanager.com/gtag/js?id=G-PDPLTEST99', (string) $homeHtml);
        $this->assertStringNotContainsString('connect.facebook.net/en_US/fbevents.js', (string) $homeHtml);
    }

    public function test_consent_gate_and_decline_wall_block_and_unlock_site(): void
    {
        $declineResponse = $this->postJson('/privacy/consent/decline');
        $declineResponse->assertOk()->assertJson(['ok' => true, 'blocked' => true]);

        $this->assertDatabaseHas('user_consents_log', [
            'consent_type' => ConsentType::PrivacyPolicy->value,
            'action' => ConsentAction::Withdrawn->value,
        ]);

        // With declined cookie set, browsing regular pages is blocked (403), while legal pages stay accessible (200)
        $this->withCookie(ConsentAuditService::DECLINED_COOKIE_NAME, '1')
            ->get('/')
            ->assertStatus(403);

        $this->withCookie(ConsentAuditService::DECLINED_COOKIE_NAME, '1')
            ->get('/register')
            ->assertStatus(403);

        $this->withCookie(ConsentAuditService::DECLINED_COOKIE_NAME, '1')
            ->get('/privacy-policy')
            ->assertOk();

        // Accepting essential policy while rejecting tracking unlocks the site and logs both decisions
        $unlockResponse = $this->withCookie(ConsentAuditService::DECLINED_COOKIE_NAME, '1')
            ->postJson('/privacy/consent/gate', [
                'essential_consent' => true,
                'cookies_tracking' => false,
            ]);

        $unlockResponse->assertOk()->assertJson([
            'ok' => true,
            'hasEssentialConsent' => true,
            'cookiesTracking' => 'withdrawn',
        ]);
    }

    public function test_client_profile_consent_switches_portability_export_and_six_business_day_sla(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-05 10:00:00', 'Africa/Cairo')); // Thursday in Cairo

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        // 1. One-click grant & withdraw marketing email consent
        $this->actingAs($user)
            ->patch(route('profile.privacy.consents'), [
                'consent_type' => 'direct_marketing_email',
                'granted' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($user->fresh()->hasGrantedConsent(ConsentType::DirectMarketingEmail));

        $this->actingAs($user)
            ->patch(route('profile.privacy.consents'), [
                'consent_type' => 'direct_marketing_email',
                'granted' => false,
            ])
            ->assertRedirect();

        $this->assertFalse($user->fresh()->hasGrantedConsent(ConsentType::DirectMarketingEmail));

        // 2. JSON & PDF Data Portability Export
        $jsonExport = $this->actingAs($user)->get(route('profile.privacy.export', ['format' => 'json']));
        $jsonExport->assertOk();
        $decoded = json_decode((string) $jsonExport->getContent(), true);
        $this->assertSame($user->email, $decoded['account']['email'] ?? null);
        $this->assertNotEmpty($decoded['consents_audit_log'] ?? []);

        $pdfExport = $this->actingAs($user)->get(route('profile.privacy.export', ['format' => 'pdf']));
        $pdfExport->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdfExport->headers->get('Content-Type'));

        // 3. Submit Right-to-be-Forgotten DSR -> skips Friday/Saturday weekend -> 6 Egyptian business days
        $this->actingAs($user)
            ->post(route('profile.privacy.requests.store'), [
                'request_type' => 'delete_account',
                'details' => 'Please erase my personal profile.',
            ])
            ->assertRedirect();

        $dsr = DataSubjectRequest::where('user_id', $user->id)
            ->where('request_type', DataSubjectRequestType::DeleteAccount->value)
            ->firstOrFail();

        $this->assertSame(DataSubjectRequestStatus::Pending, $dsr->status);
        // Thu Nov 5 -> Sun(1) Nov 8, Mon(2) Nov 9, Tue(3) Nov 10, Wed(4) Nov 11, Thu(5) Nov 12, Sun(6) Nov 15
        $this->assertSame('2026-11-15', $dsr->due_at->timezone('Africa/Cairo')->format('Y-m-d'));

        Carbon::setTestNow();
    }

    public function test_admin_dsr_resolution_enforces_reason_and_anonymizes_account_preserving_invoices(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
        $admin->assignRole('admin');

        $client = User::factory()->create([
            'name' => 'Ahmed Sensitive',
            'email' => 'ahmed.sensitive@example.com',
            'mobile_1' => '+201099887766',
            'email_verified_at' => now(),
        ]);

        $invoice = Invoice::create([
            'user_id' => $client->id,
            'status' => 'paid',
            'paid' => 500,
            'unpaid' => 0,
        ]);

        $dsr = DataSubjectRequest::create([
            'user_id' => $client->id,
            'request_type' => DataSubjectRequestType::DeleteAccount,
            'status' => DataSubjectRequestStatus::Pending,
            'requested_at' => now(),
            'due_at' => DataSubjectRequest::calculateDueAt(now()),
        ]);

        // Rejecting without a reason must fail
        $this->actingAs($admin)
            ->from('/admin/privacy-requests')
            ->patch(route('admin.privacy-requests.update', $dsr), [
                'status' => 'rejected_with_reason',
                'admin_notes' => '',
            ])
            ->assertRedirect('/admin/privacy-requests')
            ->assertSessionHasErrors('admin_notes');

        // Completing erasure scrubs PII, soft-deletes user, preserves invoice, and sets completed_at
        $this->actingAs($admin)
            ->patch(route('admin.privacy-requests.update', $dsr), [
                'status' => 'completed',
                'admin_notes' => 'PII anonymized; paid invoice #'.$invoice->id.' kept for 5-year tax archive.',
            ])
            ->assertRedirect();

        $dsr->refresh();
        $this->assertSame(DataSubjectRequestStatus::Completed, $dsr->status);
        $this->assertNotNull($dsr->completed_at);

        $trashedClient = User::withTrashed()->findOrFail($client->id);
        $this->assertTrue($trashedClient->trashed());
        $this->assertNotSame('Ahmed Sensitive', $trashedClient->name);
        $this->assertNotSame('ahmed.sensitive@example.com', $trashedClient->email);
        $this->assertNull($trashedClient->mobile_1);

        // Invoice number remains intact in DB
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'user_id' => $client->id,
            'paid' => 500,
        ]);
    }

    public function test_data_retention_cron_and_encrypted_backup_commands(): void
    {
        Storage::fake('local');

        $unverified = User::factory()->create([
            'email' => 'stale-unverified@example.com',
            'email_verified_at' => null,
            'last_login' => null,
        ]);
        DB::table('users')->where('id', $unverified->id)->update([
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        $abandoned = User::factory()->create([
            'email' => 'abandoned-25m@example.com',
            'email_verified_at' => now()->subMonths(26),
        ]);
        $paidInvoice = Invoice::create([
            'user_id' => $abandoned->id,
            'status' => 'paid',
            'paid' => 250,
            'unpaid' => 0,
        ]);
        DB::table('users')->where('id', $abandoned->id)->update([
            'created_at' => now()->subMonths(26),
            'updated_at' => now()->subMonths(26),
            'last_activity_at' => now()->subMonths(26),
            'last_login' => now()->subMonths(26),
        ]);

        DB::table('password_resets')->insert([
            'email' => 'expired-otp@example.com',
            'token' => 'expired-token-hash',
            'created_at' => now()->subHours(30),
        ]);

        $activeUser = User::factory()->create([
            'email' => 'active-protected@example.com',
            'email_verified_at' => now(),
            'last_activity_at' => now()->subDays(3),
        ]);

        $this->assertSame(0, $this->artisan('privacy:enforce-retention'));

        $this->assertDatabaseMissing('password_resets', ['email' => 'expired-otp@example.com']);
        $this->assertTrue(User::withTrashed()->findOrFail($unverified->id)->trashed());
        $this->assertTrue(User::withTrashed()->findOrFail($abandoned->id)->trashed());
        $this->assertFalse(User::withTrashed()->findOrFail($activeUser->id)->trashed());
        $this->assertDatabaseHas('invoices', ['id' => $paidInvoice->id]);

        // Encrypted Backup Command
        $this->assertSame(0, $this->artisan('privacy:encrypted-backup', ['--disk' => 'local']));

        $files = Storage::disk('local')->files('encrypted-backups');
        $this->assertCount(1, $files);

        $rawCiphertext = Storage::disk('local')->get($files[0]);
        $this->assertStringNotContainsString('active-protected@example.com', (string) $rawCiphertext);

        $decompressed = gzdecode(base64_decode(Crypt::decryptString((string) $rawCiphertext)));
        $this->assertStringContainsString('active-protected@example.com', (string) $decompressed);
    }

    public function test_signed_marketing_unsubscribe_works_immediately_without_login(): void
    {
        $user = User::factory()->create(['winback_unsubscribed_at' => null]);
        $service = app(ConsentAuditService::class);
        $service->record($user, ConsentType::DirectMarketingEmail, ConsentAction::Granted);
        $service->record($user, ConsentType::DirectMarketingSms, ConsentAction::Granted);

        $this->assertTrue($service->canSendMarketingEmail($user));

        $signedUrl = $service->generateUnsubscribeUrl($user);
        $this->get($signedUrl)->assertOk();

        $user->refresh();
        $this->assertFalse($service->canSendMarketingEmail($user));
        $this->assertFalse($service->canSendMarketingSms($user));
        $this->assertNotNull($user->winback_unsubscribed_at);
    }

    public function test_sensitive_fields_are_encrypted_at_rest_and_admin_views_exports_are_audited(): void
    {
        $user = User::factory()->create();

        $paymentMethod = UserPaymentMethod::create([
            'user_id' => $user->id,
            'type' => 'bank',
            'id_number' => '29901011234567',
            'bank_number' => 'EG380019000500000000263180002',
        ]);

        $payoutMethod = PayoutMethod::create([
            'user_id' => $user->id,
            'type' => 'instapay',
            'details' => ['ipa' => 'client@instapay', 'national_id' => '29901011234567'],
            'status' => 'active',
        ]);

        $rawPayment = DB::table('user_payment_methods')->where('id', $paymentMethod->id)->first();
        $this->assertNotSame('29901011234567', $rawPayment->id_number);
        $this->assertNotSame('EG380019000500000000263180002', $rawPayment->bank_number);
        $this->assertSame('29901011234567', $paymentMethod->fresh()->id_number);
        $this->assertSame('EG380019000500000000263180002', $paymentMethod->fresh()->bank_number);

        $rawPayout = DB::table('payout_methods')->where('id', $payoutMethod->id)->first();
        $this->assertStringNotContainsString('client@instapay', (string) $rawPayout->details);
        $this->assertSame('client@instapay', $payoutMethod->fresh()->details['ipa'] ?? null);

        // Verify Admin Data Access & CSV Export Audit Logging
        $admin = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/privacy-requests')->assertOk();
        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'admin.customer_data.viewed',
        ]);

        Route::middleware(['web', 'auth', 'admin'])->get('/admin/_test-pdpl-csv-export', static fn () => response("id,email\n1,test@example.com", 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers.csv"',
        ]));

        $this->actingAs($admin)->get('/admin/_test-pdpl-csv-export')->assertOk();
        $this->assertTrue(
            AdminAuditLog::where('actor_user_id', $admin->id)
                ->where('action', 'admin.data.exported')
                ->exists()
        );
    }

    public function test_arabic_privacy_policy_renders_all_five_mandatory_pdpl_disclosures(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/privacy-policy?lang=ar');
        $response->assertOk();

        $html = (string) $response->getContent();
        $this->assertStringContainsString('هوية المتحكم في البيانات الشخصية', $html);
        $this->assertStringContainsString('جدول تفصيلي للبيانات المجمّعة والغرض ومدة الاحتفاظ', $html);
        $this->assertStringContainsString('الجهات المشاركة ومعالجو البيانات والاستضافة الخارجية', $html);
        $this->assertStringContainsString('خارج جمهورية مصر العربية', $html);
        $this->assertStringContainsString('6 أيام عمل', $html);
        $this->assertStringContainsString('مسؤول حماية البيانات الشخصية', $html);
        $this->assertStringContainsString('admin@musoftwares.com', $html);
    }
}
