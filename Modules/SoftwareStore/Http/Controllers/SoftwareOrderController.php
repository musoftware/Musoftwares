<?php

namespace Modules\SoftwareStore\Http\Controllers;

use App\Helpers\KashierHelper;
use App\Http\Controllers\Controller;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\RecoveryDiscount;
use Modules\SoftwareStore\Services\SoftwareOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SoftwareOrderController extends Controller
{
    public function __construct(private readonly SoftwareOrderService $orders, private readonly RecoveryDiscount $recoveryDiscount) {}

    public function store(Request $request, string $slug)
    {
        $product = SoftwareProduct::where('is_active', true)->where('slug', $slug)->firstOrFail();

        $client = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_whatsapp' => 'required|string|max:50',
            'plan_key' => $product->hasPlans() ? ['required', Rule::in(array_column($product->planList(), 'key'))] : 'nullable',
            'device_id' => ['nullable', 'string', 'max:128', 'regex:/^[A-Za-z0-9_\- ]+$/'],
        ]);
        $client['device_id'] = isset($client['device_id']) ? preg_replace('/\s+/', '', $client['device_id']) : null;

        if ($product->salePrice($client['plan_key'] ?? null) <= 0) {
            return back()->with('error', __('software.price_not_set'));
        }

        $order = $this->orders->createPendingOrder($product, $client);

        try {
            return redirect()->away($this->orders->paymentUrl($order));
        } catch (\Throwable $e) {
            Log::error('Software order: payment start failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', __('software.payment_start_failed'));
        }
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

        abort_unless($order->product?->is_active, 404);

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

        return Storage::disk('local')->download($release->file_path, $release->file_name);
    }
}
