<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Currency;
use App\Services\PromotionService;
use App\Http\Resources\CouponResource;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminCouponController extends Controller
{
    public function __construct(
        protected PromotionService $promotionService
    ) {}

    public function index()
    {
        $coupons = Coupon::with('currencyRelation')
                         ->orderBy('created_at', 'desc')
                         ->paginate(15)
                         ->through(fn($c) => (new CouponResource($c))->resolve());

        $currencies = Currency::select('id', 'currency')->get();

        return Inertia::render('Admin/Coupons/Index', [
            'coupons'    => $coupons,
            'currencies' => $currencies,
        ]);
    }

    public function store(StoreCouponRequest $request)
    {
        $this->promotionService->createCoupon($request->validated());

        return redirect()->back()->with('success', 'Coupon created successfully');
    }

    public function show(Coupon $coupon)
    {
        $coupon->load('currencyRelation');
        
        $redemptions = CouponRedemption::where('coupon_id', $coupon->id)
                                       ->with(['user', 'transaction'])
                                       ->orderBy('created_at', 'desc')
                                       ->paginate(20);

        return Inertia::render('Admin/Coupons/Show', [
            'coupon'      => (new CouponResource($coupon))->resolve(),
            'redemptions' => $redemptions,
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        $this->promotionService->updateCoupon($coupon, $request->validated());

        return redirect()->back()->with('success', 'Coupon updated successfully');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully');
    }
}
