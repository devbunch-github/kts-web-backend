<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BeauticianController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\IncomeController;
use App\Http\Controllers\Api\Admin\ExpenseController;
use App\Http\Controllers\Api\Admin\PaymentSettingController;
use App\Http\Controllers\Api\Admin\SmsPackageController;


Route::get('/beauticians', [BeauticianController::class, 'index']);

// Contact
Route::post('/contact', [ContactController::class, 'store']);

// Plans & checkout
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{id}', [PlanController::class, 'show']);

Route::post('/checkout', [CheckoutController::class, 'create']);
Route::post('/checkout/confirm', [CheckoutController::class, 'confirm']);

// Auth (simple JSON endpoints – swap to Sanctum later)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/pre-register', [AuthController::class, 'preRegister']);
Route::post('/auth/set-password', [AuthController::class, 'setPassword']);
Route::post('/auth/check-email', [AuthController::class, 'checkEmail']);


Route::post('/payment/stripe/create-intent', [PaymentController::class, 'createStripeIntent']);
Route::post('/payment/paypal/create-order', [PaymentController::class, 'createPayPalOrder']);
Route::post('/payment/paypal/capture', [PaymentController::class, 'capturePayPalOrder']);
Route::post('/payment/confirm', [PaymentController::class, 'confirmPayment']);

// Route::middleware('auth:sanctum')->group(function(){
    Route::post('/subscription/stripe',[SubscriptionController::class,'createStripe']);
    Route::post('/subscription/paypal',[SubscriptionController::class,'createPayPal']);
// });

Route::post('/webhook/stripe',[WebhookController::class,'handleStripe']);
Route::post('/webhook/paypal',[WebhookController::class,'handlePayPal']);

// Super Admin
Route::get('/admin/dashboard', [DashboardController::class, 'index']);
Route::get('/admin/income/{user_id}', [IncomeController::class, 'show']);
Route::get('/admin/expense/{user_id}', [ExpenseController::class, 'show']);
Route::get('/admin/payment-settings', [PaymentSettingController::class, 'show']);
Route::post('/admin/payment-settings', [PaymentSettingController::class, 'update']);

//SMS Packages
Route::prefix('admin')->group(function () {

    // List all packages
    Route::get('/sms-packages', [SmsPackageController::class, 'index']);

    // Create new package
    Route::post('/sms-packages', [SmsPackageController::class, 'store']);

    // Get a specific package (for editing)
    Route::get('/sms-packages/{id}', [SmsPackageController::class, 'show']);

    // Update package
    Route::put('/sms-packages/{id}', [SmsPackageController::class, 'update']);

    // Delete package
    Route::delete('/sms-packages/{id}', [SmsPackageController::class, 'destroy']);
});
