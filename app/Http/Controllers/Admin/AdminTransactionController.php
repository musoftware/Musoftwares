<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\AdminAuditLog;
use App\Models\AdminSettings;
use App\Models\CostTransaction;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminTransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function create(Request $request)
    {
        $userId = $request->query('user');
        $type = $request->query('type', 'receive'); // Default type for the UI

        if (! $userId) {
            return redirect()->route('admin.users.index')->with('danger', __('general.please_select_a_user_to_add_a_transaction'));
        }

        $user = User::with('projects')->findOrFail($userId);
        $project = null;
        if ($request->has('project')) {
            $project = $user->projects()->find($request->query('project'));
        }

        $exchanges = CurrenciesExchange::Today();
        if (count($exchanges) == 0) {
            $exchanges = CurrenciesExchange::whereIn('id', function ($query) {
                $query->select(\Illuminate\Support\Facades\DB::raw('MAX(id)'))
                    ->from('currencies_exchanges')
                    ->groupBy('currency1', 'currency2');
            })->get();
        }

        $hourRate = $user->hour_rate ?? 0;
        $recommendedHourRate = AdminSettings::GetRecommendedHourlyRate($user->currency);

        $userCurrency = CurrencyHelper::getFrontendCurrency($user->currency_id);
        $user->currency_obj = $userCurrency;
        $businessCurrency = CurrencyHelper::getBusinessCurrency();

        return Inertia::render('Admin/Transactions/Create', [
            'user' => $user,
            'selectedProject' => $project,
            'activeProjects' => $user->projects()->whereNotIn('status', ['Completed', 'Cancelled'])->get(),
            'type' => $type,
            'currencies' => array_values(Currency::as_array()),
            'businessCurrency' => $businessCurrency,
            'exchanges' => $exchanges,
            'hourRate' => $hourRate,
            'recommendedHourRate' => $recommendedHourRate,
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['user', 'project', 'currency', 'month', 'year', 'type']);
        $type = $filters['type'] ?? 'income'; // income, cost, revenue

        $filteredUser = null;
        if (! empty($filters['user'])) {
            $u = User::find($filters['user']);
            if ($u) {
                $filteredUser = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'avatar_url' => $u->avatar_url,
                    'available_balance' => $u->available_balance(),
                    'currency' => $u->currency,
                ];
            }
        }

        if ($type === 'income') {
            $transactions = $this->transactionService->getIncomeTransactions($filters)
                ->withQueryString()
                ->through(fn ($t) => (new TransactionResource($t))->resolve());

            return Inertia::render('Admin/Transactions/Income', [
                'transactions' => $transactions,
                'filters' => $filters,
                'filteredUser' => $filteredUser,
            ]);
        } elseif ($type === 'cost') {
            // Reusing TransactionResource or create CostTransactionResource if needed
            $transactions = $this->transactionService->getCostTransactions($filters)
                ->withQueryString()
                ->through(fn ($t) => (new TransactionResource($t))->resolve());

            return Inertia::render('Admin/Transactions/Cost', [
                'transactions' => $transactions,
                'filters' => $filters,
                'filteredUser' => $filteredUser,
            ]);
        } elseif ($type === 'revenue') {
            // Detailed revenue logic
            $income = $this->transactionService->getIncomeTransactions($filters);
            $cost = $this->transactionService->getCostTransactions($filters);

            return Inertia::render('Admin/Transactions/Revenue', [
                'income' => $income->through(fn ($t) => (new TransactionResource($t))->resolve()),
                'cost' => $cost->through(fn ($t) => (new TransactionResource($t))->resolve()),
                'filters' => $filters,
                'filteredUser' => $filteredUser,
                'businessCurrency' => CurrencyHelper::getBusinessCurrency(),
            ]);
        }

        return Inertia::render('Admin/Transactions/Index', [
            'filters' => $filters,
            'filteredUser' => $filteredUser,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user' => 'required|exists:users,id',
            'type' => 'required|in:timer-received,timer-due,out-timer-received,refund,earned,send,used',
            'data' => 'required|array',
        ]);

        $user = User::findOrFail($request->user);
        $project = $request->filled('project') ? $user->projects()->find($request->project) : null;

        if ($request->filled('project') && ! $project) {
            return redirect()->back()->with('danger', __('erp.project_not_associated_client'));
        }

        $addedCount = $this->transactionService->processTransactionBatch($request, $user, $project, $request->data, $request->type);

        return redirect()->back()->with('success', __('erp.transactions_added_successfully', ['count' => $addedCount]));
    }

    public function reverse(Transaction $transaction)
    {
        try {
            $reverse = $this->transactionService->reverseTransaction($transaction);

            return redirect()->back()->with('success', "Transaction #{$transaction->id} reversed successfully. New transaction ID: #{$reverse->id}");
        } catch (\Exception $e) {
            return redirect()->back()->with('danger', 'Error reversing transaction: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        $type = $request->query('type', 'income');

        try {
            if ($type === 'cost') {
                $transaction = CostTransaction::findOrFail($id);
            } else {
                $transaction = Transaction::findOrFail($id);
            }

            $user = User::find($transaction->user_id ?? $transaction->client_id);
            $project = Project::find($transaction->project_id);

            $transaction->delete();

            if ($user) {
                $this->transactionService->recalculateUserBalance($user, $project);
            }

            return redirect()->back()->with('success', __('general.deleted_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('danger', __('general.error_occurred').': '.$e->getMessage());
        }
    }

    public function regenerate(Request $request, $user_id)
    {
        $user = User::findOrFail($user_id);
        $project = $request->filled('project') ? $user->projects()->find($request->project) : null;

        $this->transactionService->recalculateUserBalance($user, $project);

        return redirect()->back()->with('success', __('general.balances_recalculated_successfully'));
    }

    public function transfer(Request $request)
    {
        $userId = $request->query('user');
        if (! $userId) {
            return redirect()->route('admin.transactions.index')->with('danger', __('general.please_select_a_user_to_add_a_transaction'));
        }

        $user = User::with('projects')->findOrFail($userId);

        $exchanges = CurrenciesExchange::Today();
        if (count($exchanges) == 0) {
            $exchanges = CurrenciesExchange::whereIn('id', function ($query) {
                $query->select(\Illuminate\Support\Facades\DB::raw('MAX(id)'))
                    ->from('currencies_exchanges')
                    ->groupBy('currency1', 'currency2');
            })->get();
        }

        return Inertia::render('Admin/Transactions/Transfer', [
            'user' => $user,
            'activeProjects' => $user->projects()->whereNotIn('status', ['Completed', 'Cancelled'])->get(),
            'currencies' => array_values(Currency::as_array()),
            'exchanges' => $exchanges,
        ]);
    }

    public function start_transfer(Request $request)
    {
        $request->validate([
            'user' => 'required|exists:users,id',
            'data' => 'required|array',
        ]);

        $actorId = Auth::id();
        $userId = (int) $request->input('user');
        $data = $request->input('data');

        // Lock the user row for the entire transfer batch so concurrent
        // transfers / balance mutations cannot interleave between the
        // refunded-leg and the received-leg, and cannot race with balance
        // recompute. Lock first, validate second.
        DB::transaction(function () use ($userId, $data) {
            $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();

            foreach ($data as $item) {
                if (empty($item['amount']) || (float) $item['amount'] == 0.0) {
                    continue;
                }

                $this->transfer_function($user, $item, $item['source_project_id'], 'refunded');
                $this->transfer_function($user, $item, $item['target_project_id'], 'received');
            }

            // Re-fetch to read latest mutated state under the lock, then
            // recompute the balance inside the same transaction.
            $this->transactionService->recalculateUserBalance($user, null);

            app(AdminAuditService::class)->recordRaw(
                'transaction.transfer',
                'User',
                (string) $user->id,
                [
                    'actor_user_id' => $actorId,
                    'rows' => count($data),
                ],
                AdminAuditLog::SEVERITY_WARNING
            );
        });

        return redirect()->back()->with('success', __('erp.transfer_completed_successfully'));
    }

    private function transfer_function($client, $item, $projectId, $type)
    {
        // Canonical sign convention: $type='refunded' ⇒ negative; 'received' ⇒ positive.
        // This enforces a single source of truth regardless of the sign the
        // client submitted; sign-flip on the way in is the most common
        // source of double-credit / double-debit bugs.
        $rawAmount = (float) ($item['amount'] ?? 0);

        $transaction = new Transaction;
        $transaction->amount = $type === 'refunded'
            ? -1 * abs($rawAmount)
            : abs($rawAmount);

        if (! empty($item['reason'])) {
            $transaction->reason = $item['reason'];
        }

        $transaction->type = $type;
        $transaction->currency_id = $item['currency'];

        $exist_project = Project::find($projectId);
        if ($exist_project !== null) {
            $transaction->project_id = $exist_project->id;
        }

        $client->client_balance()->save($transaction);
    }

    public function current_timer(Request $request)
    {
        $client = User::find($request->input('user'));
        if (! $client) {
            return response()->json(['status' => false]);
        }
        $project_id = $request->input('project');
        $currency = $request->input('currency');

        if (! empty($project_id)) {
            $project = $client->projects()->find($project_id);
            if (! $project) {
                return response()->json(['status' => false]);
            }

            $amount = Transaction::where('project_id', $project->id)
                ->where('currency_id', $currency)
                ->sum('amount');

            return response()->json([
                'status' => true,
                'timer' => round($amount, 3),
            ]);

        } else {
            $projects = $client->projects()->get();
            $proj = [];

            foreach ($projects as $project) {
                $amount = Transaction::where('project_id', $project->id)
                    ->where('currency_id', $currency)
                    ->sum('amount');

                if (round($amount, 3) == 0) {
                    continue;
                }

                $proj[] = [
                    'id' => $project->id,
                    'name' => $project->project_name,
                    'timer' => round($amount, 3),
                ];
            }

            return response()->json(['status' => true, 'timers' => $proj]);
        }
    }
}
