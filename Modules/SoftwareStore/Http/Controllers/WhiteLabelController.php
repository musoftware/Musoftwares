<?php

namespace Modules\SoftwareStore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\User;
use App\Support\PageSeo;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Models\WhiteLabelPartner;
use Modules\SoftwareStore\Services\WhiteLabelService;

/** The public white label offer, and the partner's own page: brand, prices, embed code, subscription and sales. */
class WhiteLabelController extends Controller
{
    public function __construct(private readonly WhiteLabelService $whiteLabel) {}

    public function landing()
    {
        return view('softwarestore::white-label.landing', PageSeo::for('white_label'));
    }

    public function settings(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $partner = WhiteLabelPartner::where('user_id', $user->id)->first();

        return Inertia::render('Client/WhiteLabel/Index', [
            'partner' => $partner ? [
                'brand_name' => $partner->brand_name,
                'slug' => $partner->slug,
                'logo_url' => $partner->logoUrl(),
                'accent_color' => $partner->accent_color,
                'support_whatsapp' => $partner->support_whatsapp,
                'support_email' => $partner->support_email,
                'website_url' => $partner->website_url,
                'is_active' => $partner->isActive(),
                'expires_at' => $partner->subscription_expires_at?->toDateString(),
                'store_url' => $partner->storeUrl(),
                'iframe' => $this->whiteLabel->iframeCode($partner),
            ] : null,
            'suggestedSlug' => Str::slug($user->name) ?: 'store-'.$user->id,
            'rows' => $this->priceRows($partner),
            'sales' => $partner ? $this->sales($partner) : [],
            'wallet' => number_format((float) $user->user_balance, 2).' '.(Currency::find($user->currency_id)?->currency ?? ''),
            'commissionPercent' => WhiteLabelService::COMMISSION_PERCENT,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $partner = WhiteLabelPartner::firstOrNew(['user_id' => $user->id]);

        $data = $request->validate([
            'brand_name' => 'required|string|max:80',
            'slug' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('white_label_partners', 'slug')->ignore($partner->id)],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'support_whatsapp' => 'nullable|string|max:30',
            'support_email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:1024',
            'prices' => 'nullable|array',
            'prices.*' => 'nullable|numeric|min:0|max:10000000',
        ]);

        $partner->fill([
            'brand_name' => $data['brand_name'],
            'slug' => $data['slug'],
            'accent_color' => strtolower($data['accent_color']),
            'support_whatsapp' => $data['support_whatsapp'] ?? null,
            'support_email' => $data['support_email'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'prices' => $this->checkedPrices($data['prices'] ?? []),
        ]);

        if ($request->hasFile('logo')) {
            if ($partner->logo_path) {
                Storage::disk('public_uploads')->delete($partner->logo_path);
            }
            $partner->logo_path = $request->file('logo')->store('white-label', 'public_uploads');
        }

        $partner->save();

        return back()->with('success', __('white_label.saved'));
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $partner = WhiteLabelPartner::where('user_id', $request->user()->id)->first();
        if (! $partner) {
            return back()->with('error', __('white_label.save_brand_first'));
        }

        try {
            $this->whiteLabel->subscribe($partner);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('White label: subscription failed', ['partner_id' => $partner->id, 'error' => $e->getMessage()]);

            return back()->with('error', __('white_label.subscribe_failed'));
        }

        return back()->with('success', __('white_label.subscribed', ['date' => $partner->fresh()->subscription_expires_at->toDateString()]));
    }

    /** Keeps only prices that are set; any price under ours is refused with the program's name. */
    private function checkedPrices(array $prices): array
    {
        $checked = [];
        foreach ($this->priceRows(null) as $row) {
            $value = $prices[$row['key']] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            if ((float) $value < $row['base']) {
                throw ValidationException::withMessages([
                    "prices.{$row['key']}" => __('white_label.price_too_low', ['program' => $row['name'], 'price' => $row['base_label']]),
                ]);
            }
            $checked[$row['key']] = round((float) $value, 2);
        }

        return $checked;
    }

    /** One row per program, or per plan for programs with plans. */
    private function priceRows(?WhiteLabelPartner $partner): array
    {
        $rows = [];
        foreach ($this->whiteLabel->products() as $product) {
            /** @var SoftwareProduct $product */
            $plans = $product->hasPlans() ? $product->planList() : [null];
            foreach ($plans as $plan) {
                $base = $product->salePrice($plan['key'] ?? null);
                $key = WhiteLabelService::priceKey($product, $plan['key'] ?? null);
                $rows[] = [
                    'key' => $key,
                    'name' => $product->localized('name').($plan ? ' · '.$plan['name'] : ''),
                    'base' => $base,
                    'base_label' => $product->money($base).' '.$product->saleCurrency(),
                    'currency' => $product->saleCurrency(),
                    'price' => $partner?->prices[$key] ?? null,
                ];
            }
        }

        return $rows;
    }

    private function sales(WhiteLabelPartner $partner): array
    {
        return $partner->orders()->with('product')->latest()->limit(30)->get()->map(fn (SoftwareOrder $order) => [
            'id' => $order->id,
            'date' => $order->created_at->toDateString(),
            'program' => $order->product?->localized('name'),
            'customer' => $order->client_name,
            'amount' => $order->amount.' '.$order->currency,
            'share' => $order->partner_commission.' '.$order->currency,
            'paid' => $order->isPaid(),
        ])->all();
    }
}
