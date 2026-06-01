<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\BookingBranch;
use Inertia\Inertia;

class BookingBranchController extends Controller
{
    public function __construct()
    {
        // Enforce the SaaS feature flag
        $this->middleware('feature:booking-multi-branch');
    }

    public function index()
    {
        $branches = BookingBranch::where('tenant_id', Auth::user()->tenant_id)
            ->latest()
            ->paginate(15);
            
        return Inertia::render('Booking/Branches/Index', [
            'branches' => $branches
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'timezone' => 'required|string',
            'is_active' => 'boolean'
        ]);

        // Enforce branch quota via SaaS Metering
        app(\App\Services\MeteredBillingService::class)->incrementUsage('max_booking_branches', 1);

        BookingBranch::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'address' => $request->address,
            'timezone' => $request->timezone,
            'is_active' => $request->is_active ?? true,
        ]);

        return back()->with('success', __('general.branch_created_successfully'));
    }

    public function update(Request $request, $id)
    {
        $branch = BookingBranch::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'timezone' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $branch->update($request->only('name', 'address', 'timezone', 'is_active'));

        return back()->with('success', __('general.branch_updated_successfully'));
    }

    public function destroy($id)
    {
        $branch = BookingBranch::findOrFail($id);
        
        // Ensure no active bookings depend on this branch before deleting, 
        // or just soft-delete/deactivate it. We'll deactivate it for data integrity.
        $branch->is_active = false;
        $branch->save();

        return back()->with('success', __('general.branch_deactivated'));
    }
}
