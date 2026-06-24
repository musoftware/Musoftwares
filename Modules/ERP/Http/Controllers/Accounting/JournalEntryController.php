<?php

namespace Modules\ERP\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Accounting\JournalEntry;
use Modules\ERP\Services\Accounting\AccountingService;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $entries = JournalEntry::with('lines.chartOfAccount')->orderByDesc('entry_date')->get();
        return Inertia::render('ERP/Accounting/JournalEntries/Index', [
            'entries' => $entries
        ]);
    }

    public function post(Request $request, JournalEntry $journal_entry, AccountingService $service)
    {
        try {
            $service->postEntry($journal_entry);
            return redirect()->back()->with('success', 'Journal Entry posted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
