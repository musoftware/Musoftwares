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
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Http\Requests\Admin\User\ToggleBlockUserRequest;
use App\Http\Requests\Admin\User\AddTaskRequest;
use App\Http\Resources\UserResource;
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
        $query = User::query()->with('roles');

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
        // The old system defaulted to ASC sorting for users
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $direction);

        $users = $query->paginate(25)->withQueryString()->through(fn ($user) => (new UserResource($user))->resolve());

        $stats = [
            'total'           => User::count(),
            'active'          => User::where('account_status', 'active')->orWhereNull('account_status')->count(),
            'blocked'         => User::where('account_status', 'blocked')->count(),
            'kyc_verified'    => User::where('kyc_verified', true)->count(),
            'new_this_week'   => User::where('created_at', '>=', now()->subDays(7))->count(),
            'new_this_month'  => User::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'clients' => $users,
            'filters' => $request->only(['search', 'role', 'status', 'kyc', 'sort', 'direction']),
            'stats'   => $stats,
        ]);
    }

    /**
     * Show full user profile with stats.
     * Recovered from old UsersController::show()
     */
    public function show(Request $request, $id)
    {
        $user = User::with(['kycDocuments', 'kycVerifier:id,name', 'tickets', 'roles'])
            ->findOrFail($id);

        $initials = collect(explode(' ', $user->name))
            ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8'))
            ->take(2)
            ->implode('');

        $walletData = [
            'balance'  => (float) $user->available_balance(),
            'currency' => $user->currency_name(),
        ];

        $stats = [
            'tickets_total'  => $user->tickets()->count(),
            'tickets_open'   => $user->tickets()->where('ticket_status', 'open')->count(),
            'kyc_docs_count' => $user->kycDocuments()->count(),
        ];

        // Try ERP stats if ERP module exists, fallback to User model relations
        try {
            $erpClient = $user->client;
            if ($erpClient) {
                $stats['invoices_total'] = $erpClient->invoices()->count();
                $stats['invoices_paid']  = $erpClient->invoices()->where('status', 'paid')->count();
                $stats['invoices_unpaid_sum'] = $erpClient->invoices()->where('status', 'unpaid')->sum('amount');
            } else {
                $stats['invoices_total'] = $user->invoices()->count();
                $stats['invoices_paid']  = $user->invoices()->where('status', 'paid')->count();
                $stats['invoices_unpaid_sum'] = $user->invoices()->where('status', 'unpaid')->sum('amount');
            }
        } catch (\Throwable $e) {}

        // Try Marketplace stats
        try {
            if (class_exists('\Modules\Marketplace\Models\Order')) {
                $stats['orders_total'] = \Modules\Marketplace\Models\Order::where('buyer_id', $user->id)->count();
            }
            if (class_exists('\Modules\Marketplace\Models\Service')) {
                $stats['services_total'] = \Modules\Marketplace\Models\Service::where('seller_id', $user->id)->count();
                $stats['services_approved'] = \Modules\Marketplace\Models\Service::where('seller_id', $user->id)->where('status', 'approved')->count();
            }
        } catch (\Throwable $e) {}

        $userDetail = (new UserResource($user))->resolve();

        $transactions = $user->transactions()->latest()->take(10)->get()->map(function($tx) {
            return [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => $tx->amount,
                'description' => $tx->reason ?? $tx->type,
                'created_at' => $tx->created_at,
            ];
        });

        $modulePlans = \App\Models\ModulePlan::where('is_active', true)->get();

        return Inertia::render('Admin/Users/Show', [
            'client' => $userDetail,
            'stats'  => $stats,
            'modulePlans' => $modulePlans,
            'wallets' => [
                [
                    'id' => 'main',
                    'context' => 'Main Wallet',
                    'balance' => $walletData['balance'],
                    'currency' => $walletData['currency'],
                    'transactions' => $transactions,
                ]
            ],
        ]);
    }

    /**
     * Show create user form.
     */
    public function create()
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
    public function store(StoreUserRequest $request)
    {

        $this->adminUserService->createFromRequest($request);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show edit form for an existing user.
     */
    public function edit($id)
    {
        $user = User::with(['kycDocuments', 'roles'])->findOrFail($id);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id'                   => $user->id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'role'                 => $user->roles->first()?->name ?? 'user',
                'phone'                => $user->phone,
                'mobile_1'             => $user->mobile_1,
                'mobile_2'             => $user->mobile_2,
                'whatsapp_number'      => $user->whatsapp_number,
                'telegram_username'    => $user->telegram_username,
                'country'              => $user->country,
                'city'                 => $user->city,
                'currency'             => $user->currency_id,
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
    public function update(UpdateUserRequest $request, $id)
    {

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
    public function toggleBlock(ToggleBlockUserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        $this->adminUserService->toggleBlock($user, $request->input('reason'));

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

        $noWhatsApp = User::role('client')
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
    public function reset_password($id)
    {
        $user = User::findOrFail($id);
        $newPassword = $this->adminUserService->resetPassword($user);

        return response()->json([
            'new_password' => $newPassword,
            'message' => 'Password reset successfully.'
        ]);
    }

    public function referrals($id)
    {
        $client = User::findOrFail($id);
        return Inertia::render('Admin/Users/Referrals', [
            'client' => $client,
        ]);
    }

    public function files($id)
    {
        $client = User::findOrFail($id);
        return Inertia::render('Admin/Users/Files', [
            'client' => $client,
        ]);
    }

    public function reports($id)
    {
        $client = User::findOrFail($id);
        return Inertia::render('Admin/Users/Reports', [
            'client' => $client,
        ]);
    }

    public function add_task(AddTaskRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $this->adminUserService->addTask($user, $request->input('title'), $request->input('description'));

        return back()->with('success', 'Task created successfully.');
    }

    public function activateMembership(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'plan_id' => 'required|exists:module_plans,id',
            'duration_days' => 'required|integer|min:1',
        ]);

        $plan = \App\Models\ModulePlan::findOrFail($request->plan_id);

        \App\Models\UserSubscription::create([
            'client_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays($request->duration_days),
            'auto_renew' => false,
        ]);

        return back()->with('success', "Membership ({$plan->name}) activated successfully for {$request->duration_days} days.");
    }
}
