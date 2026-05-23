<?php

namespace Modules\GoldSavers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\GoldSavers\Models\GoldSaver;
use Illuminate\Support\Facades\Auth;

class GoldSaversController extends Controller
{
    public function index()
    {
        $records = GoldSaver::where('user_id', Auth::id())
            ->orderBy('bought_date', 'desc')
            ->get();
            
        // Map the calculations for the frontend
        $records->transform(function ($record) {
            return [
                'id' => $record->id,
                'carat' => $record->carat,
                'gram_price' => $record->gram_price,
                'additional_price' => $record->additional_price,
                'grams' => $record->grams,
                'tax' => $record->tax,
                'bought_date' => $record->bought_date,
                'zakat' => $record->zakat,
                'buyer_price' => $record->buyer_price(),
                'buy2sell_rate' => $record->buy2sell_rate(),
            ];
        });

        return Inertia::render('GoldSavers/Index', [
            'records' => $records
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'carat' => 'required|in:14,18,21,22,24',
            'gram_price' => 'required|numeric|min:0',
            'grams' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'additional_price' => 'required|numeric|min:0',
            'bought_date' => 'required|date',
            'zakat' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['zakat'] = $request->boolean('zakat');

        GoldSaver::create($validated);

        return redirect()->back()->with('success', 'Gold saving record added successfully.');
    }

    public function destroy(GoldSaver $goldSaver)
    {
        if ($goldSaver->user_id !== Auth::id()) {
            abort(403);
        }

        $goldSaver->delete();

        return redirect()->back()->with('success', 'Record deleted successfully.');
    }
}
