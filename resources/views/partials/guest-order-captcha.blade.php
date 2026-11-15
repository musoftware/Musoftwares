{{-- The "I am not a robot" box on a store order form; shows nothing for signed-in visitors or when the site has no keys. --}}
@if ($captchaKey = app(\App\Support\GuestOrderCaptcha::class)->siteKey())
    <div class="max-w-full overflow-x-clip">
        <div class="g-recaptcha" data-sitekey="{{ $captchaKey }}"></div>
        @error(\App\Support\GuestOrderCaptcha::FIELD)<p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
    </div>
    <script src="https://www.google.com/recaptcha/api.js?hl={{ app()->getLocale() }}" async defer></script>
@endif
