<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\TranslationService;

class Service extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'service_category_id',
        'title',
        'description',
        'image',
        'price',
        'status',
        'currency',
        'slug',
        'tagline',
        'auto_reply',
        'fake_orders_count',
        'fake_last_delivery',
        'generate_serials',
        'allow_random_serial',
        'referral_commission_from',
        'referral_commission_percentage',
        'is_free',
        'require_share_to_download',
    ];

    /** Referral commission deducted from platform fee (default) */
    public const REFERRAL_FROM_FEE = 'fee';
    /** Referral commission deducted from seller's price */
    public const REFERRAL_FROM_SELLER_PRICE = 'seller_price';

    /**
     * Get the referral commission multiplier for this service (e.g. 1.10 for 10%).
     * When the service has a custom referral_commission_percentage, use it; otherwise use the referrer's default (10% first month, then 1%).
     */
    public function getReferralCommissionMultiplier($affiliateUser, $buyer): float
    {
        if ($this->hasCustomReferralPercentage()) {
            return 1 + ((float) $this->referral_commission_percentage / 100);
        }
        return $affiliateUser->getAffiliateCommissionPercentageForReferredUser($buyer);
    }

    /**
     * Whether this service uses a custom referral commission percentage (not platform default).
     */
    public function hasCustomReferralPercentage(): bool
    {
        $pct = $this->getAttribute('referral_commission_percentage');
        return $pct !== null && $pct !== '' && is_numeric($pct);
    }

    /**
     * Display percentage for referral card (custom % or null for "10% / 1%" default).
     */
    public function getReferralDisplayPercentage(): ?float
    {
        if (!$this->hasCustomReferralPercentage()) {
            return null;
        }
        return round((float) $this->referral_commission_percentage, 2);
    }

    /**
     * Whether referral commission for this service is deducted from the seller's price (vs platform fee).
     */
    public function isReferralCommissionFromSeller(): bool
    {
        return ($this->referral_commission_from ?? 'fee') === self::REFERRAL_FROM_SELLER_PRICE;
    }

    /**
     * Translation key for the referral card "earn" message (based on from seller vs fee and custom %).
     */
    public function getReferralCardEarnTranslationKey(): string
    {
        $fromSeller = $this->isReferralCommissionFromSeller();
        $pct = $this->getReferralDisplayPercentage();
        if ($fromSeller) {
            return $pct !== null
                ? 'services.referral_card.earn_guest_custom_from_seller'
                : 'services.referral_card.earn_guest_from_seller';
        }
        return $pct !== null
            ? 'services.referral_card.earn_guest_custom'
            : 'services.referral_card.earn_guest';
    }

    /**
     * Translation params for the referral card "earn" message.
     */
    public function getReferralCardEarnTranslationParams(): array
    {
        $pct = $this->getReferralDisplayPercentage();
        if ($pct !== null) {
            return ['pct' => $pct];
        }
        return ['first' => '10', 'then' => '1'];
    }

    /**
     * Translation key for the referral card footer note (from fee vs from seller).
     */
    public function getReferralCardFooterNoteKey(): string
    {
        return $this->isReferralCommissionFromSeller()
            ? 'services.referral_card.footer_note_from_seller'
            : 'services.referral_card.footer_note';
    }

    /**
     * Estimated referral commission amount for one unit sold (for display on service show).
     * When "From platform fee": commission is % of the website fee.
     * When "From my price": commission is % of the seller's price (so 30% of price for 30%).
     * Pass $displayCurrencyId to convert (e.g. guest or logged-in user currency).
     */
    public function getEstimatedReferralCommissionPerSale($displayCurrencyId = null): float
    {
        $fromSeller = $this->isReferralCommissionFromSeller();
        $multiplier = $this->hasCustomReferralPercentage()
            ? (1 + ((float) $this->referral_commission_percentage / 100))
            : 1.10;

        if ($fromSeller) {
            // Commission is percentage of seller's price (per unit)
            $commission = (float) $this->price * ($multiplier - 1);
        } else {
            $buyerPrice = round($this->price * 1.12);
            $fee = $buyerPrice - (float) $this->price;
            $commission = $fee * (1 - 1 / $multiplier);
        }

        if ($displayCurrencyId !== null) {
            $commission = CurrenciesExchange::RateToday($commission, $this->currency, $displayCurrencyId);
        }
        return round($commission, 2);
    }

    protected $casts = [
        'fake_last_delivery' => 'datetime',
        'completed_at' => 'datetime',
        'generate_serials' => 'boolean',
        'allow_random_serial' => 'boolean',
        'referral_commission_percentage' => 'float',
        'is_free' => 'boolean',
        'require_share_to_download' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($service) {
            // For new services (without ID), use the title directly
            // For existing services, try to get the English translation
            if ($service->exists && $service->id) {
                $title_en = $service->getTranslation('title', 'en') ?: $service->title;
            } else {
                $title_en = $service->title;
            }

            // Limit slug length to avoid DB errors, assuming slug column is 255 chars
            $baseSlug = \Illuminate\Support\Str::slug($title_en);

            if (empty($service->slug) || $service->isDirty('title')) {
                $slug = $baseSlug;
                $count = 1;
                // Check for uniqueness
                while (static::where('slug', $slug)->where('id', '!=', $service->id)->exists()) {
                    $slug = $baseSlug . '-' . $count++;
                }
                $service->slug = $slug;
            }
        });
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
            ->orWhere('slug', $value)
            ->firstOrFail();
    }

    public function product_images()
    {
        yield asset($this->image);
    }

    public function images()
    {
        return $this->hasMany(ServiceImage::class)->orderBy('sort_order');
    }

    public function serials()
    {
        return $this->hasMany(ServiceSerial::class);
    }

    public function discounts()
    {
        return $this->hasMany(ServiceDiscount::class);
    }

    public function faqs()
    {
        return $this->hasMany(ServiceFaq::class);
    }

    public function extras()
    {
        return $this->hasMany(ServiceExtra::class);
    }

    public function packages()
    {
        return $this->hasMany(ServicePackage::class);
    }

    /**
     * Check if this service has a package with the given name (e.g. 'basic', 'standard', 'premium').
     */
    public function hasPackage(string $name): bool
    {
        return $this->packages()->where('name', $name)->exists();
    }

    /**
     * Get the current (buyer-converted) price for a named package.
     * Falls back to the service's own current_price() when the package doesn't exist.
     */
    public function getPackagePrice(string $name): float
    {
        $pkg = $this->packages()->where('name', $name)->first();
        if (!$pkg) {
            return (float) $this->current_price();
        }

        $basePrice = (float) $pkg->price;

        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            return round(CurrenciesExchange::RateToday($basePrice * 1.12, $this->currency, $user->currency));
        }

        return round(CurrenciesExchange::RateToday($basePrice * 1.12, $this->currency, $this->getGuestCurrencyId()));
    }

    public function landingPage()
    {
        return $this->hasOne(ServiceLandingPage::class);
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function files()
    {
        return $this->hasMany(ServiceFile::class);
    }

    public function isFree(): bool
    {
        return (bool) $this->is_free;
    }

    /**
     * Get translations for this service
     */
    public function translations()
    {
        return $this->hasMany(ServiceTranslation::class);
    }

    /**
     * Get translation for a specific field and locale
     */
    public function getTranslation(string $field, string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        // If requesting the original locale, return original
        $originalLocale = app(TranslationService::class)->detectLanguage($this->attributes[$field] ?? '');
        if ($locale === $originalLocale) {
            return $this->attributes[$field] ?? null;
        }

        // Check database for existing translation
        $translation = $this->translations()
            ->where('locale', $locale)
            ->where('field', $field)
            ->first();

        if ($translation) {
            return $translation->value;
        }

        // Don't auto-translate for unsaved models (no ID yet)
        if (!$this->exists || !$this->id) {
            return $this->attributes[$field] ?? null;
        }

        // Auto-translate and cache
        $translationService = app(TranslationService::class);
        $translated = $translationService->translate(
            $this->attributes[$field] ?? '',
            $locale,
            $originalLocale
        );

        if ($translated) {
            ServiceTranslation::updateOrCreate(
                [
                    'service_id' => $this->id,
                    'locale' => $locale,
                    'field' => $field,
                ],
                [
                    'value' => $translated,
                ]
            );
        }

        return $translated ?? ($this->attributes[$field] ?? null);
    }

    /**
     * Get title attribute with automatic translation
     */
    public function getTitleAttribute($value)
    {
        if (!$value)
            return $value;

        $locale = app()->getLocale();
        $originalLocale = app(TranslationService::class)->detectLanguage($value);

        // If current locale matches original, return as-is
        if ($locale === $originalLocale) {
            return $value;
        }

        // Return translated version
        return $this->getTranslation('title', $locale) ?? $value;
    }

    /**
     * Get tagline attribute with automatic translation
     */
    public function getTaglineAttribute($value)
    {
        if (!$value)
            return $value;

        $locale = app()->getLocale();
        $originalLocale = app(TranslationService::class)->detectLanguage($value);

        // If current locale matches original, return as-is
        if ($locale === $originalLocale) {
            return $value;
        }

        // Return translated version
        return $this->getTranslation('tagline', $locale) ?? $value;
    }

    /**
     * Get description attribute with automatic translation
     */
    public function getDescriptionAttribute($value)
    {
        if (!$value)
            return $value;

        $locale = app()->getLocale();
        $originalLocale = app(TranslationService::class)->detectLanguage($value);

        // If current locale matches original, return as-is
        if ($locale === $originalLocale) {
            return $value;
        }

        // Return translated version
        return $this->getTranslation('description', $locale) ?? $value;
    }

    /**
     * Get auto_reply attribute with automatic translation
     */
    public function getAutoReplyAttribute($value)
    {
        if (!$value)
            return $value;

        $locale = app()->getLocale();
        $originalLocale = app(TranslationService::class)->detectLanguage($value);

        // If current locale matches original, return as-is
        if ($locale === $originalLocale) {
            return $value;
        }

        // Return translated version
        return $this->getTranslation('auto_reply', $locale) ?? $value;
    }

    public function description_str()
    {
        return preg_replace("/[\r|\n]{3,}/", "\n\n", $this->description);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function getOrdersInQueueAttribute()
    {
        return $this->orders()
            ->where('status', 'active')
            ->count();
    }

    public function getTotalOrdersAttribute()
    {
        $completedReal = $this->orders()->where('status', 'completed')->count();
        $fakeCount = $this->fake_orders_count ?? 0;
        $activeCount = $this->orders_in_queue;

        return $completedReal + $fakeCount + $activeCount;
    }

    public function getLatestDeliveryAttribute()
    {
        $lastDelivery = \App\Models\ServiceOrder::where('service_id', $this->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        $realDate = $lastDelivery ? $lastDelivery->completed_at : null;
        $fakeDate = $this->fake_last_delivery;

        if (!$realDate && !$fakeDate) {
            return null;
        }

        if (!$realDate) {
            return $fakeDate;
        }

        if (!$fakeDate) {
            return $realDate;
        }

        return $realDate->gt($fakeDate) ? $realDate : $fakeDate;
    }

    /**
     * Get the reviews for the service.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get only approved reviews for the service.
     */
    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    /**
     * Get the average rating for the service.
     */
    public function getAverageRatingAttribute()
    {
        return $this->approvedReviews()->avg('rating') ?? 0;
    }

    /**
     * Get the total number of reviews for the service.
     */
    public function getReviewsCountAttribute()
    {
        return $this->approvedReviews()->count();
    }

    /**
     * Get the average response time in hours for the service.
     * Calculates based on time between order creation and first seller message.
     */
    public function getAverageResponseTimeAttribute()
    {
        $orders = $this->orders()
            ->with([
                'activities' => function ($query) {
                    $query->where('user_id', $this->user_id)
                        ->orderBy('created_at', 'asc')
                        ->limit(1);
                }
            ])
            ->whereHas('activities', function ($query) {
                $query->where('user_id', $this->user_id);
            })
            ->get();

        if ($orders->isEmpty()) {
            return null;
        }

        $totalResponseTime = 0;
        $validOrders = 0;

        foreach ($orders as $order) {
            $firstSellerMessage = $order->activities->first();
            if ($firstSellerMessage) {
                $responseTime = $order->created_at->diffInMinutes($firstSellerMessage->created_at);
                $totalResponseTime += $responseTime;
                $validOrders++;
            }
        }

        if ($validOrders === 0) {
            return null;
        }

        // Return average in minutes
        return round($totalResponseTime / $validOrders);
    }

    /**
     * Get formatted response time string
     */
    public function getFormattedResponseTimeAttribute()
    {
        $avgMinutes = $this->average_response_time;

        if ($avgMinutes === null) {
            return __('N/A');
        }

        if ($avgMinutes < 60) {
            return round($avgMinutes) . ' ' . __('min');
        } elseif ($avgMinutes < 1440) { // Less than 24 hours
            $hours = round($avgMinutes / 60, 1);
            return $hours . ' ' . __('hr');
        } else {
            $days = round($avgMinutes / 1440, 1);
            return $days . ' ' . __('days');
        }
    }

    /**
     * Get the favorites for the service.
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Check if the service is favorited by a specific user.
     */
    public function isFavoritedBy($userId)
    {
        if (!$userId)
            return false;
        return $this->favorites()->where('user_id', $userId)->exists();
    }

    public function buyer_price($user = null)
    {
        $buyer_price = round($this->price * 1.12);
        if ($user != null && empty($user->ref_user_id)) {
            return $this->price + (($buyer_price - $this->price) * 0.95);
        }
        // When referrer has "add commission to total", buyer pays base price + commission on top
        if ($user != null && $user->ref_user_id && $user->ref_user && $user->ref_user->shouldAddCommissionToTotal()) {
            $commission = $user->ref_user->calculateCommissionAmount($buyer_price, $this->currency, $user);
            return round($buyer_price + $commission, 2);
        }
        return $buyer_price;
    }

    public function current_currency_str()
    {
        if (Auth::check()) {
            return CurrenciesExchange::getCurrencySymbol(Auth::user()->currency);
        } else {
            return CurrenciesExchange::getCurrencySymbol($this->getGuestCurrencyId());
        }
    }


    public function current_price()
    {
        if (Auth::check()) {
            return round(CurrenciesExchange::RateToday($this->buyer_price(Auth::user()), $this->currency, Auth::user()->currency));
        } else {
            return round(CurrenciesExchange::RateToday($this->buyer_price(Auth::user()), $this->currency, $this->getGuestCurrencyId()));
        }
    }

    public function current_price_str()
    {
        if (Auth::check()) {
            return \App\Helper\FinanceHelper::instance()->format_money($this->current_price(), Auth::user()->currency);
        } else {
            return \App\Helper\FinanceHelper::instance()->format_money($this->current_price(), $this->getGuestCurrencyId());
        }
    }

    protected static $guestCurrencyIdCache = null;

    public function getGuestCurrencyId()
    {
//        if (static::$guestCurrencyIdCache !== null) {
//            exit();
//            return static::$guestCurrencyIdCache;
//        }

        // Try to get currency from session first to avoid repeated lookups
//        if (session()->has('guest_currency_id')) {
//            static::$guestCurrencyIdCache = session('guest_currency_id');
//            return static::$guestCurrencyIdCache;
//        }

        try {
            $ip = request()->ip();
            $ipService = app(\App\Services\IpGeolocationService::class);
             $currencyCode = $ipService->getCurrencyCodeForIp($ip);

            // Default to USD (2) if no code found
            $currencyId = 2;

            if ($currencyCode) {
                // Find currency in DB
                // Assuming Currency model has 'currency' column storing the code (e.g. 'USD')
                $currency = \App\Models\Currency::where('currency', $currencyCode)->first();
                if ($currency) {
                    $currencyId = $currency->id;
                }
            }

            // Cache in session
            session(['guest_currency_id' => $currencyId]);
            static::$guestCurrencyIdCache = $currencyId;

            return $currencyId;

        } catch (\Exception $e) {
            // Fallback to safe default
            \Illuminate\Support\Facades\Log::error("Error detecting guest currency: " . $e->getMessage());
            return 2;
        }
    }

    public function make_order($user, $qty, $extras = [])
    {
        $current_price = $this->current_price();
        $extras_price = 0;
        $selected_extras = collect([]);

        // Calculate Extras Price
        if (!empty($extras)) {
            $selected_extras = $this->extras()->whereIn('id', $extras)->get();
            foreach ($selected_extras as $extra) {
                // Use ServiceExtra logic to calculate consistent price
                $extras_price += $extra->current_price();
            }
        }

        if ($user->user_balance < ($current_price * $qty) + $extras_price) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'balance' => [__('You don\'t have balance')],
            ]);
        }
        if ($user->id == $this->user->id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'user' => [__('You don\'t have to buy from yourself')],
            ]);
        }

        $order = new ServiceOrder();
        $order->user_id = $user->id;
        $order->service_id = $this->id;
        $order->qty = $qty;

        // Calculate seller amount: total for order = (base price * qty) + extras
        $seller_extras_total = 0;
        foreach ($selected_extras as $extra) {
            $seller_extras_total += $extra->price;
        }
        $seller_total_service_currency = ($this->price * $qty) + $seller_extras_total;
        $order->seller_service_amount = round(CurrenciesExchange::RateToday($seller_total_service_currency, $this->currency, $this->user->currency));

        $order->seller_currency = $this->user->currency;
        $order->buyer_service_amount = $current_price + ($extras_price / $qty); // unit price for getTotalPriceAttribute
        // Wait, buyer_service_amount usually stores unit price.
        // If query is > 1.
        // Let's store unit price. Extras are per order or per unit?
        // Usually extras are per service unit. If I buy 2 qty, do I get 2x extras?
        // The controller didn't multiply extras by qty.
        // " $serviceTotal = $service->current_price() * $qty; "
        // " extrasTotal += ... " (once)
        // So extras are applied ONCE per order regardless of Qty? Or per unit?
        // In Fiverr, extras can be per unit or not.
        // Based on my controller implementation: "$serviceTotal + $extrasTotal".
        // It seems treating extras as a flat fee on top of the order.
        // But `buyer_service_amount` column usually stores the UNIT price for the service.
        // If `service_orders` has logical distinction, strictly it might be better to store extra cost separately or averaged.
        // Let's assume for now we just add it to the total buyer amount.
        // But `buyer_service_amount` is multiplied by `qty` in `getTotalPriceAttribute`: "return ($this->buyer_service_amount ?? 0) * ($this->qty ?? 1);"
        // So if I add distinct extra price to unit price, it will be multiplied by qty.
        // If my controller logic sums ($price * qty) + $extra, then $extra is NOT multiplied by qty.
        // So I CANNOT just add extra to `buyer_service_amount` if `qty` > 1.
        // I should probably set `buyer_service_amount` to `( ($price * $qty) + $extra ) / $qty`.
        // That effectively distributes the extra cost across units.

        $total_buyer_cost = ($current_price * $qty) + $extras_price;
        $order->buyer_service_amount = $total_buyer_cost / $qty;

        $order->buyer_currency = $user->currency;
        $order->slug = strtoupper(uniqid("O"));
        if (!empty($user->ref_user_id)) {
            $affiliate_user = $user->ref_user;
            $multiplier = $this->getReferralCommissionMultiplier($affiliate_user, $user);

            if ($this->isReferralCommissionFromSeller()) {
                // Commission is percentage of seller's amount (price * qty + extras in service currency)
                $seller_amount_service_currency = ($this->price * $qty) + $seller_extras_total;
                $commission_in_service_currency = round($seller_amount_service_currency * ($multiplier - 1), 2);
            } else {
                // Commission is percentage of platform fee (total fee for order = fee per unit * qty)
                $fee_per_unit = $this->buyer_price($user) - $this->price;
                $total_fee = $fee_per_unit * $qty;
                $commission_in_service_currency = round($total_fee * (1 - 1 / $multiplier), 2);
            }

            $order->affiliate_user_id = $affiliate_user->id;
            $order->affiliate_service_amount = round(CurrenciesExchange::RateToday($commission_in_service_currency, $this->currency, $affiliate_user->currency));
            $order->affiliate_currency = $affiliate_user->currency;

            if ($this->isReferralCommissionFromSeller()) {
                $commission_in_seller_currency = CurrenciesExchange::RateToday($commission_in_service_currency, $this->currency, $this->user->currency);
                $order->seller_service_amount = max(0, round($order->seller_service_amount - $commission_in_seller_currency, 2));
            }
        }

        DB::transaction(function () use ($user, &$order, $selected_extras) {
            // Total amount for transaction
            $total_deduction = abs($order->qty * $order->buyer_service_amount);
            $transaction_id = $user->add_balance($total_deduction * -1, 'Order ' . $this->title, 'used');

            $order->buyer_transaction_id = $transaction_id;
            $order->save();

            // Save Extras to pivot table
            foreach ($selected_extras as $extra) {
                \App\Models\ServiceOrderExtra::create([
                    'service_order_id' => $order->id,
                    'service_extra_id' => $extra->id,
                    'title' => $extra->title,
                    'price' => $extra->price, // Base price in service currency
                    'duration_days' => $extra->duration_days
                ]);
            }
            // ...

            // Handle Serial Numbers
            if ($this->generate_serials) {
                $assignedSerials = [];
                for ($i = 0; $i < $order->qty; $i++) {
                    // Try to get an available serial
                    // We lock the row to prevent race conditions
                    $serial = $this->serials()
                        ->where('status', 'available')
                        ->lockForUpdate()
                        ->first();

                    if (!$serial) {
                        if ($this->allow_random_serial) {
                            // Generate a random serial
                            $serial = new ServiceSerial();
                            $serial->service_id = $this->id;
                            $serial->serial = strtoupper(\Illuminate\Support\Str::random(4)) . '-' .
                                strtoupper(\Illuminate\Support\Str::random(4)) . '-' .
                                strtoupper(\Illuminate\Support\Str::random(4)) . '-' .
                                strtoupper(\Illuminate\Support\Str::random(4));
                        } else {
                            // No serials available and random generation not allowed
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'stock' => [__('services.serials.not_enough_codes')]
                            ]);
                        }
                    }

                    // Assign serial to order
                    $serial->order_id = $order->id;
                    $serial->status = 'sold';
                    $serial->sold_at = now();

                    // Set expiration if not already set and service has validity period
                    if (!$serial->expires_at && $this->validity_days) {
                        $serial->expires_at = now()->addDays($this->validity_days);
                    }

                    $serial->save();
                    $assignedSerials[] = $serial;
                }
            }
        });

        if (!empty($this->auto_reply) || (isset($assignedSerials) && !empty($assignedSerials))) {
            $message = !empty($this->auto_reply) ? $this->auto_reply : "";

            if (isset($assignedSerials) && !empty($assignedSerials)) {
                if (!empty($message))
                    $message .= "\n\n";
                $message .= "**Your Serial Keys:**\n";
                foreach ($assignedSerials as $serial) {
                    $message .= "- " . $serial->serial;
                    if ($serial->expires_at) {
                        $message .= " (Expires: " . $serial->expires_at->format('Y-m-d') . ")";
                    }
                    $message .= "\n";
                }
            }

            if (!empty($message)) {
                $order->add_message($this->user, $message);
            }
        }

        return $order;
    }


    public static function services_get_famous($user_id = null)
    {
        return static::services_get($user_id);
    }

    public static function services_mine($user_id = null)
    {
        $q = Service::query();

        if (isset($user_id)) {
            $q->where('user_id', $user_id);
        } else {
            if (Auth::check()) {
                $q->where('user_id', Auth::id());
            }
        }

        $q->orderBy('id', 'desc');
        return $q;
    }

    public static function services_get($user_id = null)
    {
        $q = Service::query();

        if (isset($user_id)) {
            $q->where('user_id', $user_id);
        } else {
            if (!\Illuminate\Support\Facades\Auth::check() || !\Illuminate\Support\Facades\Auth::user()->hasRole("admin")) {
                $q->where('status', 'approved');
            }
        }

        if (!empty(\request('q'))) {
            $q->where(function ($v) {
                $v->whereRaw(
                    "MATCH(title) AGAINST(?)",
                    array(\request('q'))
                );
                $v->orWhereRaw(
                    "MATCH(description) AGAINST(?)",
                    array(\request('q'))
                );
                $v->orWhereRaw(
                    "title like   ?",
                    array('%' . \request('q') . '%')
                );
                $v->orWhereRaw(
                    "description like   ?",
                    array('%' . \request('q') . '%')
                );
            });

        }

        $q->orderBy('id', 'desc');
        return $q;
    }


}



