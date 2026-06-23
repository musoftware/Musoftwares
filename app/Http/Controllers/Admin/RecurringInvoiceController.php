<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\RecurringInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecurringInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = RecurringInvoice::with('user')->latest()->paginate(50);
        $invoices->getCollection()->transform(function ($invoice) {
            $invoice->currency = \App\Helpers\CurrencyHelper::getFrontendCurrency($invoice->currency_id);
            return $invoice;
        });
        
        $currencies = Currency::all();
        $users = User::select('id', 'name', 'email')->get();

        return Inertia::render('Admin/Business/RecurringInvoices/Index', [
            'invoices' => $invoices,
            'currencies' => $currencies,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'recurring_times' => 'required|integer|min:1',
            'recurring' => 'required|in:day,week,month,year',
            'currency' => 'required|exists:currencies,id',
        ]);

        $invoice = new RecurringInvoice();
        $invoice->user_id = (int) $request->input('user_id');
        $invoice->title = $request->input('title');
        $invoice->start_date = $request->input('start_date');
        $invoice->current_date = $request->input('start_date');
        $invoice->amount = (float) $request->input('amount');
        $invoice->currency_id = (int) $request->input('currency');
        $invoice->recurring = $request->input('recurring');
        $invoice->recurring_times = (int) $request->input('recurring_times');

        if ($request->input('recurring') === 'week') {
            $request->validate(['recurring_times_week' => 'required']);
            $invoice->recurring_times_week = implode(',', $request->input('recurring_times_week', []));
        }
        if ($request->input('recurring') === 'month') {
            $request->validate(['recurring_times_month' => 'required']);
            $invoice->recurring_times_month = implode(',', $request->input('recurring_times_month', []));
        }
        if ($request->input('recurring') === 'year') {
            $request->validate(['recurring_times_year' => 'required']);
            $invoice->recurring_times_year = implode(',', $request->input('recurring_times_year', []));
        }

        $invoice->save();
        $invoice->apply();

        return redirect()->route('admin.recurring_invoices.index')->with('success', __('general.recurring_invoice_added_successfully'));
    }

    public function edit($id)
    {
        $invoice = RecurringInvoice::findOrFail($id);
        $currencies = Currency::all();
        $users = User::select('id', 'name', 'email')->get();

        return Inertia::render('Admin/Business/RecurringInvoices/Edit', [
            'invoice' => [
                'id' => $invoice->id,
                'user_id' => $invoice->user_id,
                'title' => $invoice->title,
                'amount' => $invoice->amount,
                'currency' => $invoice->currency_id,
                'start_date' => $invoice->start_date,
                'recurring' => $invoice->recurring,
                'recurring_times' => $invoice->recurring_times,
                'recurring_times_week' => $invoice->recurring_times_week ? explode(',', $invoice->recurring_times_week) : [],
                'recurring_times_month' => $invoice->recurring_times_month ? array_map('intval', explode(',', $invoice->recurring_times_month)) : [],
                'recurring_times_year' => $invoice->recurring_times_year ? explode(',', $invoice->recurring_times_year) : [],
            ],
            'currencies' => $currencies,
            'users' => $users,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'recurring_times' => 'required|integer|min:1',
            'recurring' => 'required|in:day,week,month,year',
            'currency' => 'required|exists:currencies,id',
        ]);

        $invoice = RecurringInvoice::findOrFail($id);
        $invoice->user_id = (int) $request->input('user_id');
        $invoice->title = $request->input('title');
        $invoice->start_date = $request->input('start_date');
        $invoice->current_date = $request->input('start_date');
        $invoice->amount = (float) $request->input('amount');
        $invoice->currency_id = (int) $request->input('currency');
        $invoice->recurring = $request->input('recurring');
        $invoice->recurring_times = (int) $request->input('recurring_times');

        $invoice->recurring_times_week = null;
        $invoice->recurring_times_month = null;
        $invoice->recurring_times_year = null;

        if ($request->input('recurring') === 'week') {
            $request->validate(['recurring_times_week' => 'required']);
            $invoice->recurring_times_week = implode(',', $request->input('recurring_times_week', []));
        }
        if ($request->input('recurring') === 'month') {
            $request->validate(['recurring_times_month' => 'required']);
            $invoice->recurring_times_month = implode(',', $request->input('recurring_times_month', []));
        }
        if ($request->input('recurring') === 'year') {
            $request->validate(['recurring_times_year' => 'required']);
            $invoice->recurring_times_year = implode(',', $request->input('recurring_times_year', []));
        }

        $invoice->save();

        return redirect()->route('admin.recurring_invoices.index')->with('success', __('general.recurring_invoice_updated_successfully'));
    }

    public function view($id)
    {
        $invoice = RecurringInvoice::with('user')->findOrFail($id);
        $records = $invoice->records()->with('invoice')->latest()->get()->map(function ($record) {
            $actualInvoice = $record->invoice;
            return [
                'id' => $record->id,
                'created_at' => $record->created_at,
                'amount' => $actualInvoice ? $actualInvoice->total() : null,
                'currency' => $actualInvoice ? \App\Helpers\CurrencyHelper::getFrontendCurrency($actualInvoice->currency_id) : null,
                'status' => $actualInvoice ? $actualInvoice->status : null,
                'invoice_id' => $actualInvoice ? $actualInvoice->id : null,
            ];
        });

        // Compute 15 upcoming schedule dates
        $upcomingSchedule = [];
        $count = 0;
        $baseDate = Carbon::parse($invoice->current_date);
        for ($i = 0; $i <= 365 && $count < 15; $i++) {
            $checkDate = $baseDate->copy()->addDays($i);
            if ($invoice->isToday($checkDate)) {
                $count++;
                $uniqueId = $invoice->id . '-' . $checkDate->toDateString();
                $isRecorded = $invoice->createdBefore($checkDate);

                // Fetch actual generated invoice info if this date was already executed
                $actualAmountStr = null;
                if ($isRecorded) {
                    $record = \Illuminate\Support\Facades\DB::table('recurring_invoice_records')
                        ->where('unique_id', $uniqueId)
                        ->first();
                    if ($record && $record->invoice_id) {
                        $actualInv = \App\Models\Invoice::find($record->invoice_id);
                        if ($actualInv) {
                            $actualAmountStr = \App\Helpers\FinanceHelper::instance()->format_money($actualInv->total(), $actualInv->currency_id ?? $invoice->currency_id);
                        }
                    }
                }

                $upcomingSchedule[] = [
                    'date' => $checkDate->toDateString(),
                    'amount_str' => $actualAmountStr ?? $invoice->current_amount_str(),
                    'is_actual' => $actualAmountStr !== null,
                    'recorded' => $isRecorded,
                ];
            }
        }

        $currencyModel = \App\Helpers\CurrencyHelper::getFrontendCurrency($invoice->currency_id);

        return Inertia::render('Admin/Business/RecurringInvoices/View', [
            'invoice' => [
                'id' => $invoice->id,
                'user' => $invoice->user ? ['id' => $invoice->user->id, 'name' => $invoice->user->name, 'email' => $invoice->user->email] : null,
                'title' => $invoice->title,
                'amount' => $invoice->amount,
                'currency' => $currencyModel,
                'start_date' => $invoice->start_date,
                'current_date' => $invoice->current_date,
                'recurring' => $invoice->recurring,
                'recurring_times' => $invoice->recurring_times,
                'details' => $invoice->details(),
            ],
            'records' => $records,
            'upcomingSchedule' => $upcomingSchedule,
            'total_stat' => [
                'entries_count' => $invoice->records()->count(),
            ]
        ]);
    }

    public function delete($id)
    {
        $invoice = RecurringInvoice::findOrFail($id);
        $invoice->delete();
        return redirect()->route('admin.recurring_invoices.index')->with('success', __('general.recurring_invoice_deleted'));
    }

    public function toggle($id)
    {
        $invoice = RecurringInvoice::findOrFail($id);
        $invoice->is_active = !$invoice->is_active;
        $invoice->save();
        return redirect()->back()->with('success', __('general.status_updated_successfully'));
    }
}
