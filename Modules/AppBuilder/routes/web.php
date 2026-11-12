<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Modules\AppBuilder\Http\Controllers\BuilderChatController;
use Modules\AppBuilder\Http\Controllers\BuilderDeliveryController;
use Modules\AppBuilder\Http\Controllers\BuilderPaymentController;
use Modules\AppBuilder\Http\Controllers\WorkerJobController;
use Modules\AppBuilder\Http\Middleware\AuthenticateBuilderWorker;

Route::middleware('web')->prefix('ai')->name('builder.')->group(function () {
    Route::get('/', [BuilderChatController::class, 'index'])->name('index');
    Route::post('/messages', [BuilderChatController::class, 'start'])->middleware('throttle:10,1')->name('start');
    Route::post('/webhook', [BuilderPaymentController::class, 'webhook'])->name('webhook');

    Route::whereUuid('project')->group(function () {
        Route::get('/{project}', [BuilderChatController::class, 'show'])->name('show');
        Route::get('/{project}/state', [BuilderChatController::class, 'state'])->middleware('throttle:60,1')->name('state');
        Route::post('/{project}/messages', [BuilderChatController::class, 'send'])->middleware('throttle:15,1')->name('send');
        Route::post('/{project}/originals', [BuilderChatController::class, 'originals'])->middleware('throttle:10,1')->name('originals');
        Route::post('/{project}/originals/skip', [BuilderChatController::class, 'skipOriginals'])->middleware('throttle:10,1')->name('originals.skip');
        Route::post('/{project}/left', [BuilderChatController::class, 'left'])->middleware('throttle:20,1')->name('left');
        Route::post('/{project}/discount', [BuilderChatController::class, 'discount'])->middleware('throttle:10,1')->name('discount');
        Route::post('/{project}/pay', [BuilderPaymentController::class, 'pay'])->middleware('throttle:10,1')->name('pay');
        Route::post('/{project}/pay/wallet', [BuilderPaymentController::class, 'payWithWallet'])->middleware(['auth', 'throttle:10,1'])->name('pay.wallet');
        Route::post('/{project}/free', [BuilderPaymentController::class, 'freeBuild'])->middleware(['auth', 'throttle:5,1'])->name('free');
        Route::post('/{project}/fix', [BuilderPaymentController::class, 'fix'])->middleware('throttle:10,1')->name('fix');
        Route::get('/{project}/files/{attachment}', [BuilderChatController::class, 'file'])->whereNumber('attachment')->middleware('throttle:120,1')->name('file');
        Route::get('/{project}/study', [BuilderChatController::class, 'study'])->name('study');
        Route::post('/{project}/study/pay', [BuilderPaymentController::class, 'payStudy'])->middleware('throttle:10,1')->name('study.pay');
        Route::post('/{project}/push', [BuilderChatController::class, 'push'])->middleware('throttle:10,60')->name('push');
        Route::post('/{project}/trial', [\Modules\AppBuilder\Http\Controllers\LiveTrialController::class, 'request'])->middleware('throttle:6,60')->name('trial');
        Route::get('/{project}/download', [BuilderDeliveryController::class, 'download'])->middleware('throttle:20,1')->name('download');
        Route::get('/{project}/app/{path?}', [BuilderDeliveryController::class, 'preview'])->where('path', '.*')->middleware('throttle:240,1')->name('preview');
    });
});

// The owner's worker: token only, no session, no CSRF. It calls out to here; the site never calls the worker.
Route::middleware([AuthenticateBuilderWorker::class, SubstituteBindings::class, 'throttle:120,1'])
    ->prefix('api/builder/worker')->name('builder.worker.')->whereUuid('project')->group(function () {
        Route::post('/claim', [WorkerJobController::class, 'claim'])->name('claim');
        Route::get('/trials', [\Modules\AppBuilder\Http\Controllers\LiveTrialController::class, 'pending'])->name('trials');
        Route::post('/trials/{project}/started', [\Modules\AppBuilder\Http\Controllers\LiveTrialController::class, 'started'])->name('trials.started');
        Route::post('/trials/{project}/failed', [\Modules\AppBuilder\Http\Controllers\LiveTrialController::class, 'failed'])->name('trials.failed');
        Route::get('/jobs/{project}/source', [WorkerJobController::class, 'source'])->name('source');
        Route::get('/jobs/{project}/chat', [WorkerJobController::class, 'chat'])->name('chat');
        Route::get('/jobs/{project}/attachments/{attachment}', [WorkerJobController::class, 'attachment'])->whereNumber('attachment')->name('attachment');
        Route::post('/jobs/{project}/progress', [WorkerJobController::class, 'progress'])->name('progress');
        Route::get('/looks', [\Modules\AppBuilder\Http\Controllers\LinkLookController::class, 'pending'])->name('looks');
        Route::post('/looks/{message}', [\Modules\AppBuilder\Http\Controllers\LinkLookController::class, 'store'])->whereNumber('message')->name('looks.store');
        Route::post('/jobs/{project}/snapshot', [WorkerJobController::class, 'snapshot'])->name('snapshot');
        Route::post('/jobs/{project}/question', [WorkerJobController::class, 'question'])->name('question');
        Route::get('/jobs/{project}/answers', [WorkerJobController::class, 'answers'])->name('answers');
        Route::post('/jobs/{project}/complete', [WorkerJobController::class, 'complete'])->name('complete');
        Route::post('/jobs/{project}/fail', [WorkerJobController::class, 'fail'])->name('fail');
    });

// -- Admin: every chat, and a way for the owner to step in ------------------------------------------
Route::middleware(['web', 'auth', 'verified', 'admin'])->prefix('admin/builder')->name('admin.builder.')->whereUuid('project')->group(function () {
    Route::get('/', [\Modules\AppBuilder\Http\Controllers\Admin\AdminBuilderController::class, 'index'])->name('index');
    Route::get('/{project}', [\Modules\AppBuilder\Http\Controllers\Admin\AdminBuilderController::class, 'show'])->name('show');
    Route::post('/{project}/messages', [\Modules\AppBuilder\Http\Controllers\Admin\AdminBuilderController::class, 'message'])->name('message');
    Route::post('/{project}/secrets', [\Modules\AppBuilder\Http\Controllers\Admin\AdminBuilderController::class, 'storeSecret'])->name('secrets.store');
    Route::post('/{project}/mark-paid', [\Modules\AppBuilder\Http\Controllers\Admin\AdminBuilderController::class, 'markPaid'])->name('mark-paid');
    Route::post('/{project}/extra', [\Modules\AppBuilder\Http\Controllers\Admin\AdminBuilderController::class, 'extra'])->name('extra');
    Route::post('/{project}/retry', [\Modules\AppBuilder\Http\Controllers\Admin\AdminBuilderController::class, 'retry'])->name('retry');
    Route::post('/{project}/design', [\Modules\AppBuilder\Http\Controllers\Admin\AdminBuilderController::class, 'design'])->name('design');
    Route::delete('/{project}/secrets/{secret}', [\Modules\AppBuilder\Http\Controllers\Admin\AdminBuilderController::class, 'destroySecret'])->whereNumber('secret')->name('secrets.destroy');
});
