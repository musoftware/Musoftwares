{{-- Cookie banner for Blade pages. The React twin is resources/js/Components/Privacy/CookieBanner.tsx;
     keep both the same. It only asks about optional analytics and ad cookies: reading the site needs
     nothing else. It is not a modal, so the page stays usable and nothing is dimmed or locked. --}}
@php
    $cookieChoice = app(\App\Services\Privacy\ConsentAuditService::class)->resolveRequestConsentState(request())['cookiesTracking'];
    $trackingConfig = \App\Support\PrivacySettings::trackingConfig();
    $choiceButton = 'min-h-[44px] rounded-full border border-black/15 bg-white px-4 text-sm font-semibold text-[#1d1d1f] transition hover:bg-[#f5f5f7] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0071e3] disabled:opacity-50 cursor-pointer';
@endphp

<section id="cookie-banner" tabindex="-1" aria-labelledby="cookie-banner-title" aria-describedby="cookie-banner-description"
    @class([
        'fixed inset-x-3 bottom-3 z-[99990] mx-auto max-h-[85vh] max-w-[560px] overflow-y-auto rounded-2xl bg-white p-5 text-[#1d1d1f] shadow-[0_16px_48px_-12px_rgba(0,0,0,0.35)] ring-1 ring-black/10 focus:outline-none sm:bottom-6',
        'hidden' => $cookieChoice !== 'unset',
    ])>
    <div class="flex items-start justify-between gap-3">
        <h2 id="cookie-banner-title" class="text-[17px] font-semibold leading-snug">{{ __('privacy_compliance.gate_title') }}</h2>
        <button type="button" data-cookie-action="close" class="hidden -m-2 h-11 w-11 shrink-0 items-center justify-center rounded-full text-[#6e6e73] hover:bg-[#f5f5f7] hover:text-[#1d1d1f] cursor-pointer" aria-label="{{ __('privacy_compliance.gate_close_btn') }}">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <p id="cookie-banner-description" class="mt-2 text-sm leading-relaxed text-[#6e6e73]">{{ __('privacy_compliance.gate_description') }}</p>

    <div id="cookie-banner-panel" class="mt-4 hidden space-y-2">
        <div class="rounded-xl bg-[#f5f5f7] p-3.5">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold">{{ __('privacy_compliance.gate_necessary_title') }}</span>
                <span class="text-xs font-medium text-[#6e6e73]">{{ __('privacy_compliance.gate_always_on') }}</span>
            </div>
            <p class="mt-1 text-[13px] leading-relaxed text-[#6e6e73]">{{ __('privacy_compliance.gate_necessary_desc') }}</p>
        </div>
        <label for="cookie-banner-tracking" class="flex cursor-pointer items-start gap-3 rounded-xl bg-[#f5f5f7] p-3.5">
            <input id="cookie-banner-tracking" type="checkbox" class="mt-0.5 h-5 w-5 shrink-0 cursor-pointer rounded border-black/25 text-[#1d1d1f] focus:ring-[#0071e3]">
            <span>
                <span class="block text-sm font-semibold">{{ __('privacy_compliance.gate_tracking_title') }}</span>
                <span class="mt-1 block text-[13px] leading-relaxed text-[#6e6e73]">{{ __('privacy_compliance.gate_tracking_desc') }}</span>
            </span>
        </label>
        <button type="button" data-cookie-action="save" class="w-full {{ $choiceButton }}">{{ __('privacy_compliance.gate_save_btn') }}</button>
    </div>

    <p id="cookie-banner-error" role="status" class="mt-3 hidden text-sm text-[#d70015]">{{ __('privacy_compliance.gate_save_failed') }}</p>

    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
        <button type="button" data-cookie-action="necessary" class="{{ $choiceButton }}">{{ __('privacy_compliance.gate_reject_tracking_btn') }}</button>
        <button type="button" data-cookie-action="all" class="{{ $choiceButton }}">{{ __('privacy_compliance.gate_accept_all_btn') }}</button>
        <button type="button" data-cookie-action="customize" aria-expanded="false" aria-controls="cookie-banner-panel" class="{{ $choiceButton }}">{{ __('privacy_compliance.gate_customize_btn') }}</button>
    </div>

    <div class="mt-4 border-t border-black/[0.06] pt-3 text-xs text-[#6e6e73]">
        <div class="flex flex-wrap gap-x-4 gap-y-1">
            <a href="/cookie-policy" class="py-1 hover:text-[#1d1d1f] hover:underline">{{ __('privacy_compliance.read_cookies') }}</a>
            <a href="/privacy-policy" class="py-1 hover:text-[#1d1d1f] hover:underline">{{ __('privacy_compliance.read_privacy_policy') }}</a>
        </div>
        <p class="mt-1 text-[11px] text-[#86868b]">{{ __('privacy_compliance.gate_badge') }}</p>
    </div>
