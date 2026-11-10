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
    aria-describedby="pdpl-consent-gate-description"
    class="fixed inset-0 z-[99990] {{ $shouldAutoOpenGate ? 'flex' : 'hidden' }} items-end sm:items-center justify-center bg-black/40 backdrop-blur-[2px] p-0 sm:p-6 overflow-x-clip"
>
    <style>@keyframes pdpl-sheet-up{from{transform:translateY(24px);opacity:0}to{transform:translateY(0);opacity:1}}.pdpl-sheet-up{animation:pdpl-sheet-up .25s ease-out}@media (prefers-reduced-motion:reduce){.pdpl-sheet-up{animation:none}}</style>
    <div class="pdpl-sheet-up w-full sm:max-w-[440px] max-h-[90vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-white text-[#1d1d1f] shadow-[0_24px_64px_-16px_rgba(0,0,0,0.35)] ring-1 ring-black/5 p-6">
        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-[#f5f5f7] text-[#1d1d1f]">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/>
                <path d="M8.5 8.5v.01"/><path d="M16 15.5v.01"/><path d="M12 12v.01"/><path d="M11 17v.01"/><path d="M7 14v.01"/>
            </svg>
        </div>

        <h2 id="pdpl-consent-gate-title" class="text-[17px] font-semibold leading-snug">
            {{ __('privacy_compliance.gate_title') }}
        </h2>
        <p id="pdpl-consent-gate-description" class="mt-2 text-sm leading-relaxed text-[#6e6e73]">
            {{ __('privacy_compliance.gate_description') }}
        </p>

        <label id="pdpl-essential-label" for="pdpl-essential-checkbox" class="mt-5 flex cursor-pointer select-none items-start gap-3 rounded-xl bg-[#f5f5f7] p-3.5 ring-1 ring-transparent transition">
            <input
                id="pdpl-essential-checkbox"
                type="checkbox"
                aria-describedby="pdpl-essential-hint"
                class="mt-0.5 h-[18px] w-[18px] shrink-0 cursor-pointer rounded border-black/25 text-[#1d1d1f] focus:ring-[#1d1d1f]"
            />
            <span class="text-[13px] leading-relaxed text-[#1d1d1f]">
                {{ __('privacy_compliance.gate_essential_checkbox') }}
            </span>
        </label>
        <p id="pdpl-essential-hint" role="alert" class="mt-2 hidden text-xs text-[#d70015]">
            {{ __('privacy_compliance.gate_essential_required_hint') }}
        </p>

        <div class="mt-5 grid grid-cols-2 gap-2">
            <button
                type="button"
                id="pdpl-btn-reject-tracking"
                class="min-h-[44px] rounded-full bg-[#f5f5f7] px-4 text-sm font-semibold text-[#1d1d1f] transition hover:bg-[#e8e8ed] disabled:opacity-50 cursor-pointer"
            >
                {{ __('privacy_compliance.gate_reject_tracking_btn') }}
            </button>
            <button
                type="button"
                id="pdpl-btn-accept-all"
                class="min-h-[44px] rounded-full bg-[#1d1d1f] px-4 text-sm font-semibold text-white transition hover:bg-black disabled:opacity-50 cursor-pointer"
            >
                {{ __('privacy_compliance.gate_accept_all_btn') }}
            </button>
        </div>

        <form method="POST" action="{{ route('privacy.consent.decline') }}" class="mt-3">
            @csrf
            <button
                type="submit"
                class="w-full min-h-[36px] text-[13px] text-[#6e6e73] underline-offset-4 transition hover:text-[#1d1d1f] hover:underline cursor-pointer"
            >
                {{ __('privacy_compliance.gate_decline_essential_btn') }}
            </button>
        </form>

        <div class="mt-4 border-t border-black/[0.06] pt-4 text-xs text-[#6e6e73]">
            <div class="flex flex-wrap gap-x-4 gap-y-1">
                <a href="/privacy-policy" class="hover:text-[#1d1d1f] hover:underline">{{ __('privacy_compliance.read_privacy_policy') }}</a>
                <a href="/terms-of-service" class="hover:text-[#1d1d1f] hover:underline">{{ __('privacy_compliance.read_terms') }}</a>
                <a href="/cookie-policy" class="hover:text-[#1d1d1f] hover:underline">{{ __('privacy_compliance.read_cookies') }}</a>
            </div>
            <p class="mt-2 text-[11px] text-[#86868b]">{{ __('privacy_compliance.gate_badge') }}</p>
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
            var label = document.getElementById('pdpl-essential-label');
            var hint = document.getElementById('pdpl-essential-hint');
            var acceptBtn = document.getElementById('pdpl-btn-accept-all');
            var rejectBtn = document.getElementById('pdpl-btn-reject-tracking');
            if (!backdrop || !checkbox || !acceptBtn || !rejectBtn) return;

            function setHintVisible(visible) {
                if (hint) hint.classList.toggle('hidden', !visible);
                if (label) label.classList.toggle('ring-[#d70015]', visible);
                if (label) label.classList.toggle('ring-transparent', !visible);
            }

            function resetButtons() {
                acceptBtn.disabled = false;
                rejectBtn.disabled = false;
            }

            checkbox.checked = false;
            checkbox.addEventListener('change', function () {
                if (checkbox.checked) setHintVisible(false);
            });

            function submitChoice(enableTracking) {
                if (!checkbox.checked) {
                    setHintVisible(true);
                    checkbox.focus();
                    return;
                }
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
                    resetButtons();
                });
            }

            acceptBtn.addEventListener('click', function () { submitChoice(true); });
            rejectBtn.addEventListener('click', function () { submitChoice(false); });

            window.addEventListener('open-privacy-consent-gate', function () {
                backdrop.classList.remove('hidden');
                backdrop.classList.add('flex');
                checkbox.checked = false;
                setHintVisible(false);
                resetButtons();
            });
        });
    })();
</script>

