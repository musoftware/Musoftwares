<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

Route::middleware(['auth', 'verified', 'onboarding', 'subscription:freelance'])->prefix('freelance')->name('freelance.')->group(function () {
    Route::get('/dashboard', [\Modules\Freelance\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Skills
    Route::resource('skills', \Modules\Freelance\Http\Controllers\SkillController::class)->except(['create', 'show', 'edit']);

    // Profile & Settings
    Route::get('/profile', [\Modules\Freelance\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\Modules\Freelance\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    
    Route::get('/settings/notifications', [\Modules\Freelance\Http\Controllers\SettingsController::class, 'notifications'])->name('settings.notifications');
    Route::put('/settings/notifications', [\Modules\Freelance\Http\Controllers\SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::post('/settings/notifications/shortcut-token', [\Modules\Freelance\Http\Controllers\ShortcutNotificationController::class, 'generateToken'])->name('settings.notifications.shortcut-token');
    // User Skills
    Route::post('/user-skills', [\Modules\Freelance\Http\Controllers\UserSkillController::class, 'store'])->name('user-skills.store');
    Route::delete('/user-skills/{skill_id}', [\Modules\Freelance\Http\Controllers\UserSkillController::class, 'destroy'])->name('user-skills.destroy');


    // Jobs
    Route::get('/jobs/browse', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'index'])->name('jobs.browse');
    Route::get('/jobs/my-jobs', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'myJobs'])->name('my-jobs');
    Route::resource('jobs', \Modules\Freelance\Http\Controllers\FreelanceJobController::class)->except(['index']);
    Route::post('/jobs/{job}/poke', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'poke'])->name('jobs.poke');

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
