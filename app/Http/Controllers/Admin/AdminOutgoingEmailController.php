<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OutgoingEmail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminOutgoingEmailController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $fromDate = $request->string('from_date')->trim()->toString();
        $toDate = $request->string('to_date')->trim()->toString();

        $query = OutgoingEmail::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('to_email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('mail_class', 'like', "%{$search}%");
            });
        }

        if ($status !== '' && in_array($status, ['sent', 'failed'])) {
            $query->where('status', $status);
        }

        if ($fromDate !== '') {
            $query->where('sent_at', '>=', Carbon::parse($fromDate, 'Africa/Cairo')->startOfDay()->setTimezone('UTC'));
        }

        if ($toDate !== '') {
            $query->where('sent_at', '<=', Carbon::parse($toDate, 'Africa/Cairo')->endOfDay()->setTimezone('UTC'));
        }

        $emails = $query->latest('sent_at')->latest('id')->paginate(20)->withQueryString();

        // Calculate summary stats in Cairo timezone boundaries
        $todayStart = now('Africa/Cairo')->startOfDay()->setTimezone('UTC');
        $todayEnd = now('Africa/Cairo')->endOfDay()->setTimezone('UTC');
        $monthStart = now('Africa/Cairo')->startOfMonth()->setTimezone('UTC');

        $stats = [
            'total_sent' => OutgoingEmail::count(),
            'sent_today' => OutgoingEmail::whereBetween('sent_at', [$todayStart, $todayEnd])->count(),
            'sent_this_month' => OutgoingEmail::where('sent_at', '>=', $monthStart)->count(),
            'failed_count' => OutgoingEmail::where('status', 'failed')->count(),
        ];

        return Inertia::render('Admin/OutgoingEmails/Index', [
            'emails' => $emails,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }
}
