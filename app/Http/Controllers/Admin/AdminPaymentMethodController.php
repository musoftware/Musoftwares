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
        // Validate status filter against known enum values to prevent silent empty results
        $allowedStatuses = ['pending', 'active', 'declined'];
        $statusFilter    = in_array($request->status, $allowedStatuses, true) ? $request->status : null;

        $query = UserPaymentMethod::with('user')->orderBy('id', 'desc');

        // Status filter: default shows pending + active (most actionable)
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        } else {
            $query->whereIn('status', ['pending', 'active']);
        }

        // Search by user name, email, bank name, mobile, payee email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                  ->orWhere('bank', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('payee_email', 'like', "%{$search}%")
                  ->orWhere('ewallet_provider', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $methods = $query->paginate(15)
                         ->withQueryString()
                         ->through(fn ($m) => (new PaymentMethodResource($m))->resolve());

        $stats = [
            'total'    => UserPaymentMethod::count(),
            'pending'  => UserPaymentMethod::where('status', 'pending')->count(),
            'active'   => UserPaymentMethod::where('status', 'active')->count(),
            'declined' => UserPaymentMethod::where('status', 'declined')->count(),
        ];

        return Inertia::render('Admin/PaymentMethods/Index', [
            'methods' => $methods,
            'filters' => $request->only(['status', 'search']),
            'stats'   => $stats,
        ]);
    }

    public function show(UserPaymentMethod $paymentMethod)
    {
        $paymentMethod->load('user');

        return Inertia::render('Admin/PaymentMethods/Show', [
            'paymentMethod' => (new PaymentMethodResource($paymentMethod))->resolve(),
        ]);
    }

    public function update(Request $request, UserPaymentMethod $paymentMethod)
    {
        $request->validate([
            'status' => 'required|in:pending,active,declined',
        ]);

        $oldStatus = $paymentMethod->status;
        $newStatus = $request->status;

        $paymentMethod->update(['status' => $newStatus]);

        // Only notify if user is linked and status actually changed
        if ($oldStatus !== $newStatus && $paymentMethod->user) {
            $this->sendStatusNotification($paymentMethod, $newStatus);
        }

        return redirect()->back()->with('success', 'Payment method status updated.');
    }

    /**
     * Send a status notification to the user.
     * Silently skips if the notification class does not exist.
     */
    private function sendStatusNotification(UserPaymentMethod $paymentMethod, string $status): void
    {
        if (! class_exists(\App\Notifications\PaymentMethodStatusNotification::class)) {
            return;
        }

        try {
            $paymentMethod->user->notify(
                new \App\Notifications\PaymentMethodStatusNotification($paymentMethod, $status)
            );
        } catch (\Throwable $e) {
            // Notification failure must never break the admin action
            \Illuminate\Support\Facades\Log::warning('PaymentMethod notification failed', [
                'method_id' => $paymentMethod->id,
                'status'    => $status,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
