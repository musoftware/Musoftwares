<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\AdminSettings;
use App\Models\Currency;
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
            return redirect()->route('admin.users.index')->with('danger', 'Please select a user to add a transaction.');
        }

        $user = User::with('projects')->findOrFail($userId);
        $project = null;
        if ($request->has('project')) {
            $project = $user->projects()->find($request->query('project'));
        }

        return Inertia::render('Admin/Transactions/Create', [
            'user' => $user,
            'selectedProject' => $project,
            'type' => $type,
            'currencies' => Currency::as_array(),
            'businessCurrency' => Currency::find(AdminSettings::business_currency()),
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['user', 'project', 'currency', 'month', 'year', 'type']);
        $type = $filters['type'] ?? 'income'; // income, cost, revenue

        if ($type === 'income') {
            $transactions = $this->transactionService->getIncomeTransactions($filters)
                                 ->withQueryString()
                                 ->through(fn($t) => clone (new TransactionResource($t))->resolve());
            
            return Inertia::render('Admin/Transactions/Income', [
                'transactions' => $transactions,
                'filters' => $filters,
            ]);
        } elseif ($type === 'cost') {
            // Reusing TransactionResource or create CostTransactionResource if needed
            $transactions = $this->transactionService->getCostTransactions($filters)
                                 ->withQueryString()
                                 ->through(fn($t) => clone (new TransactionResource($t))->resolve());
            
            return Inertia::render('Admin/Transactions/Cost', [
                'transactions' => $transactions,
                'filters' => $filters,
            ]);
        } elseif ($type === 'revenue') {
            // Detailed revenue logic
            $income = $this->transactionService->getIncomeTransactions($filters);
            $cost = $this->transactionService->getCostTransactions($filters);
            
            return Inertia::render('Admin/Transactions/Revenue', [
                'income' => $income->through(fn($t) => clone (new TransactionResource($t))->resolve()),
                'cost' => $cost->through(fn($t) => clone (new TransactionResource($t))->resolve()),
                'filters' => $filters,
                'businessCurrency' => Currency::find(AdminSettings::business_currency()),
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
            return response()->json([
                'status' => false, 
                'message' => 'Project is not associated to this client',
            ], 403);
        }

        $addedCount = $this->transactionService->processTransactionBatch($request, $user, $project, $request->data, $request->type);

        return response()->json(['status' => true, 'added' => $addedCount]);
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

        return redirect()->back()->with('success', 'Balances recalculated successfully.');
    }
}
