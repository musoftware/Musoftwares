@php
    $pdplGateState = app(\App\Services\Privacy\ConsentAuditService::class)->resolveRequestConsentState(request());
    $pdplTrackingConfig = \App\Support\PrivacySettings::trackingConfig();
    $isLegalExemptRoute = request()->is(
        'privacy-policy',
        'terms-of-service',
        'cookie-policy',
        'privacy',
        'terms',
        'cookies',
        'legal/*',
        'unsubscribe/*',
        'winback/unsubscribe/*'
    );
    $shouldAutoOpenGate = !($pdplGateState['hasEssentialConsent'] ?? false) && !$isLegalExemptRoute;
@endphp

<div
    id="pdpl-consent-gate-backdrop"
    role="dialog"
    aria-modal="true"
    aria-labelledby="pdpl-consent-gate-title"
    class="fixed inset-0 z-[99990] {{ $shouldAutoOpenGate ? 'flex' : 'hidden' }} items-center justify-center bg-black/75 backdrop-blur-sm p-3 sm:p-6 overflow-x-clip"
>
    <div class="w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-2xl border border-black/15 bg-white text-[#1d1d1f] shadow-2xl p-5 sm:p-7">
        <div class="flex items-start gap-3 mb-4">
            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-black/10 bg-[#f5f5f7] text-[#0071e3]">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-[#0066cc] mb-1">
                    {{ __('privacy_compliance.gate_badge') }}
                </p>
                <h2 id="pdpl-consent-gate-title" class="text-lg sm:text-xl font-bold leading-snug text-[#1d1d1f]">
                    {{ __('privacy_compliance.gate_title') }}
                </h2>
            </div>
        </div>

        <p class="text-xs sm:text-sm text-[#515154] leading-relaxed mb-4">
            {{ __('privacy_compliance.gate_description') }}
        </p>

        <div class="rounded-xl border border-black/10 bg-[#f5f5f7]/80 p-3.5 sm:p-4 mb-4">
            <label for="pdpl-essential-checkbox" class="flex items-start gap-3 cursor-pointer select-none">
                <input
                    id="pdpl-essential-checkbox"
                    type="checkbox"
                    class="mt-1 h-4 w-4 shrink-0 rounded border-black/30 text-[#0071e3] focus:ring-[#0071e3]"
                />
                <span class="text-xs sm:text-sm font-medium text-[#1d1d1f] leading-relaxed">
                    {{ __('privacy_compliance.gate_essential_checkbox') }}
                </span>
            </label>
            <p id="pdpl-essential-hint" class="mt-2 text-xs font-medium text-[#b45309]">
                {{ __('privacy_compliance.gate_essential_required_hint') }}
            </p>
        </div>

        <div class="rounded-xl border border-black/10 bg-white p-3.5 sm:p-4 mb-5">
            <h3 class="text-xs sm:text-sm font-semibold text-[#1d1d1f] mb-1">
                {{ __('privacy_compliance.gate_cookies_heading') }}
            </h3>
            <p class="text-xs text-[#515154] leading-relaxed">
                {{ __('privacy_compliance.gate_cookies_description') }}
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-3">
            <button
                type="button"
                id="pdpl-btn-accept-all"
                disabled
                class="min-h-[44px] w-full rounded-xl bg-[#0071e3] px-4 py-2.5 text-xs sm:text-sm font-semibold text-white transition hover:bg-[#0077ed] disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer"
            >
                {{ __('privacy_compliance.gate_accept_all_btn') }}
            </button>
            <button
                type="button"
                id="pdpl-btn-reject-tracking"
                disabled
                class="min-h-[44px] w-full rounded-xl border-2 border-[#1d1d1f] bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#1d1d1f] transition hover:bg-[#f5f5f7] disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer"
            >
                {{ __('privacy_compliance.gate_reject_tracking_btn') }}
            </button>
        </div>

        <form method="POST" action="{{ route('privacy.consent.decline') }}" class="mb-4">
            @csrf
            <button
                type="submit"
                class="min-h-[40px] w-full rounded-xl border border-[#dc2626]/30 bg-[#fef2f2] px-4 py-2 text-xs font-semibold text-[#b91c1c] transition hover:bg-[#fee2e2] cursor-pointer"
            >
                {{ __('privacy_compliance.gate_decline_essential_btn') }}
            </button>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-black/10 text-xs">
            <div class="flex flex-wrap items-center gap-4">
                <a href="/privacy-policy" class="font-semibold text-[#0066cc] hover:underline">
                    {{ __('privacy_compliance.read_privacy_policy') }}
                </a>
                <a href="/terms-of-service" class="font-semibold text-[#0066cc] hover:underline">
                    {{ __('privacy_compliance.read_terms') }}
                </a>
                <a href="/cookie-policy" class="font-semibold text-[#0066cc] hover:underline">
                    {{ __('privacy_compliance.read_cookies') }}
                </a>
            </div>
            <span class="text-[11px] text-[#86868b]">
                {{ $pdplGateState['policyVersion'] ?? 'v2.0-2026' }}
            </span>
        </div>
    </div>
</div>

<script>
    (function () {
        var trackingConfig = @json($pdplTrackingConfig);
        var initialState = @json($pdplGateState);
        var gateUrl = @json(route('privacy.consent.gate'));

        if (initialState.cookiesTracking === 'granted' && typeof window.__musoftwareLoadTrackers === 'function') {
            window.__musoftwareLoadTrackers(trackingConfig);
        }

        document.addEventListener('DOMContentLoaded', function () {
            var backdrop = document.getElementById('pdpl-consent-gate-backdrop');
            var checkbox = document.getElementById('pdpl-essential-checkbox');
            var hint = document.getElementById('pdpl-essential-hint');
            var acceptBtn = document.getElementById('pdpl-btn-accept-all');
            var rejectBtn = document.getElementById('pdpl-btn-reject-tracking');
            if (!backdrop || !checkbox || !acceptBtn || !rejectBtn) return;

            function syncButtons() {
                var checked = Boolean(checkbox.checked);
                acceptBtn.disabled = !checked;
                rejectBtn.disabled = !checked;
                if (hint) hint.style.display = checked ? 'none' : 'block';
            }

            checkbox.checked = false;
            syncButtons();
            checkbox.addEventListener('change', syncButtons);

            function submitChoice(enableTracking) {
                if (!checkbox.checked) return;
                acceptBtn.disabled = true;
                rejectBtn.disabled = true;
                var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

                fetch(gateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        essential_consent: true,
                        cookies_tracking: enableTracking
                    })
                }).then(function (res) {
                    if (!res.ok) throw new Error('Consent request failed');
                    if (enableTracking && typeof window.__musoftwareLoadTrackers === 'function') {
                        window.__musoftwareLoadTrackers(trackingConfig);
                    } else if (!enableTracking && typeof window.__musoftwareRevokeTrackers === 'function') {
                        window.__musoftwareRevokeTrackers();
                    }
                    backdrop.classList.add('hidden');
                    backdrop.classList.remove('flex');
                }).catch(function () {
                    syncButtons();
                });
            }

            acceptBtn.addEventListener('click', function () { submitChoice(true); });
            rejectBtn.addEventListener('click', function () { submitChoice(false); });

            window.addEventListener('open-privacy-consent-gate', function () {
                backdrop.classList.remove('hidden');
                backdrop.classList.add('flex');
                syncButtons();
            });
        });
    })();
</script>
