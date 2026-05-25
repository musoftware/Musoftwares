<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserPaymentMethod;
use App\Http\Resources\PaymentMethodResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = UserPaymentMethod::with('user')->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['pending', 'active']);
        }

        $methods = $query->paginate(15)
                         ->withQueryString()
                         ->through(fn($m) => clone (new PaymentMethodResource($m))->resolve());

        return Inertia::render('Admin/PaymentMethods/Index', [
            'methods' => $methods,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(UserPaymentMethod $paymentMethod)
    {
        $paymentMethod->load('user');

        return Inertia::render('Admin/PaymentMethods/Show', [
            'paymentMethod' => clone (new PaymentMethodResource($paymentMethod))->resolve(),
        ]);
    }

    public function update(Request $request, UserPaymentMethod $paymentMethod)
    {
        $request->validate([
            'status' => 'required|in:pending,active,declined',
        ]);

        $paymentMethod->update([
            'status' => $request->status,
        ]);

        if ($request->status === 'active') {
            if (class_exists(\App\Notifications\PaymentStatusNotification::class)) {
                $paymentMethod->user->notify(new \App\Notifications\PaymentStatusNotification($paymentMethod));
            }
        }

        return redirect()->route('admin.payment-methods.index')->with('success', 'Payment method status updated.');
    }
}
