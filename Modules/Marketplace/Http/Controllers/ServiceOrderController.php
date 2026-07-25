<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Services\FinancialTransactionService;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Events\MarketplaceOrderPlaced;
use App\Events\MarketplaceOrderCompleted;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Services\EscrowService;

class ServiceOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->query('tab', 'purchases');

        if ($tab === 'sales') {
            $orders = ServiceOrder::with(['buyer', 'package.service'])->where('seller_id', $user->id)->latest()->paginate(15);
        } else {
            $orders = ServiceOrder::with(['seller', 'package.service'])->where('buyer_id', $user->id)->latest()->paginate(15);
        }

        return Inertia::render('Marketplace/Orders/Index', [
            'orders' => $orders,
            'tab' => $tab
        ]);
    }

    public function show(ServiceOrder $order)
    {
        // Authorize user (buyer or seller)
        if (auth()->id() !== $order->buyer_id && auth()->id() !== $order->seller_id) {
            abort(403);
        }

        $order->load(['buyer', 'seller', 'package.service']);

        $conversation = Conversation::firstOrCreate(
            [
                'conversable_type' => ServiceOrder::class,
                'conversable_id' => $order->id,
            ],
            [
                'type' => 'marketplace_order',
                'status' => 'open',
            ]
        );

        if (! $conversation->participants()->exists()) {
            $conversation->participants()->createMany([
                ['user_id' => $order->buyer_id, 'role' => 'buyer'],
                ['user_id' => $order->seller_id, 'role' => 'seller'],
            ]);
        }

        $conversation->load(['messages.sender', 'participants.user']);

        return Inertia::render('Marketplace/Orders/Show', [
            'order' => $order,
            'conversation' => $conversation
        ]);
    }

    public function store(Request $request, FinancialTransactionService $financialService, EscrowService $escrowService, \Modules\Marketplace\Services\SoftwareLicenseService $digitalKeyService)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:marketplace_packages,id',
        ]);

        $package = ServicePackage::with('service')->findOrFail($validated['package_id']);

        if (!$package->service || $package->service->status !== 'active') {
            return redirect()->back()->withErrors(['error' => 'This service is currently unavailable.']);
        }

        $buyer = auth()->user();
        $seller_id = $package->service->seller_id;

        if ($buyer->id === $seller_id) {
            return redirect()->back()->withErrors(['error' => 'You cannot purchase your own service.']);
        }

        if ($buyer->available_balance() < $package->price) {
            return redirect()->back()->withErrors(['error' => 'Insufficient balance.']);
        }

        // Commission logic (BC math for financial precision)
        $commissionRate = config('marketplace.commission_rate', '0.10');
        $commissionAmount = bcmul((string) $package->price, (string) $commissionRate, 4);

        DB::beginTransaction();

        try {
            // Lock buyer to prevent concurrent balance deductions
            $lockedBuyer = \App\Models\User::where('id', $buyer->id)->lockForUpdate()->first();
            if ($lockedBuyer->available_balance() < $package->price) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => 'Insufficient balance.']);
            }

            $deliveryPayload = null;
            $orderStatus = ServiceOrderStatus::PENDING;

            // Handle Instant Digital Delivery Key if service provides serials
            if ($package->service->generate_serials) {
                $serial = $digitalKeyService->assignSerialToOrder($package->service->id, 0, $lockedBuyer->id);
                $deliveryPayload = ['serial_code' => $serial->serial_code];
                $orderStatus = ServiceOrderStatus::DELIVERED;
            }

            $snapshot = [
                'service_title' => $package->service->title,
                'service_description' => $package->service->description,
                'package_name' => $package->name,
                'package_description' => $package->description,
                'price' => (float) $package->price,
                'delivery_days' => $package->delivery_days,
                'revisions' => $package->revisions ?? 1,
            ];

            // Create Order
            $order = ServiceOrder::create([
                'buyer_id' => $lockedBuyer->id,
                'seller_id' => $seller_id,
                'package_id' => $package->id,
                'amount' => $package->price,
                'currency_id' => $package->currency_id,
                'commission_amount' => $commissionAmount,
                'status' => $orderStatus,
                'snapshot' => $snapshot,
                'due_date' => now('Africa/Cairo')->addDays($package->delivery_days),
                'delivery_payload' => $deliveryPayload,
                'delivered_at' => $package->service->generate_serials ? now('Africa/Cairo') : null,
                'auto_complete_at' => $package->service->generate_serials ? now('Africa/Cairo')->addDays(3) : null,
            ]);

            // Log status history
            \Modules\Marketplace\Models\OrderStatusHistory::create([
                'order_id' => $order->id,
                'old_status' => null,
                'new_status' => $orderStatus->value,
                'changed_by' => $lockedBuyer->id,
                'note' => 'Order created and payment held in escrow.'
            ]);

            // Delegate escrow creation and lock to the EscrowService
            $escrowService->holdFunds($order);

            // Create Conversation
            $conversation = Conversation::create([
                'conversable_type' => ServiceOrder::class,
                'conversable_id' => $order->id,
                'type' => 'marketplace_order',
                'status' => 'open'
            ]);

            $conversation->participants()->createMany([
                ['user_id' => $buyer->id, 'role' => 'buyer'],
                ['user_id' => $seller_id, 'role' => 'seller'],
            ]);

            DB::commit();

            event(new MarketplaceOrderPlaced($order));

            return redirect()->route('marketplace.orders.show', $order->id)->with('success', __('general.order_placed_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Order failed: ' . $e->getMessage()]);
        }
    }

    public function deliver(Request $request, ServiceOrder $order)
    {
        $this->authorize('deliver', $order);

        $validated = $request->validate([
            'message' => 'nullable|string',
            'links' => 'nullable|string'
        ]);

        $payload = [
            'message' => $validated['message'] ?? '',
            'links' => $validated['links'] ?? ''
        ];

        $order->update([
            'status' => ServiceOrderStatus::DELIVERED,
            'delivered_at' => now('Africa/Cairo'),
            'auto_complete_at' => now('Africa/Cairo')->addDays(3),
            'delivery_payload' => $payload
        ]);

        return redirect()->back()->with('success', __('general.order_marked_as_delivered'));
    }

    public function complete(ServiceOrder $order, EscrowService $escrowService)
    {
        $this->authorize('complete', $order);

        if ($order->status === ServiceOrderStatus::COMPLETED) {
            return redirect()->back()->with('error', __('general.order_already_completed'));
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => ServiceOrderStatus::COMPLETED,
                'completed_at' => now('Africa/Cairo'),
            ]);

            $escrow = \Modules\Marketplace\Models\MarketplaceEscrow::where('order_id', $order->id)->first();
            if ($escrow) {
                $escrowService->releaseFunds($escrow);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to complete order: ' . $e->getMessage()]);
        }

        event(new MarketplaceOrderCompleted($order));

        return redirect()->back()->with('success', __('general.order_completed'));
    }

    public function dispute(ServiceOrder $order, EscrowService $escrowService)
    {
        $this->authorize('dispute', $order);

        $order->update([
            'status' => ServiceOrderStatus::DISPUTED,
        ]);
        
        $escrow = \Modules\Marketplace\Models\MarketplaceEscrow::where('order_id', $order->id)->first();
        if ($escrow) {
            $escrowService->dispute($escrow);
        }

        return redirect()->back()->with('success', __('general.order_is_now_in_dispute'));
    }
}

