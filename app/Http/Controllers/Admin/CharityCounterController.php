<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CharityCounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CharityCounterController extends Controller
{
    /**
     * Display the charity counter admin page.
     */
    public function index(Request $request)
    {
        $query = CharityCounter::with('user')->orderBy('balance', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $charityCounters = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/CharityCounter/Index', [
            'charityCounters' => $charityCounters,
            'filters' => $request->only(['search']),
            'stats' => [
                'totalBalance' => CharityCounter::getGlobalBalance(),
                'totalReceived' => CharityCounter::getGlobalTotalReceived(),
                'totalSpent' => CharityCounter::getGlobalTotalSpent(),
                'totalUsers' => CharityCounter::count(),
            ],
        ]);
    }

    /**
     * Add amount to global counter.
     */
    public function addAmount(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            CharityCounter::addToGlobalCounterByAdmin(
                $request->amount,
                $request->description,
                Auth::id()
            );

            DB::commit();

            return back()->with('success', 'تم إضافة المبلغ بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * Subtract amount from global counter.
     */
    public function subtractAmount(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            CharityCounter::subtractFromGlobalCounter(
                $request->amount,
                $request->description,
                Auth::id()
            );

            DB::commit();

            return back()->with('success', 'تم خصم المبلغ بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
