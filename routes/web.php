<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ERP Routes
Route::middleware(['auth', 'verified'])->prefix('erp')->name('erp.')->group(function () {
    Route::get('/dashboard', function () { return Inertia::render('ERP/Dashboard'); })->name('dashboard');
    Route::get('/invoices', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
});

// Freelance Routes
Route::middleware(['auth', 'verified'])->prefix('freelance')->name('freelance.')->group(function () {
    Route::get('/dashboard', function () { return Inertia::render('Freelance/Dashboard'); })->name('dashboard');

    // Skills
    Route::resource('skills', \Modules\Freelance\Http\Controllers\SkillController::class)->except(['create', 'show', 'edit']);

    // User Skills
    Route::post('/user-skills', [\Modules\Freelance\Http\Controllers\UserSkillController::class, 'store'])->name('user-skills.store');
    Route::delete('/user-skills/{skill_id}', [\Modules\Freelance\Http\Controllers\UserSkillController::class, 'destroy'])->name('user-skills.destroy');

    // Points
    Route::get('/points', function(Illuminate\Http\Request $request) {
        $packages = \Modules\Freelance\Models\PointPackage::all();
        $transactions = \Modules\Freelance\Models\PointTransaction::where('user_id', $request->user()->id)->latest()->paginate(10);
        return Inertia::render('Freelance/Points/Index', ['packages' => $packages, 'transactions' => $transactions]);
    })->name('points.index');
    Route::resource('point-packages', \Modules\Freelance\Http\Controllers\PointPackageController::class)->except(['create', 'show', 'edit']);
    Route::post('/point-purchases', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'store'])->name('point-purchases.store');

    // Jobs
    Route::get('/jobs/browse', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'index'])->name('jobs.browse');
    Route::get('/jobs/my-jobs', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'myJobs'])->name('my-jobs');
    Route::resource('jobs', \Modules\Freelance\Http\Controllers\FreelanceJobController::class)->except(['index']);

    // Proposals
    Route::post('/jobs/{job}/proposals', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'store'])->name('proposals.store');
    Route::post('/proposals/{proposal}/accept', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'accept'])->name('proposals.accept');
    Route::post('/proposals/{proposal}/reject', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'reject'])->name('proposals.reject');
    Route::delete('/proposals/{proposal}/withdraw', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'withdraw'])->name('proposals.withdraw');

    // Contracts
    Route::get('/contracts/{contract}', [\Modules\Freelance\Http\Controllers\ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/complete', [\Modules\Freelance\Http\Controllers\ContractController::class, 'complete'])->name('contracts.complete');
    Route::post('/contracts/{contract}/dispute', [\Modules\Freelance\Http\Controllers\ContractController::class, 'dispute'])->name('contracts.dispute');
});

// Marketplace Routes
Route::middleware(['auth', 'verified'])->prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/dashboard', function () { return Inertia::render('Marketplace/Dashboard'); })->name('dashboard');
    Route::get('/services', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'index'])->name('services.index');
});

require __DIR__.'/auth.php';
