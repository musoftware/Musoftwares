<?php

namespace App\Http\Controllers\Client;

use App\Helpers\FinanceHelper;
use App\Http\Controllers\Controller;
use App\Models\CurrenciesExchange;
use App\Models\MicroService;
use App\Models\MicroServiceOrder;
use App\Services\MicroServiceOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MicroServiceController extends Controller
{
    public function __construct(
        protected MicroServiceOrderService $orderService
    ) {}

    /**
     * Display micro-services catalog & user's orders.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userCurrencyId = $user->currency_id ?: 1;
        $userCurrencySymbol = $user->currency?->symbol ?? 'EGP';

        $services = MicroService::active()
            ->with('currency')
            ->get()
            ->map(function (MicroService $s) use ($userCurrencyId) {
                $baseCurrencyId = $s->currency_id ?: (CurrenciesExchange::BusinessCurrency() ?: 1);
                $convertedPrice = (float) CurrenciesExchange::RateToday(
                    (float) $s->price,
                    $baseCurrencyId,
                    $userCurrencyId
                );

                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'description' => $s->description,
                    'base_price' => (float) $s->price,
                    'price' => $convertedPrice,
                    'formatted_price' => FinanceHelper::instance()->format_money($convertedPrice, $userCurrencyId),
                    'delivery_days' => $s->delivery_days,
                    'is_active' => $s->is_active,
                ];
            });

        $orders = MicroServiceOrder::where('user_id', $user->id)
            ->with(['microService', 'currency'])
            ->orderByDesc('id')
            ->get()
            ->map(function (MicroServiceOrder $o) {
                return [
                    'id' => $o->id,
                    'service_title' => $o->microService?->title ?? 'خدمة مصغرة',
                    'service_description' => $o->microService?->description,
                    'amount_paid' => (float) $o->amount_paid,
                    'currency_symbol' => $o->currency?->symbol ?? 'EGP',
                    'formatted_amount' => FinanceHelper::instance()->format_money((float) $o->amount_paid, $o->currency_id),
                    'requirements' => $o->requirements,
                    'status' => $o->status,
                    'admin_notes' => $o->admin_notes,
                    'completed_at' => $o->completed_at ? $o->completed_at->toIso8601String() : null,
                    'created_at' => $o->created_at ? $o->created_at->toIso8601String() : null,
                ];
            });

        $availableBalance = (float) $user->available_balance();

        return Inertia::render('Client/MicroServices/Index', [
            'services' => $services,
            'orders' => $orders,
            'available_balance' => $availableBalance,
            'formatted_balance' => FinanceHelper::instance()->format_money($availableBalance, $userCurrencyId),
            'currency_symbol' => $userCurrencySymbol,
        ]);
    }

    /**
     * Purchase and create a new micro-service order.
     */
    public function order(Request $request, MicroService $microService)
    {
        $validated = $request->validate([
            'requirements' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'requirements.required' => 'يرجى كتابة تفاصيل وبيانات الطلب المطلوبة لتنفيذ الخدمة.',
            'requirements.min' => 'يجب كتابة تفاصيل كافية (على الأقل 5 أحرف).',
        ]);

        $this->orderService->purchase(Auth::user(), $microService, $validated['requirements']);

        return redirect()->route('micro-services.index')
            ->with('success', 'تم شراء الخدمة المصغرة بنجاح وتم إرسال تفاصيل طلبك للإدارة للبدء في التنفيذ.');
    }
}
