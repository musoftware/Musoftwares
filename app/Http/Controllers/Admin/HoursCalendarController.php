<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceItemTimer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HoursCalendarController extends Controller
{
    public function index()
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $yearQuery = $isSqlite ? "strftime('%Y', date_start) as year" : 'YEAR(date_start) as year';

        $years_lists = InvoiceItemTimer::select(DB::raw($yearQuery))
            ->whereNotNull('date_start')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years_lists)) {
            $years_lists = [date('Y')];
        }

        return Inertia::render('Admin/HoursCalendar', [
            'years' => $years_lists,
        ]);
    }

    public function getData(Request $request)
    {
        $invoice_item_timers = InvoiceItemTimer::query();
        $selected_year = (int) $request->input('year', date('Y'));

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($isSqlite) {
            $yearly = $invoice_item_timers->select(
                DB::raw('CAST((julianday(invoice_item_timers.date_end) - julianday(invoice_item_timers.date_start)) * 86400 AS INTEGER) AS diff'),
                DB::raw("strftime('%Y', invoice_item_timers.date_start) as year"),
                DB::raw("strftime('%m', invoice_item_timers.date_start) as month"),
                DB::raw("strftime('%d', invoice_item_timers.date_start) as day")
            )
                ->where(DB::raw('invoice_item_timers.date_end'), '>', DB::raw('invoice_item_timers.date_start'))
                ->where(DB::raw("strftime('%Y', invoice_item_timers.date_start)"), (string) $selected_year)
                ->orderBy('invoice_item_timers.id', 'desc')
                ->get();
        } else {
            $yearly = $invoice_item_timers->select(
                DB::raw('TIMESTAMPDIFF(SECOND, invoice_item_timers.date_start, invoice_item_timers.date_end) AS diff'),
                DB::raw('YEAR(invoice_item_timers.date_start) as year'),
                DB::raw('MONTH(invoice_item_timers.date_start) as month'),
                DB::raw('DAY(invoice_item_timers.date_start) as day')
            )
                ->where(DB::raw('invoice_item_timers.date_end'), '>', DB::raw('invoice_item_timers.date_start'))
                ->where(DB::raw('YEAR(invoice_item_timers.date_start)'), $selected_year)
                ->orderBy('invoice_item_timers.id', 'desc')
                ->get();
        }

        $merge = [];
        foreach ($yearly as $w) {
            $mm = str_pad($w['month'], 2, '0', STR_PAD_LEFT);
            $dd = str_pad($w['day'], 2, '0', STR_PAD_LEFT);
            $date = "{$w['year']}-{$mm}-{$dd}";

            if (! isset($merge[$date])) {
                $merge[$date] = 0;
            }
            $merge[$date] += round($w['diff'] / 60 / 60, 3);
        }

        $heatmap = [];
        foreach ($merge as $date => $work_seconds) {
            $heatmap[] = [
                'date' => $date,
                'count' => round($work_seconds, 3),
            ];
        }

        // 2. Business settings
        $businessCurrencyId = \App\Models\AdminSettings::business_currency();
        $businessCurrency = \App\Models\AdminSettings::business_currency_name();
        $marketHourlyRate = (float) \App\Models\AdminSettings::GetValue('market_hourly_rate', 0);
        $recommendedHourlyRate = (float) \App\Models\AdminSettings::GetRecommendedHourlyRate($businessCurrencyId);

        // 3. Last 30 Days stats (Cairo timezone: Africa/Cairo)
        $tz = new \DateTimeZone('Africa/Cairo');
        $today = \Carbon\Carbon::now($tz);
        $startDate = (clone $today)->subDays(29)->startOfDay();
        $endDate = (clone $today)->endOfDay();

        // Convert boundary dates to UTC equivalents for safe DB queries
        $utcStartDate = (clone $startDate)->setTimezone('UTC')->toDateTimeString();
        $utcEndDate = (clone $endDate)->setTimezone('UTC')->toDateTimeString();

        $timers30 = InvoiceItemTimer::with(['project', 'user', 'invoiceItem.invoice', 'currency'])
            ->whereBetween('date_start', [$utcStartDate, $utcEndDate])
            ->whereNotNull('date_start')
            ->whereNotNull('date_end')
            ->whereRaw('date_end > date_start')
            ->get();

        // Initialize 30 days structure
        $chart30 = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = (clone $today)->subDays($i)->format('Y-m-d');
            $chart30[$d] = [
                'date' => $d,
                'hours' => 0.0,
                'amount' => 0.0, // business currency sum
                'market_rate' => $marketHourlyRate,
                'recommended_rate' => $recommendedHourlyRate,
                'actual_rate' => 0.0,
            ];
        }

        // Detailed session activity log
        $detailedSessions = [];

        foreach ($timers30 as $timer) {
            $start = \Carbon\Carbon::parse($timer->date_start)->setTimezone($tz);
            $end = \Carbon\Carbon::parse($timer->date_end)->setTimezone($tz);
            $dateStr = $start->format('Y-m-d');
            
            $durationSeconds = abs($end->getTimestamp() - $start->getTimestamp());
            $hours = round($durationSeconds / 3600, 3);
            
            // Convert to business currency
            $businessAmount = 0.0;
            if ($timer->business_amount > 0) {
                $businessAmount = (float)$timer->business_amount;
            } else {
                $currency = $timer->currency_id ?? $businessCurrencyId;
                $businessAmount = \App\Models\CurrenciesExchange::RateByDate(
                    $timer->date_start,
                    $timer->amount,
                    $currency,
                    $businessCurrencyId
                );
            }

            // Populate chart data if falls within the last 30 days window
            if (isset($chart30[$dateStr])) {
                $chart30[$dateStr]['hours'] += $hours;
                $chart30[$dateStr]['amount'] += $businessAmount;
            }

            // Populate detailed sessions log
            $invoice = $timer->invoiceItem->invoice ?? null;
            
            // Format duration string
            $hoursPart = floor($durationSeconds / 3600);
            $minutesPart = floor(($durationSeconds % 3600) / 60);
            $durationStr = $hoursPart . 'h ' . $minutesPart . 'm';

            $clientName = $timer->user ? $timer->user->name : ($invoice && $invoice->user ? $invoice->user->name : 'N/A');
            $projectName = $timer->project ? $timer->project->name : 'N/A';
            
            $timerRate = $hours > 0 ? ($timer->amount / $hours) : 0;
            $timerBusinessRate = $hours > 0 ? ($businessAmount / $hours) : 0;

            $detailedSessions[] = [
                'id' => $timer->id,
                'date' => $start->format('Y-m-d H:i'),
                'duration_str' => $durationStr,
                'hours' => round($hours, 2),
                'amount' => round($timer->amount, 2),
                'amount_str' => \App\Helpers\FinanceHelper::instance()->format_money($timer->amount, $timer->currency_id ?? ($invoice->currency ?? 1)),
                'business_amount' => round($businessAmount, 2),
                'business_amount_str' => \App\Helpers\FinanceHelper::instance()->format_money($businessAmount, $businessCurrencyId),
                'actual_rate' => round($timerRate, 2),
                'actual_rate_str' => \App\Helpers\FinanceHelper::instance()->format_money($timerRate, $timer->currency_id ?? ($invoice->currency ?? 1)),
                'business_rate' => round($timerBusinessRate, 2),
                'business_rate_str' => \App\Helpers\FinanceHelper::instance()->format_money($timerBusinessRate, $businessCurrencyId),
                'project_name' => $projectName,
                'client_name' => $clientName,
                'invoice_id' => $invoice ? $invoice->id : null,
                'invoice_number' => $invoice ? $invoice->invoice_number : null,
            ];
        }

        // Finalize actual rates in chart
        foreach ($chart30 as $d => &$day) {
            $day['actual_rate'] = $day['hours'] > 0 ? round($day['amount'] / $day['hours'], 2) : 0;
            $day['hours'] = round($day['hours'], 2);
            $day['amount'] = round($day['amount'], 2);
        }

        // Sort detailed sessions by date descending
        usort($detailedSessions, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return response()->json([
            'heatmap' => $heatmap,
            'chart_30_days' => array_values($chart30),
            'market_hourly_rate' => $marketHourlyRate,
            'recommended_hourly_rate' => $recommendedHourlyRate,
            'business_currency' => $businessCurrency,
            'last_30_days_timers' => $detailedSessions,
        ]);
    }
}
