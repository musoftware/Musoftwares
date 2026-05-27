<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\PaymentMethod;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    // ── Tenant resolution ─────────────────────────────────────────────

    private function resolveTenant(): Tenant
    {
        return Tenant::where('user_id', Auth::id())->firstOrFail();
    }

    /**
     * Resolve the TenantClient for the current platform user.
     * Uses user_id FK (C3 fix), falls back to email match for legacy records.
     */
    private function resolveClient(): TenantClient
    {
        $client = TenantClient::where('user_id', Auth::id())->first();

        if (!$client) {
            abort(403, 'No client record is linked to your account.');
        }

        return $client;
    }

    // ── Client Methods ────────────────────────────────────────────────

    public function index(Request $request)
    {
        $client = $this->resolveClient();

        $paymentMethods = PaymentMethod::where('client_id', $client->id)->get();

        return Inertia::render('ERP/PaymentMethods/Index', [
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request)
    {
        $client = $this->resolveClient();

        $request->validate([
            'bank_name'           => 'required|string|max:100',
            // M1 fix: was 'account_name' — DB column is 'account_holder_name'
            'account_holder_name' => 'required|string|max:200',
            'account_number'      => 'required|string|max:100',
            'iban'                => 'nullable|string|max:50',
            'swift_code'          => 'nullable|string|max:20',
            'bank_country'        => 'required|string|max:100',
            'bank_currency'       => 'required|string|max:10',
            'is_default'          => 'boolean',
        ]);

        if ($request->boolean('is_default')) {
            PaymentMethod::where('client_id', $client->id)->update(['is_default' => false]);
        }

        PaymentMethod::create([
            'tenant_id'           => $client->tenant_id,
            'client_id'           => $client->id,
            'type'                => 'bank_transfer',
            'bank_name'           => $request->bank_name,
            'account_holder_name' => $request->account_holder_name,  // M1 fix
            'account_number'      => $request->account_number,
            'iban'                => $request->iban,
            'swift_code'          => $request->swift_code,
            'bank_country'        => $request->bank_country,
            'bank_currency'       => $request->bank_currency,
            'is_default'          => $request->boolean('is_default'),
            'status'              => 'pending_review', // M1 fix: was 'pending', DB enum is 'pending_review'
        ]);

        return back()->with('success', 'Payment method added. Awaiting admin review.');
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $client = $this->resolveClient();

        if ($paymentMethod->client_id !== $client->id) {
            abort(403, 'Unauthorized access to payment method.');
        }

        $request->validate([
            'is_default' => 'required|boolean',
        ]);

        if ($request->boolean('is_default')) {
            PaymentMethod::where('client_id', $client->id)->update(['is_default' => false]);
        }

        $paymentMethod->update(['is_default' => $request->boolean('is_default')]);

        return back()->with('success', 'Payment method updated.');
    }

    public function destroy(Request $request, PaymentMethod $paymentMethod)
    {
        $client = $this->resolveClient();

        if ($paymentMethod->client_id !== $client->id) {
            abort(403, 'Unauthorized access to payment method.');
        }

        $paymentMethod->delete();

        return back()->with('success', 'Payment method removed.');
    }

    // ── Admin Methods (M9 fix: requires tenant ownership) ─────────────

    public function approve(Request $request, PaymentMethod $paymentMethod)
    {
        // M9 fix: verify the payment method belongs to a client of this tenant
        $tenant = $this->resolveTenant();
        $this->authorizePaymentMethod($paymentMethod, $tenant);

        $paymentMethod->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Payment method approved.');
    }

    public function reject(Request $request, PaymentMethod $paymentMethod)
    {
        $request->validate([
            'rejection_note' => 'required|string|max:500',
        ]);

        // M9 fix: verify tenant ownership
        $tenant = $this->resolveTenant();
        $this->authorizePaymentMethod($paymentMethod, $tenant);

        $paymentMethod->update([
            'status'         => 'rejected',
            'rejection_note' => $request->rejection_note,
            'reviewed_by'    => Auth::id(),
            'reviewed_at'    => now(),
        ]);

        return back()->with('success', 'Payment method rejected.');
    }

    private function authorizePaymentMethod(PaymentMethod $paymentMethod, Tenant $tenant): void
    {
        $clientBelongsToTenant = TenantClient::where('tenant_id', $tenant->id)
            ->where('id', $paymentMethod->client_id)
            ->exists();

        if (!$clientBelongsToTenant) {
            abort(403, 'Unauthorized access to this payment method.');
        }
    }
}
