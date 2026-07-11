<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\PaymentLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentLinkController extends Controller
{
    public function index()
    {
        $paymentLinks = PaymentLink::with(['currency', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $currencies = Currency::all();

        return Inertia::render('Admin/Finance/PaymentLinks/Index', [
            'paymentLinks' => $paymentLinks,
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
        ]);

        PaymentLink::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'currency_id' => $validated['currency_id'],
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', __('erp.created_successfully'));
    }

    public function destroy(PaymentLink $paymentLink)
    {
        $paymentLink->delete();

        return redirect()->back()->with('success', __('erp.deleted_successfully'));
    }
}
