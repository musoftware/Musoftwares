<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UsersController extends Controller
{
    public function __construct(
        protected AdminUserService $adminUserService
    ) {}

    /**
     * Full user listing with search, role filter, top-users filter, sort.
     * Recovered from old UsersController::index()
     */
    public function index(Request $request): InertiaResponse
    {
        $query = User::query()
            ->select('id', 'name', 'email', 'role', 'account_status', 'created_at',
                     'whatsapp_number', 'preferred_currency', 'kyc_verified',
                     'last_activity_at', 'onboarding_completed');

        // Full-text search across name, email, whatsapp
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('whatsapp_number', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($role = $request->get('role')) {
            $query->role($role); // Spatie HasRoles scope
        }

        // Account status filter
        if ($status = $request->get('status')) {
            $query->where('account_status', $status);
        }

        // KYC filter
        if ($request->get('kyc') === 'verified') {
            $query->where('kyc_verified', true);
        } elseif ($request->get('kyc') === 'unverified') {
            $query->where('kyc_verified', false);
        }

        // Sorting
        $sortable  = ['name', 'email', 'created_at', 'id', 'last_activity_at'];
        $sort      = in_array($request->get('sort'), $sortable) ? $request->get('sort') : 'id';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction);

        $users = $query->paginate(25)->withQueryString()->through(function ($user) {
            return [
                'id'                   => $user->id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'role'                 => $user->role,
                'account_status'       => $user->account_status ?? 'active',
                'kyc_verified'         => (bool) $user->kyc_verified,
                'preferred_currency'   => $user->preferred_currency ?? 'USD',
                'whatsapp_number'      => $user->whatsapp_number,
                'created_at'           => $user->created_at,
                'last_activity_at'     => $user->last_activity_at,
                'onboarding_completed' => (bool) $user->onboarding_completed,
            ];
        });

        $stats = [
            'total'           => User::count(),
            'active'          => User::where('account_status', 'active')->orWhereNull('account_status')->count(),
            'blocked'         => User::where('account_status', 'blocked')->count(),
            'kyc_verified'    => User::where('kyc_verified', true)->count(),
            'new_this_week'   => User::where('created_at', '>=', now()->subDays(7))->count(),
            'new_this_month'  => User::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'filters' => $request->only(['search', 'role', 'status', 'kyc', 'sort', 'direction']),
            'stats'   => $stats,
        ]);
    }

    /**
     * Show full user profile with stats.
     * Recovered from old UsersController::show()
     */
    public function show($id): InertiaResponse
    {
        $user = User::with(['kycDocuments', 'kycVerifier:id,name', 'supportTickets'])
            ->findOrFail($id);

        $initials = collect(explode(' ', $user->name))
            ->map(fn($w) => strtoupper(substr($w, 0, 1)))
            ->take(2)
            ->implode('');

        $walletData = null;
        try {
            $wallet     = $user->getWallet();
            $walletData = [
                'balance'  => (float) $wallet->balance,
                'currency' => $wallet->currency,
            ];
        } catch (\Throwable $e) {}

        $stats = [
            'tickets_total'  => $user->supportTickets()->count(),
            'tickets_open'   => $user->supportTickets()->where('status', 'open')->count(),
            'kyc_docs_count' => $user->kycDocuments()->count(),
        ];

        // Try ERP stats if ERP module exists
        try {
            $erpClient = $user->client;
            if ($erpClient) {
                $stats['invoices_total'] = $erpClient->invoices()->count();
                $stats['invoices_paid']  = $erpClient->invoices()->where('status', 'paid')->count();
            }
        } catch (\Throwable $e) {}

        $userDetail = [
            'id'                   => $user->id,
            'name'                 => $user->name,
            'email'                => $user->email,
            'initials'             => $initials,
            'role'                 => $user->role,
            'account_status'       => $user->account_status ?? 'active',
            'block_reason'         => $user->block_reason,
            'email_verified_at'    => $user->email_verified_at,
            'created_at'           => $user->created_at,
            'last_activity_at'     => $user->last_activity_at,
            'phone'                => $user->phone,
            'mobile_1'             => $user->mobile_1,
            'mobile_2'             => $user->mobile_2,
            'whatsapp_number'      => $user->whatsapp_number,
            'telegram_username'    => $user->telegram_username,
            'country'              => $user->country,
            'city'                 => $user->city,
            'preferred_currency'   => $user->preferred_currency ?? 'USD',
            'onboarding_completed' => (bool) $user->onboarding_completed,
            'kyc_verified'         => (bool) $user->kyc_verified,
            'kyc_verified_at'      => $user->kyc_verified_at,
            'kyc_verified_by'      => $user->kycVerifier?->name,
            'kyc_notes'            => $user->kyc_notes,
            'kyc_documents'        => $user->kycDocuments->map(fn($d) => [
                'id'          => $d->id,
                'type'        => $d->document_type,
                'status'      => $d->status,
                'uploaded_at' => $d->created_at,
            ]),
        ];

        return Inertia::render('Admin/Users/Show', [
            'user'   => $userDetail,
            'stats'  => $stats,
            'wallet' => $walletData,
        ]);
    }

    /**
     * Show create user form.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Users/Create', [
            'roles'      => ['admin', 'client'],
            'currencies' => ['USD', 'EUR', 'GBP', 'EGP', 'AED', 'SAR'],
        ]);
    }

    /**
     * Store a new user.
     * Business rule: name must include a last name.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'required|email|unique:users,email',
            'role'               => 'required|in:admin,client',
            'preferred_currency' => 'nullable|string|size:3',
        ]);

        // Enforce last-name requirement (recovered from old project)
        if (!str_contains(trim($request->input('name')), ' ')) {
            throw ValidationException::withMessages([
                'name' => ['Full name must include a last name.'],
            ]);
        }

        $this->adminUserService->createFromRequest($request);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show edit form for an existing user.
     */
    public function edit($id): InertiaResponse
    {
        $user = User::with(['kycDocuments'])->findOrFail($id);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id'                   => $user->id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'role'                 => $user->role,
                'phone'                => $user->phone,
                'mobile_1'             => $user->mobile_1,
                'mobile_2'             => $user->mobile_2,
                'whatsapp_number'      => $user->whatsapp_number,
                'telegram_username'    => $user->telegram_username,
                'country'              => $user->country,
                'city'                 => $user->city,
                'preferred_currency'   => $user->preferred_currency ?? 'USD',
                'account_status'       => $user->account_status ?? 'active',
                'block_reason'         => $user->block_reason,
                'kyc_verified'         => (bool) $user->kyc_verified,
                'kyc_notes'            => $user->kyc_notes,
            ],
            'roles'      => ['admin', 'client'],
            'currencies' => ['USD', 'EUR', 'GBP', 'EGP', 'AED', 'SAR'],
            'statuses'   => ['active', 'blocked', 'suspended'],
        ]);
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($id)],
            'role'  => 'nullable|in:admin,client',
        ]);

        $user = User::findOrFail($id);
        $this->adminUserService->updateFromRequest($user, $request);

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete a user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted.');
    }

    /**
     * Toggle block/unblock a user.
     * Recovered from old project's account_status workflow.
     */
    public function toggleBlock(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->account_status === 'blocked') {
            $user->account_status = 'active';
            $user->block_reason   = null;
        } else {
            $request->validate(['reason' => 'nullable|string|max:500']);
            $user->account_status = 'blocked';
            $user->block_reason   = $request->input('reason');
        }

        $user->save();

        return back()->with('success', $user->account_status === 'active'
            ? 'User unblocked.'
            : 'User blocked.');
    }

    /**
     * Impersonate a user — generate a token and redirect to impersonation endpoint.
     * Recovered from old project: UsersController::login_as()
     */
    public function loginAs($id)
    {
        $user  = User::findOrFail($id);
        $token = $user->createToken('admin-impersonation-' . Auth::id())->plainTextToken;

        // Store the impersonation token in session so the impersonated session can be detected
        session(['impersonating_user_id' => $user->id, 'impersonated_by' => Auth::id()]);

        return Inertia::render('Admin/Users/Impersonate', [
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'token' => $token,
        ]);
    }

    /**
     * Users with problematic status: unpaid invoices or blocked with no contact.
     * Recovered from old project: UsersController::problematic()
     */
    public function problematic(): InertiaResponse
    {
        $blocked = User::where('account_status', 'blocked')
            ->latest()
            ->get()
            ->map(fn($u) => [
                'id'             => $u->id,
                'name'           => $u->name,
                'email'          => $u->email,
                'account_status' => $u->account_status,
                'block_reason'   => $u->block_reason,
                'created_at'     => $u->created_at,
            ]);

        $noWhatsApp = User::where('role', 'client')
            ->where(fn($q) => $q->whereNull('whatsapp_number')->orWhere('whatsapp_number', ''))
            ->latest()
            ->get()
            ->map(fn($u) => [
                'id'             => $u->id,
                'name'           => $u->name,
                'email'          => $u->email,
                'whatsapp_number'=> $u->whatsapp_number,
                'created_at'     => $u->created_at,
            ]);

        return Inertia::render('Admin/Users/Problematic', [
            'blocked_users'       => $blocked,
            'no_whatsapp_users'   => $noWhatsApp,
        ]);
    }
}
