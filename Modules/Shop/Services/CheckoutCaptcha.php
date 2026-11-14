<?php

namespace Modules\Shop\Services;

/** The "I am not a robot" box on the checkout page. It is shown and checked only when the site has reCAPTCHA keys. */
class CheckoutCaptcha
{
    public const FIELD = 'g-recaptcha-response';

    public function siteKey(): ?string
    {
        $on = config('shop.orders.captcha') && config('services.recaptcha.secret_key');

        return $on ? (config('services.recaptcha.site_key') ?: null) : null;
    }
}
