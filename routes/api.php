<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BeauticianController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;


Route::get('/beauticians', [BeauticianController::class, 'index']);

// Contact
Route::post('/contact', [ContactController::class, 'store']);

// Plans & checkout
Route::get('/plans', [PlanController::class, 'index']);
Route::post('/checkout', [CheckoutController::class, 'create']);
Route::post('/checkout/confirm', [CheckoutController::class, 'confirm']);

// Auth (simple JSON endpoints – swap to Sanctum later)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/pre-register', [AuthController::class, 'preRegister']);
Route::post('/auth/set-password', [AuthController::class, 'setPassword']);

Route::post('/payment/stripe/create-intent', [PaymentController::class, 'createStripeIntent']);
Route::post('/payment/paypal/create-order', [PaymentController::class, 'createPayPalOrder']);
Route::post('/payment/paypal/capture', [PaymentController::class, 'capturePayPalOrder']);
Route::post('/payment/confirm', [PaymentController::class, 'confirmPayment']);



