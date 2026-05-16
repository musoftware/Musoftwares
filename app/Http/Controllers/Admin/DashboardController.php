<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Invoice;
use Modules\Marketplace\Models\Order;
use Modules\Core\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalClients = User::where('role', 'client')->count();
        $activeTenants = Tenant::where('is_active', true)->count();

        $revenueThisMonth = DB::table('journal_entry_lines')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('ledgers', 'accounts.ledger_id', '=', 'ledgers.id')
            ->where('ledgers.type', 'revenue')
            ->whereMonth('journal_entry_lines.created_at', now()->month)
            ->whereYear('journal_entry_lines.created_at', now()->year)
            ->sum('journal_entry_lines.business_amount');

        $recentInvoices = Invoice::with('client')
            ->latest()
            ->take(5)
            ->get();

        $recentOrders = Order::with(['buyer', 'seller'])
            ->latest()
            ->take(5)
            ->get();

        $recentWithdrawals = WalletTransaction::where('type', 'debit')
            ->where('description', 'like', '%withdraw%')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalClients' => $totalClients,
                'activeTenants' => $activeTenants,
                'revenueThisMonth' => $revenueThisMonth,
            ],
            'recentInvoices' => $recentInvoices,
            'recentOrders' => $recentOrders,
            'recentWithdrawals' => $recentWithdrawals,
        ]);
    }
}
