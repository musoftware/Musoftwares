<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\Currency;
use App\Services\AI\GeminiEstimatorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiEstimatorController extends Controller
{
    public function __construct(
        protected GeminiEstimatorService $estimatorService
    ) {}

    public function index()
    {
        $user = auth()->user();

        $userCurrency = null;
        if ($user->hour_rate_currency_id) {
            $userCurrency = Currency::find($user->hour_rate_currency_id);
        } else {
            // Fallback to business currency if user doesn't have one set
            $businessCurrencyId = AdminSettings::business_currency()->id ?? null;
            if ($businessCurrencyId) {
                $userCurrency = Currency::find($businessCurrencyId);
            }
        }

        return Inertia::render('Admin/Tools/AiEstimator', [
            'expected_monthly_income' => (float) (AdminSettings::GetValue('expected_monthly_income') ?? 0),
            'work_days_per_month' => (float) (AdminSettings::GetValue('work_days_per_month') ?? 0),
            'hours_per_day' => (float) (AdminSettings::GetValue('hours_per_day') ?? 0),
            'currency' => $userCurrency,
        ]);
    }

    public function estimate(Request $request)
    {
        $request->validate([
            'task_description' => 'required|string|min:10',
        ]);

        $estimatedHours = $this->estimatorService->estimateHours($request->input('task_description'));

        if ($estimatedHours === null) {
            return response()->json(['error' => __('admin.ai_estimator_error')], 500);
        }

        return response()->json([
            'estimated_hours' => $estimatedHours,
        ]);
    }
}
