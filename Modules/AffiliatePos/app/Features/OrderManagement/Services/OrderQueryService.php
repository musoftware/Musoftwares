<?php

namespace Modules\AffiliatePos\app\Features\OrderManagement\Services;

use Modules\AffiliatePos\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderQueryService
{
    public function buildQuery(Request $request)
    {
        $query = Order::with('items.product', 'items.sku', 'shippingCompany', 'user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', Carbon::parse($request->from_date)->startOfDay());
        }
        
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', Carbon::parse($request->to_date)->endOfDay());
        }

        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        if ($request->filled('mobile')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_phone', $request->mobile)
                  ->orWhere('customer_phone2', $request->mobile);
            });
        }

        if ($request->filled('name')) {
            $query->where('customer_name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('shipping_companies')) {
            $query->where('shipping_company_id', $request->shipping_companies);
        }

        if ($request->filled('product')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->product);
            });
        }

        if ($request->has('processing')) {
            $query->whereIn('status', ['new', 'preparing', 'shipping']);
        }

        if ($request->has('returning')) {
            $query->whereIn('status', ['returning', 'returned']);
        }

        if ($request->has('replacing')) {
            $query->where(function($q) {
                $q->where('status', 'replacing')
                  ->orWhere(function($q2) {
                      $q2->where('status', 'partial_delivery')->where('replacing', 1);
                  });
            });
        }

        if ($request->filled('customer_governorate_id')) {
            $query->where('customer_governorate_id', $request->customer_governorate_id);
        }

        if ($request->filled('customer_city_id')) {
            $query->where('customer_city_id', $request->customer_city_id);
        }

        if ($request->filled('order')) {
            $query->where('id', $request->order);
        }

        return $query->latest();
    }
}
