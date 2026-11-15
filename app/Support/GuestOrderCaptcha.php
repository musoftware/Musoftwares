<?php

namespace App\Support;

use App\Rules\Recaptcha;

/**
 * The "I am not a robot" box on the store order forms. Only visitors who are not signed in see it, and only when
 * the site has reCAPTCHA keys, so a bot can no longer fill the form with fake names and emails.
 */
class GuestOrderCaptcha
{
    public const FIELD = 'g-recaptcha-response';

    /** The key the page needs to show the box, or null when the box is not shown. */
    public function siteKey(): ?string
    {
        if (auth()->check() || ! config('services.recaptcha.guest_orders') || ! config('services.recaptcha.secret_key')) {
            return null;
        }

        return config('services.recaptcha.site_key') ?: null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return $this->siteKey() === null ? [] : [self::FIELD => ['required', new Recaptcha]];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [self::FIELD.'.required' => __('general.recaptcha_required')];
    }
}
