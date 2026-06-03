<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\AdminSettings;
use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Services\TransactionService;
use App\Http\Resources\TransactionResource;
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

        if (!$userId) {
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

        $userCurrency = \App\Helpers\CurrencyHelper::getFrontendCurrency($user->currency_id);
        $user->currency_obj = $userCurrency;
        $businessCurrency = \App\Helpers\CurrencyHelper::getBusinessCurrency();

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

        if ($type === 'income') {
            $transactions = $this->transactionService->getIncomeTransactions($filters)
                                 ->withQueryString()
                                 ->through(fn($t) => (new TransactionResource($t))->resolve());
            
            return Inertia::render('Admin/Transactions/Income', [
                'transactions' => $transactions,
                'filters' => $filters,
            ]);
        } elseif ($type === 'cost') {
            // Reusing TransactionResource or create CostTransactionResource if needed
            $transactions = $this->transactionService->getCostTransactions($filters)
                                 ->withQueryString()
                                 ->through(fn($t) => (new TransactionResource($t))->resolve());
            
            return Inertia::render('Admin/Transactions/Cost', [
                'transactions' => $transactions,
                'filters' => $filters,
            ]);
        } elseif ($type === 'revenue') {
            // Detailed revenue logic
            $income = $this->transactionService->getIncomeTransactions($filters);
            $cost = $this->transactionService->getCostTransactions($filters);
            
            return Inertia::render('Admin/Transactions/Revenue', [
                'income' => $income->through(fn($t) => (new TransactionResource($t))->resolve()),
                'cost' => $cost->through(fn($t) => (new TransactionResource($t))->resolve()),
                'filters' => $filters,
                'businessCurrency' => \App\Helpers\CurrencyHelper::getBusinessCurrency(),
            ]);
        }

        return Inertia::render('Admin/Transactions/Index', [
            'filters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user' => 'required|exists:users,id',
            'type' => 'required|in:timer-received,timer-due,out-timer-received,refund,earned,send',
            'data' => 'required|array',
        ]);

        $user = User::findOrFail($request->user);
        $project = $request->filled('project') ? $user->projects()->find($request->project) : null;

        if ($request->filled('project') && !$project) {
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
            return redirect()->back()->with('danger', 'Error reversing transaction: ' . $e->getMessage());
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
        if (!$userId) {
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

        $user = User::findOrFail($request->input('user'));
        $data = $request->input('data');

        foreach ($data as $item) {
            if (empty($item['amount']) || $item['amount'] == 0) continue;

            $this->transfer_function($user, $item, $item['source_project_id'], 'refunded');
            $this->transfer_function($user, $item, $item['target_project_id'], 'received');
        }
        
        // Optionally update balances
        $this->transactionService->recalculateUserBalance($user);

        return redirect()->back()->with('success', __('erp.transfer_completed_successfully'));
    }

    private function transfer_function($client, $item, $projectId, $type)
    {
        $transaction = new Transaction();

        if ($type == 'refunded') {
            $transaction->amount = -1 * abs($item['amount']);
        } else {
            $transaction->amount = abs($item['amount']);
        }

        if (!empty($item['reason'])) {
            $transaction->reason = $item['reason'];
        }

        $transaction->type = $type;
        $transaction->currency_id = $item['currency'];

        $exist_project = Project::find($projectId);
        if ($exist_project !== null) {
            $transaction->project_id = $exist_project->id;
            // Removed date_start setting as it's not strictly necessary for transfer logic alone
        }

        $client->client_balance()->save($transaction);
    }

    public function current_timer(Request $request)
    {
        $client = User::find($request->input('user'));
        if (!$client) {
            return response()->json(['status' => false]);
        }
        $project_id = $request->input('project');
        $currency = $request->input('currency');

        if (!empty($project_id)) {
            $project = $client->projects()->find($project_id);
            if (!$project) {
                return response()->json(['status' => false]);
            }

            $amount = Transaction::where('project_id', $project->id)
                ->where('currency_id', $currency)
                ->sum('amount');

            return response()->json([
                'status' => true,
                'timer' => round($amount , 3)
            ]);

        } else {
            $projects = $client->projects()->get();
            $proj = array();

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
                    'timer' => round($amount, 3)
                ];
            }

            return response()->json(['status' => true, 'timers' => $proj]);
        }
    }
}
