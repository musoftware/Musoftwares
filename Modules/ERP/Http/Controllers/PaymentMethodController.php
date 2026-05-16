<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\PaymentMethod;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    // Client Methods
    public function index(Request $request)
    {
        $client = $request->user()->client;
        if (!$client) abort(403);

        $paymentMethods = PaymentMethod::where('client_id', $client->id)->get();

        return Inertia::render('ERP/PaymentMethods/Index', [
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request)
    {
        $client = $request->user()->client;
        if (!$client) abort(403);

        $request->validate([
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'swift_code' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        if ($request->is_default) {
            PaymentMethod::where('client_id', $client->id)->update(['is_default' => false]);
        }

        PaymentMethod::create([
            'tenant_id' => $client->tenant_id,
            'client_id' => $client->id,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'swift_code' => $request->swift_code,
            'is_default' => $request->is_default ?? false,
            'status' => 'pending', // Requires admin approval
        ]);

        return back()->with('success', 'Payment method added. Waiting for approval.');
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $client = $request->user()->client;
        if (!$client || $paymentMethod->client_id !== $client->id) abort(403);

        $request->validate([
            'is_default' => 'required|boolean',
        ]);

        if ($request->is_default) {
            PaymentMethod::where('client_id', $client->id)->update(['is_default' => false]);
        }

        $paymentMethod->update(['is_default' => $request->is_default]);

        return back()->with('success', 'Payment method updated.');
    }

    public function destroy(Request $request, PaymentMethod $paymentMethod)
    {
        $client = $request->user()->client;
        if (!$client || $paymentMethod->client_id !== $client->id) abort(403);

        $paymentMethod->delete();

        return back()->with('success', 'Payment method removed.');
    }

    // Admin Methods
    public function approve(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['status' => 'approved']);
        return back()->with('success', 'Payment method approved.');
    }

    public function reject(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['status' => 'rejected']);
        return back()->with('success', 'Payment method rejected.');
    }
}