</section>

<script>
    (function () {
        var config = @json($trackingConfig);
        var choice = @json($cookieChoice);
        var saveUrl = @json(route('privacy.consent.gate'));

        if (choice === 'granted' && window.__musoftwareLoadTrackers) window.__musoftwareLoadTrackers(config);

        document.addEventListener('DOMContentLoaded', function () {
            var banner = document.getElementById('cookie-banner');
            if (!banner) return;
            var panel = document.getElementById('cookie-banner-panel');
            var trackingBox = document.getElementById('cookie-banner-tracking');
            var error = document.getElementById('cookie-banner-error');
            var closeButton = banner.querySelector('[data-cookie-action="close"]');
            var customizeButton = banner.querySelector('[data-cookie-action="customize"]');
            var opener = null;

            function setBusy(busy) {
                banner.querySelectorAll('button').forEach(function (button) { button.disabled = busy; });
            }

            function hide() {
                banner.classList.add('hidden');
                if (opener && document.body.contains(opener)) opener.focus();
                opener = null;
            }

            function applyChoice(allow) {
                var wasLoaded = Boolean(window.__musoftwareTrackersLoaded);
                choice = allow ? 'granted' : 'withdrawn';
                if (allow && window.__musoftwareLoadTrackers) window.__musoftwareLoadTrackers(config);
                if (!allow && window.__musoftwareRevokeTrackers) window.__musoftwareRevokeTrackers();
                // Scripts that already ran cannot be unloaded; a reload starts the page without them.
                if (!allow && wasLoaded) window.location.reload();
            }

            function save(allow) {
                setBusy(true);
                error.classList.add('hidden');
                var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                fetch(saveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ cookies_tracking: allow })
                }).then(function (response) {
                    if (!response.ok) throw new Error('cookie choice not saved');
                    applyChoice(allow);
                    hide();
                }).catch(function () {
                    error.classList.remove('hidden');
                }).finally(function () {
                    setBusy(false);
                });
            }

            function togglePanel() {
                var open = panel.classList.toggle('hidden') === false;
                customizeButton.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) trackingBox.focus();
            }

            var actions = {
                necessary: function () { save(false); },
                all: function () { save(true); },
                save: function () { save(trackingBox.checked); },
                customize: togglePanel,
                close: hide
            };

            banner.addEventListener('click', function (event) {
                var button = event.target.closest('[data-cookie-action]');
                if (button && actions[button.dataset.cookieAction]) actions[button.dataset.cookieAction]();
            });

            banner.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && opener) hide();
            });

            window.addEventListener('open-privacy-consent-gate', function () {
                opener = document.activeElement;
                trackingBox.checked = choice === 'granted';
                error.classList.add('hidden');
                closeButton.classList.remove('hidden');
                closeButton.classList.add('inline-flex');
                banner.classList.remove('hidden');
                banner.focus();
            });
        });
    })();
</script>
