<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MissingExchangeRateException;
use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\Currency;
use App\Models\User;
use App\Services\ClientSystems\IntegrationPrompt;
use App\Services\ClientSystems\SystemBilling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The owner links a client's own system to a monthly price and/or a share of its sales,
 * then copies the integration prompt into an AI working on that system.
 */
class ClientSystemController extends Controller
{
    public function __construct(private readonly SystemBilling $billing) {}

    public function index(): Response
    {
        $systems = ClientSystem::with(['user:id,name,email', 'currency:id,currency'])->latest()->paginate(30);
        $systems->getCollection()->transform(fn (ClientSystem $system) => $system->summary());

        return Inertia::render('Admin/ClientSystems/Index', ['systems' => $systems]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules() + ['currency_id' => ['required', 'exists:currencies,id']]);
        $system = $user->clientSystems()->create($data + ['next_bill_at' => now()]);

        return $this->billNow($system, __('client_systems.created'));
    }

    public function show(ClientSystem $clientSystem, IntegrationPrompt $prompt): Response
    {
        $clientSystem->load(['user:id,name,email', 'currency:id,currency']);

        return Inertia::render('Admin/ClientSystems/Show', [
            'system' => $clientSystem->summary() + [
                'grace_days' => $clientSystem->grace_days,
                'open_until' => $clientSystem->open_until?->toDateString(),
                'manually_locked' => $clientSystem->manually_locked,
                'next_bill_at' => $clientSystem->next_bill_at?->toIso8601String(),
                'last_check_in_ip' => $clientSystem->last_check_in_ip,
                'fingerprint_changed_at' => $clientSystem->pending_fingerprint ? $clientSystem->fingerprint_changed_at?->toIso8601String() : null,
            ],
            'bills' => $clientSystem->bills()->with('invoice:id,status,final_total,unpaid')->latest('period_start')->limit(24)->get(),
            'sales' => $clientSystem->sales()->latest('sold_at')->limit(50)->get(['id', 'external_id', 'amount', 'status', 'sold_at', 'client_system_bill_id']),
            'prompt' => $prompt->render($clientSystem),
        ]);
    }

    public function update(Request $request, ClientSystem $clientSystem): RedirectResponse
    {
        $clientSystem->update($request->validate($this->rules()));

        return back()->with('success', __('client_systems.saved'));
    }

    /** Lets the system work until a date even with an unpaid bill (empty date removes it). */
    public function openUntil(Request $request, ClientSystem $clientSystem): RedirectResponse
    {
        $data = $request->validate(['open_until' => ['nullable', 'date', 'after:today']]);
        $clientSystem->update(['open_until' => isset($data['open_until']) ? \Illuminate\Support\Carbon::parse($data['open_until'])->endOfDay() : null]);

        return back()->with('success', __('client_systems.saved'));
    }

    public function toggleLock(ClientSystem $clientSystem): RedirectResponse
    {
        $clientSystem->update(['manually_locked' => ! $clientSystem->manually_locked]);

        return back()->with('success', __($clientSystem->manually_locked ? 'client_systems.locked_now' : 'client_systems.unlocked_now'));
    }

    /** The owner checked the change on the client side and accepts the new license files. */
    public function acceptFingerprint(ClientSystem $clientSystem): RedirectResponse
    {
        $clientSystem->forceFill(['fingerprint' => $clientSystem->pending_fingerprint, 'pending_fingerprint' => null, 'fingerprint_changed_at' => null])->save();

        return back()->with('success', __('client_systems.saved'));
    }

    public function rotateKeys(ClientSystem $clientSystem): RedirectResponse
    {
        $clientSystem->fillNewKeys();
        $clientSystem->forceFill(['fingerprint' => null, 'pending_fingerprint' => null, 'fingerprint_changed_at' => null])->save();

        return back()->with('success', __('client_systems.keys_rotated'));
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'sales_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:60'],
        ];
    }

    /** The first bill is made at once. Without an exchange rate it waits for the hourly run instead. */
    private function billNow(ClientSystem $system, string $message): RedirectResponse
    {
        try {
            $this->billing->billDue($system, now());
        } catch (MissingExchangeRateException $e) {
            Log::warning('ClientSystems: first bill waits for an exchange rate', ['client_system_id' => $system->id, 'error' => $e->getMessage()]);

            return redirect()->route('admin.client-systems.show', $system)->with('error', __('client_systems.bill_waits_for_rate'));
        }

        return redirect()->route('admin.client-systems.show', $system)->with('success', $message);
    }
}
