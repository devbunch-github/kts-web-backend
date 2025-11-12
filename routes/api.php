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
use App\Http\Controllers\Api\Admin\AdminIncomeController;
use App\Http\Controllers\Api\Admin\AdminExpenseController;
use App\Http\Controllers\Api\Admin\PaymentSettingController;
use App\Http\Controllers\Api\Admin\SmsPackageController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\Business\AppointmentController;
use App\Http\Controllers\Api\Business\BusinessDashboardController;
use App\Http\Controllers\Api\AccountantController;
use App\Http\Controllers\Api\Accountant\Auth\LoginController;
use App\Http\Controllers\Api\Accountant\AcctDashboardController;
use App\Http\Controllers\Api\Business\PromoCodeController;
use App\Http\Controllers\Api\Business\GiftCardController;
use App\Http\Controllers\Api\Business\EmailMessageController;


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
Route::get('/admin/income/{user_id}', [AdminIncomeController::class, 'show']);
Route::get('/admin/expense/{user_id}', [AdminExpenseController::class, 'show']);
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

    // SMS Purchase Blance
    Route::get('/sms-purchase-balance', [SmsPackageController::class, 'purchasebalance']);
});


// Accountant Dashboard Login
Route::post('/accountant/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/beautician/check', [BeauticianController::class, 'check']);
    Route::post('/beautician/setup', [BeauticianController::class, 'setup']);

    Route::get('/business/dashboard/summary', [BusinessDashboardController::class, 'summary']);
    Route::get('/business/dashboard/appointments', [BusinessDashboardController::class, 'appointments']);



    Route::post('/file-upload', [FileUploadController::class, 'store']);
    Route::delete('/file-upload', [FileUploadController::class, 'destroy']);


    Route::get('/income', [IncomeController::class, 'index']);
    Route::post('/income', [IncomeController::class, 'store']);
    Route::get('/income/{id}', [IncomeController::class, 'show']);
    Route::put('/income/{id}', [IncomeController::class, 'update']);
    Route::delete('/income/{id}', [IncomeController::class, 'destroy']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/services', [ServiceController::class, 'index']);

    Route::get('/income/export/pdf', [IncomeController::class, 'exportPdf']);

    Route::apiResource('employees', EmployeeController::class);
    Route::get('/employees/{employee}/time-offs', [EmployeeController::class, 'timeOffs']);
    Route::post('/employees/{employee}/time-offs', [EmployeeController::class, 'storeTimeOff']);
    // Route::get('/employees/{employee}/schedule', [EmployeeController::class, 'schedule']);
    Route::get('/employees/{employee}/calendar', [EmployeeController::class, 'calendar']);
    Route::get('/employees/{id}/schedule', [EmployeeController::class, 'weekSchedule']);
    Route::post('/employees/{id}/schedule', [EmployeeController::class, 'storeSchedule']);

    Route::apiResource('appointments', AppointmentController::class);

    Route::apiResource('promo-codes', PromoCodeController::class);
    Route::get('gift-cards', [GiftCardController::class, 'index']);
    Route::post('gift-cards', [GiftCardController::class, 'store']);
    Route::get('gift-cards/{id}', [GiftCardController::class, 'show']);
    Route::put('gift-cards/{id}', [GiftCardController::class, 'update']);
    Route::delete('gift-cards/{id}', [GiftCardController::class, 'destroy']);

    Route::get('email-messages', [EmailMessageController::class, 'index']);
    Route::get('email-messages/{id}', [EmailMessageController::class, 'show']);
    Route::put('email-messages/{id}', [EmailMessageController::class, 'update']);

    Route::prefix('admin')->group(function () {
        Route::get('expenses/export/pdf', [ExpenseController::class, 'exportPdf']);
        Route::post('expenses/upload', [ExpenseController::class, 'uploadFiles']);
        Route::delete('expenses/file', [ExpenseController::class, 'deleteFile']);

        Route::apiResource('expenses', ExpenseController::class);


        // Services CRUD
        Route::get('services/{id}', [ServiceController::class, 'show']);
        Route::post('services', [ServiceController::class, 'store']);
        Route::put('services/{id}', [ServiceController::class, 'update']);
        Route::delete('services/{id}', [ServiceController::class, 'destroy']);

        // Categories CRUD
        Route::get('categories/{id}', [CategoryController::class, 'show']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{id}', [CategoryController::class, 'update']);
        Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::put('/customers/{id}', [CustomerController::class, 'update']);
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

        // Customer Reviews
        Route::get('/customer/reviews', [CustomerController::class, 'reviews']);
        Route::post('/customer/reviews/{id}/status', [CustomerController::class, 'updateReviewStatus']);
        Route::post('/customer/reviews/bulk-status', [CustomerController::class, 'bulkUpdateReviewStatus']);
        Route::delete('/customer/reviews/{id}', [CustomerController::class, 'destroyReview']);

        // SMS Packages
        Route::get('/sms-packages', [SmsPackageController::class, 'getSmsPackages']);

        // Accountant
        Route::apiResource('accountants', AccountantController::class);
        Route::post('accountants/{id}/reset-password', [AccountantController::class, 'resetPassword']);
        Route::patch('/accountants/{id}/toggle-access', [AccountantController::class, 'toggleAccess']);
        Route::get('/accountants/{id}', [AccountantController::class, 'show']);
    });

    // Accountant Dashboard
    Route::get('/accountant/dashboard', [AcctDashboardController::class, 'index']);
    Route::get('/accountant/dashboard/summary', [AcctDashboardController::class, 'dbsummary']);

    // Accountant Income
    Route::get('/accountant/income', [AcctDashboardController::class, 'fetchAccountantIncome']);
    Route::delete('/accountant/income/{id}', [AcctDashboardController::class, 'deleteIncome']);
    Route::get('/accountant/income/{id}', [AcctDashboardController::class, 'fetchIncomeById']);
    Route::put('/accountant/income/{id}', [AcctDashboardController::class, 'updateIncome']);

    // Accontant Expense
    Route::get('/accountant/expenses', [AcctDashboardController::class, 'fetchAccountantExpenses']);
    Route::delete('/accountant/expenses/{id}', [AcctDashboardController::class, 'deleteExpense']);
    Route::get('/accountant/categories', [AcctDashboardController::class, 'fetchExpenseCategories']);
    Route::get('/accountant/expense/{id}', [AcctDashboardController::class, 'fetchExpenseById']);
    Route::put('/accountant/expense/{id}', [AcctDashboardController::class, 'updateExpense']);

    //Summary Reports
    Route::post('/accountant/summary/pdf', [AcctDashboardController::class, 'summary']);
    Route::post('/accountant/summary/csv', [AcctDashboardController::class, 'summaryCSV']);

});

// Subscription Packages
Route::post('/admin/plans', [PlanController::class, 'store']);
Route::get('/admin/plans', [PlanController::class, 'index']);
Route::post('/admin/plans', [PlanController::class, 'store']);
Route::get('/admin/plans/{id}', [PlanController::class, 'show']);
Route::put('/admin/plans/{id}', [PlanController::class, 'update']);
Route::delete('/admin/plans/{id}', [PlanController::class, 'destroy']);

Route::get('/admin/subscriptions', [SubscriptionController::class, 'getSubscriptions']);
Route::post('/admin/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);

// Subscription Packages
Route::post('/admin/plans', [PlanController::class, 'store']);
Route::get('/admin/plans', [PlanController::class, 'index']);
Route::post('/admin/plans', [PlanController::class, 'store']);
Route::get('/admin/plans/{id}', [PlanController::class, 'show']);
Route::put('/admin/plans/{id}', [PlanController::class, 'update']);
Route::delete('/admin/plans/{id}', [PlanController::class, 'destroy']);

Route::get('/admin/subscriptions', [SubscriptionController::class, 'getSubscriptions']);
Route::post('/admin/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);
