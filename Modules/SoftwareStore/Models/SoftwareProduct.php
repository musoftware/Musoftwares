<?php

namespace Modules\SoftwareStore\Models;

use App\Models\SerialSoftware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $slug
 * @property int|null $serial_software_id
 * @property string $name_en
 * @property string $name_ar
 * @property string|null $tagline_en
 * @property string|null $tagline_ar
 * @property string|float|int $price
 * @property string $currency
 * @property string|null $image_path
 * @property string|null $video_path
 * @property string|null $video_url
 * @property string|null $poster_path
 * @property array|null $plans
 */
class SoftwareProduct extends Model
{
    use SoftDeletes;

    /** Section order used when a program does not define its own. */
    public const DEFAULT_SECTIONS = ['overview', 'problems', 'stat', 'showcase', 'pillars', 'video', 'steps', 'metrics', 'before_after', 'gallery', 'details', 'faq'];

    /** Every section a landing page can show, including the price plans block. */
    public const ALL_SECTIONS = ['overview', 'problems', 'stat', 'showcase', 'pillars', 'video', 'steps', 'metrics', 'before_after', 'gallery', 'details', 'plans', 'faq'];

    /** Products that are services, not software. Slugs ending in -system are business systems; the rest are programs. */
    private const SERVICE_SLUGS = [
        'marketing-suite', 'whatsapp-official', 'company-website-design', 'landing-page-design', 'online-store-development',
        'custom-web-app-development', 'website-care-plans', 'social-media-management', 'paid-ads-management',
        'logo-and-brand-identity', 'ai-chatbot-whatsapp-automation',
    ];

    private const DEFAULT_THEME = ['accent' => '#4f46e5', 'ink' => '#111827'];

    protected $guarded = ['id'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'gallery' => 'array',
        'theme' => 'array',
        'plans' => 'array',
        'landing' => 'array',
    ];

    public function serialSoftware(): BelongsTo
    {
        return $this->belongsTo(SerialSoftware::class, 'serial_software_id');
    }

    /** The serial system is the source of truth for price when it sells this program. */
    private function pricedBySerialSystem(): bool
    {
        $software = $this->serialSoftware;

        return $software && $software->requires_payment && $software->price !== null;
    }

    /** @return array<int, array{key: string, name: string, price: float|int|string}> */
    public function planList(): array
    {
        $isArabic = app()->getLocale() === 'ar';
        $plans = array_filter($this->plans ?? [], fn ($plan) => isset($plan['key'], $plan['name'], $plan['price']));

        return array_values(array_map(function (array $plan) use ($isArabic) {
            $plan['name'] = ($isArabic ? ($plan['name_ar'] ?? null) : null) ?: $plan['name'];
            $plan['description'] = ($isArabic ? ($plan['description_ar'] ?? null) : null) ?: ($plan['description'] ?? null);
            $plan['features'] = ($isArabic ? ($plan['features_ar'] ?? null) : null) ?: ($plan['features'] ?? []);

            return $plan;
        }, $plans));
    }

    public function hasPlans(): bool
    {
        return $this->planList() !== [];
    }

    public function planByKey(?string $key): ?array
    {
        foreach ($this->planList() as $plan) {
            if ($plan['key'] === $key) {
                return $plan;
            }
        }

        return null;
    }

    /** Lowest plan price, used for "from X" on the list page. */
    public function startingPrice(): float
    {
        return $this->hasPlans() ? (float) min(array_column($this->planList(), 'price')) : $this->salePrice();
    }

    public function salePrice(?string $planKey = null): float
    {
        $plan = $this->planByKey($planKey);
        if ($plan) {
            return (float) $plan['price'];
        }

        return (float) ($this->pricedBySerialSystem() ? $this->serialSoftware->price : $this->price);
    }

    /** Whole numbers without decimals, anything else with two (46.80). */
    /** Which block of the /services page this product belongs to: programs, systems or services. */
    public function group(): string
    {
        return match (true) {
            in_array($this->slug, self::SERVICE_SLUGS, true) => 'services',
            str_ends_with($this->slug, '-system') => 'systems',
            default => 'programs',
        };
    }

    public function money(float $amount): string
    {
        return number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }

    /** What the price covers: "per year" unless the page copy says otherwise (for example "one payment"). */
    public function periodLabel(): string
    {
        return $this->landingCopy()['plans']['period'] ?? __('software.per_year');
    }

    public function saleCurrency(): string
    {
        return $this->pricedBySerialSystem() ? ($this->serialSoftware->currency ?: $this->currency) : $this->currency;
    }

    /** Length of one paid period in days. Null means the license never ends. */
    public function licenseDays(): ?int
    {
        $software = $this->serialSoftware;
        if ($software?->billing_days) {
            return (int) $software->billing_days;
        }

        return match ($software?->billing_cycle) {
            SerialSoftware::CYCLE_LIFETIME => null,
            SerialSoftware::CYCLE_MONTHLY => 30,
            default => 365,
        };
    }

    public function ownerWhatsapp(): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->serialSoftware?->whatsapp_number);

        return $number ?: null;
    }

    /** Link opened by the desktop program so the store knows which device to activate. */
    public function purchaseUrl(?string $deviceId = null): string
    {
        $url = route('website-services.show', $this->slug);

        return $deviceId ? $url.'?device='.rawurlencode($deviceId) : $url;
    }

    public function releases(): HasMany
    {
        return $this->hasMany(SoftwareRelease::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SoftwareOrder::class);
    }

    public function latestRelease(): ?SoftwareRelease
    {
        return $this->releases()
            ->where('is_published', true)
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->first();
    }

    public function localized(string $field, ?string $locale = null): ?string
    {
        $suffix = ($locale ?? app()->getLocale()) === 'ar' ? 'ar' : 'en';

        return $this->{$field.'_'.$suffix} ?: $this->{$field.'_en'};
    }

    /** Landing copy for the current language, falling back to English. */
    public function landingCopy(?string $locale = null): array
    {
        $lang = ($locale ?? app()->getLocale()) === 'ar' ? 'ar' : 'en';

        return ($this->landing[$lang] ?? null) ?: ($this->landing['en'] ?? []);
    }

    public function themeColors(): array
    {
        return array_merge(self::DEFAULT_THEME, $this->theme['colors'] ?? []);
    }

    public function sectionOrder(): array
    {
        return $this->theme['sections'] ?? self::DEFAULT_SECTIONS;
    }

    public function imageUrl(): ?string
    {
        return $this->mediaUrl($this->image_path);
    }

    public function posterUrl(): ?string
    {
        return $this->mediaUrl($this->poster_path) ?? $this->mediaUrl($this->galleryPaths()[0] ?? null);
    }

    public function videoFileUrl(): ?string
    {
        return $this->mediaUrl($this->video_path);
    }

    /** @return string[] */
    public function galleryPaths(): array
    {
        return array_values(array_filter($this->gallery ?? []));
    }

    /** @return string[] */
    public function galleryUrls(): array
    {
        return array_map(fn (string $path) => $this->mediaUrl($path), $this->galleryPaths());
    }

    /** Turns a YouTube link into an embeddable URL. Returns null for anything else. */
    public function embedUrl(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        $matched = preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/))([\w-]{11})~', $this->video_url, $m);

        return $matched ? 'https://www.youtube.com/embed/'.$m[1] : null;
    }

    public function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('uploads/'.ltrim($path, '/'));
    }
}
