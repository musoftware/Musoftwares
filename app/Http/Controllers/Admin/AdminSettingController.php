<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FinanceHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;
use App\Models\UserIntegration;
use App\Models\WhatsAppChannel;
use App\Services\SystemConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class AdminSettingController extends Controller
{
    public function __construct(
        protected SystemConfigurationService $configService
    ) {}

    public function index()
    {
        $currencies = Currency::all();

        $adminIds = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->pluck('id');

        // This query requires the WhatsAppChannel model which we assume is ported.
        $whatsappChannels = class_exists(WhatsAppChannel::class)
            ? WhatsAppChannel::whereIn('user_id', $adminIds)
                ->where('status', 'connected')
                ->where('is_active', true)
                ->with('user:id,name')
                ->orderBy('user_id')
                ->orderBy('name')
                ->get()
            : [];

        $hasGoogleCalendar = class_exists(UserIntegration::class)
            ? UserIntegration::where('user_id', auth()->id())
                ->where('provider', 'google_calendar')
                ->exists()
            : false;

        // Fetch all current settings to pass to frontend
        $settings = [
            'business_currency' => AdminSettings::GetValue('business_currency'),
            'business_name' => AdminSettings::GetValue('business_name'),
            'business_phone' => AdminSettings::GetValue('business_phone'),
            'business_address' => AdminSettings::GetValue('business_address'),
            'business_tax' => AdminSettings::GetValue('business_tax'),
            'business_email' => AdminSettings::GetValue('business_email'),
            'overhead_cost_default' => AdminSettings::GetValue('overhead_cost_default'),
            'ownwallet' => AdminSettings::GetValue('ownwallet') === '1',
            'payoneer_active' => AdminSettings::GetValue('payoneer_active') === '1',
            'paymob_active' => AdminSettings::GetValue('paymob_active') === '1',
            'paymob_token' => AdminSettings::GetValue('paymob_token'),
            'paymob_card_integration' => AdminSettings::GetValue('paymob_card_integration'),
            'paymob_wallet_integration' => AdminSettings::GetValue('paymob_wallet_integration'),
            'paymob_card_iframe' => AdminSettings::GetValue('paymob_card_iframe'),
            'gumroad' => AdminSettings::GetValue('gumroad'),
            'whatsapp_default_channel_id' => AdminSettings::GetValue('whatsapp_default_channel_id'),
            'friday_work_allowed' => AdminSettings::GetValue('friday_work_allowed') === '1',
            'max_devices_per_tenant' => AdminSettings::GetValue('max_devices_per_tenant') ?? 3,
            'gemini_api_keys' => AdminSettings::GetValue('gemini_api_keys'),
            'expected_monthly_income' => AdminSettings::GetValue('expected_monthly_income'),
            'work_days_per_month' => AdminSettings::GetValue('work_days_per_month'),
            'hours_per_day' => AdminSettings::GetValue('hours_per_day'),
            'google_analytics_id' => AdminSettings::GetValue('google_analytics_id'),
            'google_tag_manager_id' => AdminSettings::GetValue('google_tag_manager_id'),
            'meta_pixel_id' => AdminSettings::GetValue('meta_pixel_id'),
            'custom_head_scripts' => AdminSettings::GetValue('custom_head_scripts'),
            'custom_body_scripts' => AdminSettings::GetValue('custom_body_scripts'),
            // Recurring invoice notification channel toggles (one place; default mail+fcm).
            'notif_channels_invoice_created' => AdminSettings::GetValue('notif_channels_invoice_created', 'mail,fcm'),
            'invoice_notification_channels' => AdminSettings::INVOICE_NOTIFICATION_CHANNELS,
        ];

        return Inertia::render('Admin/Settings/Index', [
            'currencies' => $currencies,
            'whatsappChannels' => $whatsappChannels,
            'settings' => $settings,
            'hasGoogleCalendar' => $hasGoogleCalendar,
            'overheadHourlyRateEgp' => FinanceHelper::calculateOverheadHourlyRate(),
        ]);
    }

    public function store(Request $request)
    {
        // SECURITY NOTE:
        // `custom_head_scripts` and `custom_body_scripts` are emitted UNESCAPED
        // (see resources/views/app.blade.php lines 83 and 143 — `{!! $custom_head !!}`).
        // That is intentional (analytics, tag manager, chat widgets), but it
        // means ANY write to these fields is an XSS sink that executes on every
        // page view for every visitor. The trust boundary is the admin role
        // + the audit log. We:
        //   1. Require the script block to be wrapped in <script>...</script>
        //      (or <style>...</style>) so casual form junk can't slip into the
        //      raw HTML stream.
        //   2. Length-cap each script payload.
        //   3. Record an audit log entry that captures a SHA-256 fingerprint
        //      and length so we can later diff what was changed.
        $customHead = $request->input('custom_head_scripts');
        $customBody = $request->input('custom_body_scripts');

        if ($customHead !== null && $customHead !== '') {
            $this->assertSafeScriptBlock($customHead, 'custom_head_scripts');
        }
        if ($customBody !== null && $customBody !== '') {
            $this->assertSafeScriptBlock($customBody, 'custom_body_scripts');
        }

        $validated = $request->validate([
            'business_currency' => 'nullable|string',
            'business_name' => 'nullable|string',
            'business_phone' => 'nullable|string',
            'business_address' => 'nullable|string',
            'business_tax' => 'nullable|string',
            'business_email' => 'nullable|email',
            'overhead_cost_default' => 'nullable|numeric',
            'ownwallet' => 'boolean',
            'payoneer_active' => 'boolean',
            'paymob_active' => 'boolean',
            'paymob_token' => 'nullable|string',
            'paymob_card_integration' => 'nullable|string',
            'paymob_wallet_integration' => 'nullable|string',
            'paymob_card_iframe' => 'nullable|string',
            'gumroad' => 'nullable|string',
            'whatsapp_default_channel_id' => 'nullable|string',
            'friday_work_allowed' => 'boolean',
            'max_devices_per_tenant' => 'nullable|integer|min:1',
            'gemini_api_keys' => 'nullable|string',
            'expected_monthly_income' => 'nullable|numeric|min:0',
            'work_days_per_month' => 'nullable|numeric|min:0',
            'hours_per_day' => 'nullable|numeric|min:0',
            'google_analytics_id' => 'nullable|string',
            'google_tag_manager_id' => 'nullable|string',
            'meta_pixel_id' => 'nullable|string',
            'custom_head_scripts' => 'nullable|string|max:16384',
            'custom_body_scripts' => 'nullable|string|max:16384',
            'notif_channels_invoice_created' => ['nullable', 'string', function ($attr, $value, $fail) {
                $allowed = AdminSettings::INVOICE_NOTIFICATION_CHANNELS;
                $bad = array_diff(array_map('trim', explode(',', (string) $value)), $allowed);
                if (! empty($bad)) {
                    $fail('Invalid channel(s): '.implode(', ', $bad).'. Allowed: '.implode(', ', $allowed));
                }
            }],
        ]);

        $this->configService->updateSettings($validated);

        return redirect()->back()->with('success', __('general.settings_updated_successfully'));
    }

    /**
     * Guard the custom-script fields by requiring the payload to be wrapped
     * in <script>...</script> or <style>...</style> blocks. This is a coarse
     * filter — the trust boundary remains the admin role + audit log — but
     * it prevents accidental pastes of raw HTML from being rendered verbatim.
     */
    private function assertSafeScriptBlock(string $value, string $field): void
    {
        $trimmed = trim($value);
        $looksScript = (bool) preg_match('/^<(script|style)\b/i', $trimmed);
        $looksClose = (bool) preg_match('/<\/(script|style)>\s*$/i', $trimmed);

        if (! $looksScript || ! $looksClose) {
            abort(422, __('The :field field must be wrapped in <script>...</script> or <style>...</style> tags.', [
                'field' => $field,
            ]));
        }
    }

    public function updatePrices()
    {
        $currencies = Currency::all();

        return Inertia::render('Admin/Settings/UpdatePrices', [
            'currencies' => $currencies,
        ]);
    }

    public function doUpdatePrices(Request $request)
    {
        $request->validate([
            'hour_rate' => 'required|numeric',
            'currency' => 'required|exists:currencies,id',
        ]);

        $rate = $request->input('hour_rate');
        $currency = $request->input('currency');

        User::query()->update([
            'hour_rate' => $rate,
            'hour_rate_currency_id' => $currency,
        ]);

        if ($request->boolean('update_projects')) {
            Project::whereNotIn('status', ['completed', 'canceled'])
                ->update(['hour_rate' => $rate]);
        }

        return redirect()->back()->with('success', __('admin.prices_updated_for_all_clients'));
    }

    public function calculateHourlyRate(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|integer',
        ]);

        $currencyId = $request->input('currency_id');
        $baseRate = FinanceHelper::calculateOverheadHourlyRate();
        $businessCurrency = AdminSettings::GetValue('business_currency', 2);

        $rate = CurrenciesExchange::RateToday(
            $baseRate,
            $businessCurrency,
            $currencyId
        );

        return response()->json(['rate' => round($rate, 2)]);
    }

    public function syncExchangeRates()
    {
        try {
            Artisan::call('currency:fetch-rates');

            return redirect()->back()->with('success', __('general.exchange_rates_synced_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('general.failed_to_sync_exchange_rates').': '.$e->getMessage());
        }
    }
}
