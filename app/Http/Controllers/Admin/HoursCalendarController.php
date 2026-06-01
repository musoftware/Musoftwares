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
        $yearQuery = $isSqlite ? "strftime('%Y', date_start) as year" : "YEAR(date_start) as year";
        
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
        $selected_year = (int)$request->input('year', date('Y'));

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($isSqlite) {
            $yearly = $invoice_item_timers->select(
                DB::raw("CAST((julianday(invoice_item_timers.date_end) - julianday(invoice_item_timers.date_start)) * 86400 AS INTEGER) AS diff"),
                DB::raw("strftime('%Y', invoice_item_timers.created_at) as year"),
                DB::raw("strftime('%m', invoice_item_timers.created_at) as month"),
                DB::raw("strftime('%d', invoice_item_timers.created_at) as day")
            )
                ->where(DB::raw('invoice_item_timers.date_end'), '>', DB::raw('invoice_item_timers.date_start'))
                ->where(function ($query) use ($selected_year) {
                    if ($selected_year == date('Y')) {
                        $query->where(DB::raw('invoice_item_timers.date_start'), '>', DB::raw("date('now', '-1 year')"));
                    } else {
                        $query->where(DB::raw("strftime('%Y', invoice_item_timers.date_start)"), (string)$selected_year);
                    }
                })
                ->orderBy('invoice_item_timers.id', 'desc')
                ->get();
        } else {
            $yearly = $invoice_item_timers->select(
                DB::raw('TIMESTAMPDIFF(SECOND, invoice_item_timers.date_start, invoice_item_timers.date_end) AS diff'),
                DB::raw('YEAR(invoice_item_timers.created_at) as year'),
                DB::raw('MONTH(invoice_item_timers.created_at) as month'),
                DB::raw('DAY(invoice_item_timers.created_at) as day')
            )
                ->where(DB::raw('invoice_item_timers.date_end'), '>', DB::raw('invoice_item_timers.date_start'))
                ->where(function ($query) use ($selected_year) {
                    if ($selected_year == date('Y')) {
                        $query->where(DB::raw('invoice_item_timers.date_start'), '>', DB::raw('DATE_SUB(NOW(), INTERVAL 1 YEAR)'));
                    } else {
                        $query->where(DB::raw('YEAR(invoice_item_timers.date_start)'), $selected_year);
                    }
                })
                ->orderBy('invoice_item_timers.id', 'desc')
                ->get();
        }

        $merge = [];
        foreach ($yearly as $w) {
            $mm = str_pad($w['month'], 2, "0", STR_PAD_LEFT);
            $dd = str_pad($w['day'], 2, "0", STR_PAD_LEFT);
            $date = "{$w['year']}-{$mm}-{$dd}";

            if (!isset($merge[$date])) {
                $merge[$date] = 0;
            }
            $merge[$date] += round($w['diff'] / 60 / 60, 3);
        }

        $data = [];
        foreach ($merge as $date => $work_seconds) {
            $data[] = [
                'date' => $date, 
                'count' => round($work_seconds, 3)
            ];
        }
        
        return response()->json($data);
    }
}
