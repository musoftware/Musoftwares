<?php

use Illuminate\Support\Facades\Route;
use Modules\WebTools\Http\Controllers\Financial\CalculatorController;
use Modules\WebTools\Http\Controllers\Financial\GoldIndicatorController;
use Modules\WebTools\Http\Controllers\Financial\GoldSaverController;
use Modules\WebTools\Http\Controllers\Financial\PayGuestController;
use Modules\WebTools\Http\Controllers\Financial\PayoutUsdController;
use Modules\WebTools\Http\Controllers\Financial\SmartPricingCalculatorController;
use Modules\WebTools\Http\Controllers\Financial\WithdrawInstapayController;
use Modules\WebTools\Http\Controllers\Utilities\CipherIdentifierController;
use Modules\WebTools\Http\Controllers\Utilities\CoordinatesConverterController;
use Modules\WebTools\Http\Controllers\Utilities\JsObfuscatorController;
use Modules\WebTools\Http\Controllers\Utilities\MultipleCountdownTimerController;

Route::prefix('tools')->name('tools.')->group(function () {

    // Utilities
    Route::get('/cipher-identifier', [CipherIdentifierController::class, 'index'])->name('cipher-identifier');
    Route::get('/coordinates-converter', [CoordinatesConverterController::class, 'index'])->name('coordinates-converter');
    Route::get('/js-obfuscator', [JsObfuscatorController::class, 'index'])->name('js-obfuscator');
    Route::get('/multiple-countdown-timer', [MultipleCountdownTimerController::class, 'index'])->name('multiple-countdown-timer');

    // Financial Tools
    Route::get('/gold-indicator', [GoldIndicatorController::class, 'index'])->name('gold-indicator');
    Route::post('/gold-indicator', [GoldIndicatorController::class, 'process'])->name('gold-indicator.process');
    
    Route::get('/gold-saver', [GoldSaverController::class, 'index'])->name('gold-saver');
    Route::post('/gold-saver', [GoldSaverController::class, 'process'])->name('gold-saver.process');
    
    Route::get('/smart-pricing-calculator', [SmartPricingCalculatorController::class, 'index'])->name('smart-pricing-calculator');
    Route::post('/smart-pricing-calculator', [SmartPricingCalculatorController::class, 'process'])->name('smart-pricing-calculator.process');
    
    Route::redirect('/instapay-calculator', '/tools/withdraw-instapay', 301);
    
    Route::get('/withdraw-instapay', [WithdrawInstapayController::class, 'index'])->name('withdraw-instapay');
    Route::post('/withdraw-instapay', [WithdrawInstapayController::class, 'process'])->name('withdraw-instapay.process');
    Route::post('/withdraw-instapay/signup', [WithdrawInstapayController::class, 'signup'])->name('withdraw-instapay.signup')->middleware('throttle:10,1');
    Route::get('/withdraw-instapay/pay-link', [WithdrawInstapayController::class, 'payLink'])->name('withdraw-instapay.pay-link');
    Route::get('/withdraw-instapay/signed-pay-url', [WithdrawInstapayController::class, 'signedPayUrl'])->name('withdraw-instapay.signed-pay-url')->middleware(['auth', 'throttle:120,1']);
    
    Route::get('/pay-guest', [PayGuestController::class, 'index'])->name('pay-guest');
    Route::post('/pay-guest/signup', [PayGuestController::class, 'signup'])->name('pay-guest.signup')->middleware('throttle:10,1');
    Route::get('/pay-guest/pay-link', [PayGuestController::class, 'payLink'])->name('pay-guest.pay-link');
    Route::get('/pay-guest/signed-pay-url', [PayGuestController::class, 'signedPayUrl'])->name('pay-guest.signed-pay-url')->middleware(['auth', 'throttle:120,1']);
    
    Route::get('/calculator', [CalculatorController::class, 'index'])->name('calculator');
    Route::post('/calculator', [CalculatorController::class, 'process'])->name('calculator.process');
    Route::get('/calculator/signed-pay-url', [CalculatorController::class, 'signedPayUrl'])->name('calculator.signed-pay-url')->middleware(['auth', 'throttle:120,1']);
    
    Route::get('/payout-usd', [PayoutUsdController::class, 'index'])->name('payout-usd');
    Route::post('/payout-usd', [PayoutUsdController::class, 'process'])->name('payout-usd.process');
});
