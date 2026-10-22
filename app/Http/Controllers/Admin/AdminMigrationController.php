<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AdminMigrationController extends Controller
{
    public function index(): Response
    {
        Artisan::call('migrate:status', ['--pending' => true]);

        return Inertia::render('Admin/System/Migrations', [
            'pendingOutput' => trim(Artisan::output()),
        ]);
    }

    public function run()
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
        } catch (\Throwable $e) {
            Log::error('Admin migrate failed', ['error' => $e->getMessage(), 'admin_id' => auth()->id()]);

            return back()->with('error', $e->getMessage());
        }

        Log::info('Admin ran migrations', ['admin_id' => auth()->id(), 'output' => $output]);

        return back()->with('success', $output ?: 'Nothing to migrate.');
    }
}
