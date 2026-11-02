<?php

namespace Modules\SoftwareStore\Services;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Earning;
use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareLicense;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Models\SoftwareRelease;
use Modules\SoftwareStore\Models\WhiteLabelPartner;
use RuntimeException;

/**
 * The money rules of white label:
 * - the partner pays a monthly subscription from their wallet;
 * - they sell at our price or higher, never lower;
 * - on every paid order they earn 30% of our price plus everything they added on top.
 */
class WhiteLabelService
{
    public const MONTHLY_PRICE = 1000;

    public const CURRENCY = 'EGP';

    public const SUBSCRIPTION_DAYS = 30;

    public const COMMISSION_PERCENT = 30;

    /** Days a share waits before it moves to the wallet, so a refunded payment can still be taken back. */
    public const CLEARING_DAYS = 14;

    /** Hours a store download waits for a new device's first start from the same address. */
    public const DOWNLOAD_MATCH_HOURS = 48;

    /** The programs a partner can sell: active, priced, and not a business system or a service. */
    public function products(): Collection
    {
        return SoftwareProduct::where('is_active', true)
            ->with('serialSoftware')
            ->orderBy('id')
            ->get()
            ->filter(fn (SoftwareProduct $product) => $product->group() === 'programs' && $product->startingPrice() > 0)
            ->values();
    }

    public static function priceKey(SoftwareProduct $product, ?string $planKey): string
    {
        return $planKey ? $product->id.':'.$planKey : (string) $product->id;
    }

    /** The price the partner's customer pays. A price under ours (or none) falls back to ours. */
    public function partnerPrice(WhiteLabelPartner $partner, SoftwareProduct $product, ?string $planKey = null): float
    {
        $base = $product->salePrice($planKey);
        $set = (float) ($partner->prices[self::priceKey($product, $planKey)] ?? 0);

        return round(max($base, $set), 2);
    }

    /** 30% of our price plus the partner's markup. */
    public function commission(float $base, float $partnerPrice): float
    {
        return round($base * self::COMMISSION_PERCENT / 100 + max(0, $partnerPrice - $base), 2);
    }

    /**
     * Charges one month to the partner's wallet and adds 30 days on top of the current end date
     * (or today when it has ended). Throws DomainException when the wallet does not hold enough.
     */
    public function subscribe(WhiteLabelPartner $partner): void
    {
        DB::transaction(function () use ($partner) {
            /** @var User $user */
            $user = User::whereKey($partner->user_id)->lockForUpdate()->firstOrFail();
            $currencyId = Currency::where('currency', self::CURRENCY)->value('id');
            if (! $currencyId) {
                throw new RuntimeException('Currency '.self::CURRENCY.' is missing');
            }

            $price = (float) CurrenciesExchange::RateToday(self::MONTHLY_PRICE, $currencyId, $user->currency_id);
            if ((float) $user->user_balance < $price) {
                throw new DomainException(__('white_label.not_enough_balance'));
            }

            $user->add_balance(-1 * self::MONTHLY_PRICE, 'White label subscription', 'used', $currencyId);

            $from = $partner->isActive() ? $partner->subscription_expires_at->copy() : now();
            $partner->update(['subscription_expires_at' => $from->addDays(self::SUBSCRIPTION_DAYS)]);
        });
    }

