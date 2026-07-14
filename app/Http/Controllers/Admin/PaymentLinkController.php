<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PaymentLinkController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentLink::with(['currency', 'user', 'client']);

        $this->applyTenantScope($query);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('title', 'like', $like)
                    ->orWhere('uuid', 'like', $like)
                    ->orWhere('amount', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like))
                    ->orWhereHas('client', fn ($u) => $u->where('name', 'like', $like))
                    ->orWhereHas('currency', fn ($c) => $c->where('currency', 'like', $like));
            });
        }

        if ($status = $request->query('status')) {
            if (in_array($status, [
                PaymentLink::STATUS_PENDING,
                PaymentLink::STATUS_PAID,
                PaymentLink::STATUS_CANCELLED,
                PaymentLink::STATUS_EXPIRED,
            ], true)) {
                $query->where('status', $status);
            }
        }

        if ($currencyId = $request->query('currency_id')) {
            $query->where('currency_id', $currencyId);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(5, min($perPage, 100));

        $paymentLinks = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        $stats = $this->buildStats($request);

        $currencies = Currency::all();

        $clients = $this->loadClientList();

        return Inertia::render('Admin/Finance/PaymentLinks/Index', [
            'paymentLinks' => $paymentLinks,
            'currencies' => $currencies,
            'clients' => $clients,
            'filters' => $request->only(['search', 'status', 'currency_id', 'date_from', 'date_to', 'per_page']),
            'stats' => $stats,
            'canForceMarkPaid' => Auth::user()?->hasAnyRole(['super_admin', 'superadmin']) ?? false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'client_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'expires_at' => 'nullable|date|after:now',
        ]);

        PaymentLink::create([
            'user_id' => Auth::id(),
            'client_id' => $validated['client_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'],
            'currency_id' => $validated['currency_id'],
            'status' => PaymentLink::STATUS_PENDING,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()->back()->with('success', __('admin.payment_link_created', ['default' => 'Payment link created successfully.']));
    }

    public function update(Request $request, PaymentLink $paymentLink)
    {
        $this->authorize('update', $paymentLink);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $paymentLink->update($validated);

        return redirect()->back()->with('success', __('admin.payment_link_updated', ['default' => 'Payment link updated.']));
    }

    public function cancel(PaymentLink $paymentLink)
    {
        $this->authorize('cancel', $paymentLink);

        if (! $paymentLink->markCancelled()) {
            return redirect()->back()->with('error', __('admin.payment_link_cancel_failed', ['default' => 'Only pending payment links can be cancelled.']));
        }

        return redirect()->back()->with('success', __('admin.payment_link_cancelled', ['default' => 'Payment link cancelled.']));
    }

    public function markPaid(PaymentLink $paymentLink)
    {
        $this->authorize('forceMarkPaid', $paymentLink);

        if ($paymentLink->markPaid(PaymentLink::METHOD_MANUAL, null, Auth::id())) {
            return redirect()->back()->with('success', __('admin.payment_link_marked_paid', ['default' => 'Payment link marked as paid.']));
        }

        return redirect()->back()->with('error', __('admin.payment_link_already_paid', ['default' => 'Payment link is already paid.']));
    }

    public function destroy(PaymentLink $paymentLink)
    {
        $this->authorize('delete', $paymentLink);

        $paymentLink->delete();

        return redirect()->back()->with('success', __('admin.deleted_successfully'));
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:payment_links,id',
        ]);

        $query = PaymentLink::query();
        $this->applyTenantScope($query);
        $deleted = $query->whereIn('id', $validated['ids'])->delete();

        return redirect()->back()->with('success', __('admin.bulk_deleted', ['count' => $deleted, 'default' => "{$deleted} payment links deleted."]));
    }

    protected function applyTenantScope($query): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        if ($user->isAdmin()) {
            return;
        }

        $query->where('user_id', $user->id);
    }

    protected function buildStats(Request $request): array
    {
        $base = PaymentLink::query();
        $this->applyTenantScope($base);

        if ($currencyId = $request->query('currency_id')) {
            $base->where('currency_id', $currencyId);
        }

        return [
            'total' => (clone $base)->count(),
            'paid' => (clone $base)->where('status', PaymentLink::STATUS_PAID)->count(),
            'pending' => (clone $base)->where('status', PaymentLink::STATUS_PENDING)->count(),
            'cancelled' => (clone $base)->where('status', PaymentLink::STATUS_CANCELLED)->count(),
            'expired' => (clone $base)->where('status', PaymentLink::STATUS_PENDING)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->count(),
        ];
    }

    protected function loadClientList(): array
    {
        if (! Auth::user()?->isAdmin()) {
            return [];
        }

        return User::query()
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
            ->all();
    }
}