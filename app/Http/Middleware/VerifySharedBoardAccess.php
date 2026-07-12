<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class VerifySharedBoardAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If the user is authenticated (not a guest), they can view the board
        // but only up to today's date.
        if ($request->user()) {
            $date = $request->route('date');
            try {
                $dateCarbon = Carbon::createFromFormat('!Y-m-d', $date);
            } catch (\Throwable $e) {
                $dateCarbon = Carbon::today();
            }

            if ($dateCarbon->isPast() || $dateCarbon->isToday()) {
                return $next($request);
            }
        }

        // 2. Otherwise, they must have a valid signature (guest or user requesting future dates).
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        abort(403, 'Invalid or expired signature.');
    }
}
