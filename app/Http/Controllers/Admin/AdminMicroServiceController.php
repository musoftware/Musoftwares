<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FinanceHelper;
use App\Http\Controllers\Controller;
use App\Models\MicroService;
use App\Models\MicroServiceOrder;
use App\Services\MicroServiceOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminMicroServiceController extends Controller
{
    public function __construct(
        protected MicroServiceOrderService $orderService
    ) {}

    /**
     * Display admin services & client orders management.
     */
    public function index(Request $request)
    {
        $services = MicroService::orderBy('order_index')
            ->orderBy('id')
            ->withCount('orders')
            ->get()
            ->map(function (MicroService $s) {
                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'description' => $s->description,
                    'price' => (float) $s->price,
                    'formatted_price' => FinanceHelper::instance()->format_money((float) $s->price, $s->currency_id ?: 1),
                    'delivery_days' => $s->delivery_days,
                    'is_active' => $s->is_active,
                    'orders_count' => $s->orders_count,
                    'created_at' => $s->created_at ? $s->created_at->toIso8601String() : null,
                ];
            });

        $ordersQuery = MicroServiceOrder::with(['user', 'microService', 'currency'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $ordersQuery->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $ordersQuery->where(function ($q) use ($search) {
                $q->where('requirements', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('microService', fn ($m) => $m->where('title', 'like', "%{$search}%"));
            });
        }

        $orders = $ordersQuery->paginate(15)->through(function (MicroServiceOrder $o) {
            return [
                'id' => $o->id,
                'client_name' => $o->user?->name ?? 'غير معروف',
                'client_email' => $o->user?->email,
                'service_title' => $o->microService?->title ?? 'خدمة محذوفة',
                'amount_paid' => (float) $o->amount_paid,
                'formatted_amount' => FinanceHelper::instance()->format_money((float) $o->amount_paid, $o->currency_id),
                'requirements' => $o->requirements,
                'status' => $o->status,
                'admin_notes' => $o->admin_notes,
                'completed_at' => $o->completed_at ? $o->completed_at->toIso8601String() : null,
                'created_at' => $o->created_at ? $o->created_at->toIso8601String() : null,
            ];
        });

        $stats = [
            'total_orders' => MicroServiceOrder::count(),
            'pending_orders' => MicroServiceOrder::where('status', 'pending')->count(),
            'completed_orders' => MicroServiceOrder::where('status', 'completed')->count(),
            'active_services' => MicroService::where('is_active', true)->count(),
        ];

        return Inertia::render('Admin/MicroServices/Index', [
            'services' => $services,
            'orders' => $orders,
            'filters' => $request->only(['status', 'search']),
            'stats' => $stats,
        ]);
    }

    /**
     * Create a new micro service.
     */
    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:90'],
            'is_active' => ['boolean'],
        ]);

        MicroService::create($validated);

        return redirect()->back()->with('success', 'تم إنشاء الخدمة المصغرة بنجاح.');
    }

    /**
     * Update an existing micro service.
     */
    public function updateService(Request $request, MicroService $microService)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:90'],
            'is_active' => ['boolean'],
        ]);

        $microService->update($validated);

        return redirect()->back()->with('success', 'تم تحديث الخدمة المصغرة بنجاح.');
    }

    /**
     * Soft delete a micro service.
     */
    public function destroyService(MicroService $microService)
    {
        $microService->delete();

        return redirect()->back()->with('success', 'تم حذف الخدمة المصغرة بنجاح.');
    }

    /**
     * Mark an order as completed with admin delivery notes.
     */
    public function completeOrder(Request $request, MicroServiceOrder $order)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->orderService->completeOrder($order, $validated['admin_notes'] ?? null);

        return redirect()->back()->with('success', "تم تحديد الطلب #{$order->id} كمكتمل بنجاح.");
    }
}
