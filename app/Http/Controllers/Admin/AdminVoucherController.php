<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Services\PromotionService;
use App\Http\Resources\VoucherResource;
use App\Http\Requests\Admin\StoreVoucherRequest;
use App\Http\Requests\Admin\UpdateVoucherRequest;
use Inertia\Inertia;

class AdminVoucherController extends Controller
{
    public function __construct(
        protected PromotionService $promotionService
    ) {}

    public function index()
    {
        $vouchers = Voucher::with(['spendCurrency', 'rewardCurrency'])
                           ->orderBy('created_at', 'desc')
                           ->paginate(15)
                           ->through(fn($v) => (new VoucherResource($v))->resolve());

        return Inertia::render('Admin/Vouchers/Index', [
            'vouchers' => $vouchers,
        ]);
    }

    public function store(StoreVoucherRequest $request)
    {
        $this->promotionService->createVoucher($request->validated());

        return redirect()->back()->with('success', __('general.voucher_created_successfully'));
    }

    public function show(Voucher $voucher)
    {
        $voucher->load(['spendCurrency', 'rewardCurrency']);
        
        $redemptions = VoucherRedemption::where('voucher_id', $voucher->id)
                                        ->with(['user', 'transaction', 'rewardTransaction'])
                                        ->orderBy('created_at', 'desc')
                                        ->paginate(20);

        return Inertia::render('Admin/Vouchers/Show', [
            'voucher'     => (new VoucherResource($voucher))->resolve(),
            'redemptions' => $redemptions, // Usually would have its own resource
        ]);
    }

    public function update(UpdateVoucherRequest $request, Voucher $voucher)
    {
        $this->promotionService->updateVoucher($voucher, $request->validated());

        return redirect()->back()->with('success', __('general.voucher_updated_successfully'));
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();

        return redirect()->route('admin.vouchers.index')->with('success', __('general.voucher_deleted_successfully'));
    }
}
