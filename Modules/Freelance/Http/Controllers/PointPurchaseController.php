<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\PointPackage;
use Modules\Freelance\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointPurchaseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:point_packages,id',
        ]);

        $package = PointPackage::findOrFail($validated['package_id']);

        // In a real application, you would integrate with a payment gateway here.
        // For this task, we will simulate a successful purchase.

        DB::transaction(function () use ($request, $package) {
            PointTransaction::create([
                'user_id' => $request->user()->id,
                'points' => $package->points,
                'type' => 'earned',
                'description' => "Purchased {$package->name} package",
            ]);
        });

        return back()->with('success', "Successfully purchased {$package->points} points.");
    }
}
