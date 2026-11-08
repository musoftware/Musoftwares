<?php

namespace Modules\Shop\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Shop\Models\ShopOrder;

class AdminShopOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status');

        $orders = ShopOrder::with('items')
            ->when(in_array($status, ShopOrder::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Shop/Orders', [
            'orders' => $orders,
            'statuses' => ShopOrder::STATUSES,
            'status' => $status,
            'currency' => config('shop.currency_symbol'),
        ]);
    }

    public function updateStatus(Request $request, ShopOrder $order): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(ShopOrder::STATUSES)]]);
        $order->update($data);

        return back();
    }
}