    /** Records the partner's share of a paid order once. A failure is logged and never undoes the payment. */
    public function recordCommission(SoftwareOrder $order): void
    {
        $partner = $order->partner;
        $amount = (float) $order->partner_commission;
        if (! $partner || $amount <= 0) {
            return;
        }

        try {
            $currencyId = Currency::where('currency', $order->currency)->value('id');
            Earning::create([
                'user_id' => $partner->user_id,
                'currency_id' => $currencyId,
                'amount' => $amount,
                'convert_to_balance_on' => now()->addDays(self::CLEARING_DAYS),
            ]);
        } catch (\Throwable $e) {
            Log::error('White label: commission not recorded', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * The setup file a partner's customer downloads carries the partner in its name
     * ("gridreader-setup-1.0.0.wl-nile-soft.exe"). The installed program sends that name back on its
     * first start, so it shows the partner's brand before anything is bought.
     */
    public function setupFileName(SoftwareRelease $release, WhiteLabelPartner $partner): string
    {
        return preg_replace('/\.exe$/i', '', $release->file_name).'.wl-'.$partner->slug.'.exe';
    }

    /** Reads the partner back from a setup file name; Windows may have added " (1)" to a second download. */
    public function partnerFromSetupName(?string $setupName): ?WhiteLabelPartner
    {
        if (! $setupName || ! preg_match('/\.wl-([a-z0-9]+(?:-[a-z0-9]+)*)(?:\s*\(\d+\))?\.exe$/i', $setupName, $m)) {
            return null;
        }

        return WhiteLabelPartner::where('slug', strtolower($m[1]))->first();
    }

    public function recordDownload(WhiteLabelPartner $partner, SoftwareProduct $product, string $ip): void
    {
        DB::table('white_label_downloads')->insert([
            'white_label_partner_id' => $partner->id,
            'software_product_id' => $product->id,
            'ip' => $ip,
            'created_at' => now(),
        ]);
    }

    /**
     * The partner a device belongs to: the one who sold its license, otherwise the store it was downloaded from.
     * A device learns its store once, from the setup file name the program sends or, for programs installed
     * before that existed, from a download made from the same address shortly before its first start.
     */
    public function partnerForDevice(SerialSoftware $software, SerialDevice $device, ?int $userId, ?string $setupName, ?string $ip, bool $isNewDevice): ?WhiteLabelPartner
    {
        if (! $device->white_label_partner_id) {
            $found = $this->partnerFromSetupName($setupName) ?? ($isNewDevice ? $this->partnerFromRecentDownload($software, $ip) : null);
            if ($found) {
                $device->update(['white_label_partner_id' => $found->id]);
            }
        }

        // a license decides: one bought from us shows our brand even if the program came from a partner's store
        $license = $userId
            ? SerialSoftwareLicense::where('user_id', $userId)->where('serial_software_id', $software->id)->latest('id')->first()
            : null;
        $partner = WhiteLabelPartner::find($license ? $license->white_label_partner_id : $device->white_label_partner_id);

        return $partner?->isActive() ? $partner : null;
    }

    /** The partner's own page for this program, with the device so the payment activates it by itself. */
    public function devicePurchaseUrl(WhiteLabelPartner $partner, SerialSoftware $software, string $deviceId): string
    {
        $product = $this->products()->firstWhere('serial_software_id', $software->id);

        return $product
            ? route('white-label.product', [$partner, $product->slug]).'?device='.rawurlencode($deviceId)
            : $partner->storeUrl();
    }

    private function partnerFromRecentDownload(SerialSoftware $software, ?string $ip): ?WhiteLabelPartner
    {
        $productId = SoftwareProduct::where('serial_software_id', $software->id)->value('id');
        if (! $productId || ! $ip) {
            return null;
        }

        $partnerId = DB::table('white_label_downloads')
            ->where('software_product_id', $productId)
            ->where('ip', $ip)
            ->where('created_at', '>=', now()->subHours(self::DOWNLOAD_MATCH_HOURS))
            ->orderByDesc('id')
            ->value('white_label_partner_id');

        return $partnerId ? WhiteLabelPartner::find($partnerId) : null;
    }

    /**
     * The line a partner pastes into their site. The script puts the store where the line is and keeps
     * its height fitted to the content, so the partner's page never shows a second scroll bar.
     */
    public function embedCode(WhiteLabelPartner $partner): string
    {
        return '<script src="'.route('white-label.embed', $partner).'" async></script>';
    }

    /** What embed.js runs on the partner's page. `data-lang="ar"` or "en" on the script tag picks the language. */
    public function embedScript(WhiteLabelPartner $partner): string
    {
        $config = json_encode([
            'src' => route('white-label.store', ['partner' => $partner, 'embed' => 1]),
            'origin' => rtrim(url('/'), '/'),
            'title' => $partner->brand_name,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

        return <<<JS
(function () {
  var c = {$config};
  var s = document.currentScript;
  if (!s) return;
  var f = document.createElement('iframe');
  var lang = s.getAttribute('data-lang');
  f.src = c.src + (lang ? '&lang=' + encodeURIComponent(lang) : '');
  f.title = c.title;
  f.loading = 'lazy';
  f.setAttribute('scrolling', 'no');
  f.style.cssText = 'display:block;width:100%;min-height:480px;border:0;overflow:hidden';
  s.parentNode.insertBefore(f, s);
  window.addEventListener('message', function (e) {
    if (e.origin !== c.origin || e.source !== f.contentWindow || !e.data || e.data.type !== 'wl-store-height') return;
    f.style.height = Math.ceil(e.data.height) + 'px';
  });
})();
JS;
    }

    /** For sites that do not allow scripts: a plain frame with a fixed height. */
    public function iframeCode(WhiteLabelPartner $partner): string
    {
        return '<iframe src="'.route('white-label.store', ['partner' => $partner, 'embed' => 1]).'" style="width:100%;min-height:720px;border:0" loading="lazy" title="'.e($partner->brand_name).'"></iframe>';
    }
}
