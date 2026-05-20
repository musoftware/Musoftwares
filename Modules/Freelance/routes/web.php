<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

Route::middleware(['auth', 'verified', 'onboarding', 'subscription:freelance'])->prefix('freelance')->name('freelance.')->group(function () {
    Route::get('/dashboard', [\Modules\Freelance\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Skills
    Route::resource('skills', \Modules\Freelance\Http\Controllers\SkillController::class)->except(['create', 'show', 'edit']);

    // User Skills
    Route::post('/user-skills', [\Modules\Freelance\Http\Controllers\UserSkillController::class, 'store'])->name('user-skills.store');
    Route::delete('/user-skills/{skill_id}', [\Modules\Freelance\Http\Controllers\UserSkillController::class, 'destroy'])->name('user-skills.destroy');

    // Points
    Route::get('/points', function(Request $request) {
        $packages = \Modules\Freelance\Models\PointPackage::all();
        $transactions = \Modules\Freelance\Models\PointTransaction::where('user_id', $request->user()->id)->latest()->paginate(10);
        return Inertia::render('Freelance/Points/Index', ['packages' => $packages, 'transactions' => $transactions]);
    })->name('points.index');
    Route::resource('point-packages', \Modules\Freelance\Http\Controllers\PointPackageController::class)->except(['create', 'show', 'edit']);
    Route::post('/point-purchases', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'store'])->name('point-purchases.store');
    Route::post('/point-purchases/wallet', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'storeWallet'])->name('point-purchases.store-wallet');
    Route::get('/point-purchases/success', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'success'])->name('point-purchases.success');
    Route::get('/point-purchases/failure', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'failure'])->name('point-purchases.failure');

    // Jobs
    Route::get('/jobs/browse', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'index'])->name('jobs.browse');
    Route::get('/jobs/my-jobs', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'myJobs'])->name('my-jobs');
    Route::resource('jobs', \Modules\Freelance\Http\Controllers\FreelanceJobController::class)->except(['index']);

    // Proposals
    Route::get('/proposals', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'index'])->name('proposals.index');
    Route::post('/jobs/{job}/proposals', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'store'])->name('proposals.store');
    Route::post('/proposals/{proposal}/accept', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'accept'])->name('proposals.accept');
    Route::post('/proposals/{proposal}/reject', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'reject'])->name('proposals.reject');
    Route::delete('/proposals/{proposal}/withdraw', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'withdraw'])->name('proposals.withdraw');

    // Contracts
    Route::get('/contracts', [\Modules\Freelance\Http\Controllers\ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/{contract}', [\Modules\Freelance\Http\Controllers\ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/complete', [\Modules\Freelance\Http\Controllers\ContractController::class, 'complete'])->name('contracts.complete');
    Route::post('/contracts/{contract}/dispute', [\Modules\Freelance\Http\Controllers\ContractController::class, 'dispute'])->name('contracts.dispute');
});