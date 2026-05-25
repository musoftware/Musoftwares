<?php

namespace Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AffiliatePos\Models\Order;
use Modules\AffiliatePos\app\Features\OrderManagement\Resources\OrderResource;

class AffiliateOrderController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        $query = Order::whereHas('items', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with(['items' => function($q) use ($userId) {
            $q->where('user_id', $userId)->with('product', 'sku');
        }])->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(50);
        
        return OrderResource::collection($orders);
    }
}
