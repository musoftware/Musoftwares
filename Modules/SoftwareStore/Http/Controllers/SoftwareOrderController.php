<?php

namespace Modules\SoftwareStore\Http\Controllers;

use App\Helpers\KashierHelper;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Currency;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\RecoveryDiscount;
use Modules\SoftwareStore\Services\SoftwareOrderService;
use Modules\SoftwareStore\Services\WhiteLabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SoftwareOrderController extends Controller
{
    public function __construct(private readonly SoftwareOrderService $orders, private readonly RecoveryDiscount $recoveryDiscount) {}

    public function store(Request $request, string $slug)
    {
        $product = SoftwareProduct::where('slug', $slug)->firstOrFail();
        abort_unless($product->canBeOrdered(), 404);

        $client = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_whatsapp' => 'required|string|max:50',
            'plan_key' => $product->hasPlans() ? ['required', Rule::in(array_column($product->planList(), 'key'))] : 'nullable',
            'device_id' => ['nullable', 'string', 'max:128', 'regex:/^[A-Za-z0-9_\- ]+$/'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);
        $client['device_id'] = isset($client['device_id']) ? preg_replace('/\s+/', '', $client['device_id']) : null;

        $basePrice = (float) $product->salePrice($client['plan_key'] ?? null);
        if ($basePrice <= 0) {
            return back()->with('error', __('software.price_not_set'));
        }

        $couponResult = $this->resolveCoupon($client['coupon_code'] ?? null, $basePrice);
        if (isset($couponResult['error'])) {
            return back()->withInput()->withErrors(['coupon_code' => $couponResult['error']]);
        }

        $coupon = $couponResult['coupon'] ?? null;
        $discountAmount = $couponResult['discount'] ?? 0.0;
        $finalAmount = max(0.0, round($basePrice - $discountAmount, 2));

        $order = DB::transaction(function () use ($product, $client, $coupon, $discountAmount, $finalAmount) {
            if ($coupon) {
                $this->consumeCoupon($coupon, $discountAmount, $product->saleCurrency());
            }

            return $this->orders->createPendingOrder(
                $product,
                $client,
                partner: null,
                coupon: $coupon,
                discountAmount: $discountAmount,
                overrideAmount: $finalAmount
            );
        });

        if ($order->amount <= 0) {
            $this->orders->markPaid($order, 'coupon:'.($coupon ? $coupon->code : 'free'));

            return redirect()->route('software.orders.success', $order->uuid);
        }

        try {
            return redirect()->away($this->orders->paymentUrl($order));
        } catch (\Throwable $e) {
            Log::error('Software order: payment start failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', __('software.payment_start_failed'));
        }
    }

    private function resolveCoupon(?string $rawCode, float $basePrice): array
    {
        if (empty($rawCode)) {
            return ['coupon' => null, 'discount' => 0.0];
        }

        if (! auth()->check()) {
            return ['error' => __('software.coupon_requires_client_login')];
        }

        $user = auth()->user();
        if (! ($user->hasRole('client') || $user->isAdmin())) {
            return ['error' => __('software.coupon_must_be_client')];
        }

        $code = strtoupper(trim($rawCode));
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->is_active) {
            return ['error' => __('software.coupon_invalid_or_inactive')];
        }

        $now = now('Africa/Cairo');
        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            return ['error' => __('software.coupon_invalid_or_inactive')];
        }

        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            return ['error' => __('software.coupon_expired')];
        }

        if ($coupon->max_total_uses && $coupon->current_uses >= $coupon->max_total_uses) {
            return ['error' => __('software.coupon_exhausted')];
        }

        if ($coupon->max_uses_per_user) {
            $userUses = CouponRedemption::where('coupon_id', $coupon->id)->where('user_id', $user->id)->count();
            if ($userUses >= $coupon->max_uses_per_user) {
                return ['error' => __('software.coupon_user_limit_reached')];
            }
        }

        if ($coupon->min_purchase_amount && $basePrice < (float) $coupon->min_purchase_amount) {
            return ['error' => __('software.coupon_invalid_or_inactive')];
        }

        $discount = (float) $coupon->calculateDiscount($basePrice);

        return ['coupon' => $coupon, 'discount' => $discount];
    }

    private function consumeCoupon(Coupon $coupon, float $discountAmount, string $saleCurrency): void
    {
        $coupon->increment('current_uses');
        if ($coupon->max_total_uses && $coupon->current_uses >= $coupon->max_total_uses) {
            $coupon->update(['is_active' => false]);
        }

        $currencyId = $coupon->currency_id ?? Currency::where('currency', $saleCurrency)->value('id') ?? 1;

        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => auth()->id(),
            'discount_amount' => $discountAmount,
            'currency_id' => $currencyId,
        ]);
    }

    public function success(string $uuid)
    {
        $order = SoftwareOrder::with('product')->where('uuid', $uuid)->firstOrFail();
        $hasRelease = $order->isPaid() && $order->product->latestRelease();

        return view($order->partner ? 'softwarestore::white-label.result' : 'softwarestore::result', [
            'order' => $order,
            'succeeded' => true,
            'whatsappLink' => $this->orders->whatsappLink($order),
            'downloadLink' => $hasRelease ? $this->orders->downloadLink($order) : null,
        ]);
    }

    /** The signed link in the reminder email: an unpaid order goes back to the payment page, a paid one to its result page. */
    public function resume(string $uuid)
    {
        $order = SoftwareOrder::with('product')->where('uuid', $uuid)->firstOrFail();

        if ($order->isPaid()) {
            return redirect()->route('software.orders.success', $order->uuid);
        }

        abort_unless($order->product?->canBeOrdered(), 404);

        $this->recoveryDiscount->apply($order);

        try {
            return redirect()->away($this->orders->paymentUrl($order));
        } catch (\Throwable $e) {
            Log::error('Software order: resume payment failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return redirect()->route('software.orders.failure', $order->uuid);
        }
    }

    public function failure(string $uuid)
    {
        $order = SoftwareOrder::with('product')->where('uuid', $uuid)->firstOrFail();

        return view($order->partner ? 'softwarestore::white-label.result' : 'softwarestore::result', ['order' => $order, 'succeeded' => false]);
    }

    public function webhook(Request $request)
    {
        if (! KashierHelper::validatePayload()) {
            Log::warning('Software Kashier webhook: invalid signature.');

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $data = $request->json('data', []);
        $isPaymentSuccess = ($data['status'] ?? '') === 'SUCCESS';
        $matched = preg_match('/^swo_(\d+)_/', (string) ($data['merchantOrderId'] ?? ''), $m);
        if (! $isPaymentSuccess || ! $matched) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $order = SoftwareOrder::find((int) $m[1]);
        if (! $order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        $this->orders->markPaid($order, $data['transactionId'] ?? null);

        return response()->json(['status' => 'processed'], 200);
    }

    public function download(Request $request, string $uuid)
    {
        abort_unless($request->hasValidSignature(), 403);

        $order = SoftwareOrder::with('product')->where('uuid', $uuid)->firstOrFail();
        abort_unless($order->isPaid(), 403);

        $release = $order->product->latestRelease();
        abort_unless($release && Storage::disk('local')->exists($release->file_path), 404);

        // bought from a partner: the setup carries their name, so the program shows their brand
        $name = $order->partner ? app(WhiteLabelService::class)->setupFileName($release, $order->partner) : $release->file_name;

        return Storage::disk('local')->download($release->file_path, $name);
    }
}
