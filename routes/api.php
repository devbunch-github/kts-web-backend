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
use App\Http\Controllers\Api\Client\ClientController;
use App\Http\Controllers\Api\Business\LoyaltyCardController;
use App\Http\Controllers\Api\Business\LoyaltyProgramController;
use App\Http\Controllers\Api\Business\BusinessSettingController;
use App\Http\Controllers\Api\Business\{RotaController,TimeOffController};
use App\Http\Controllers\Api\Business\BusinessFormController;
use App\Http\Controllers\Api\Business\BusinessToDoController;
use App\Http\Controllers\Api\Business\NotificationController;
use App\Http\Controllers\Api\Business\BusinessProfileController;
use App\Http\Controllers\Api\Business\BusinessReportController;
use App\Http\Controllers\Api\Public\AppointmentPaymentController;
use App\Http\Controllers\Api\Public\GiftCardPaymentController;

Route::get('/beauticians', [BeauticianController::class, 'index']);
Route::middleware('optional.sanctum')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/services', [ServiceController::class, 'index']);
    Route::apiResource('employees', EmployeeController::class);

    //business settings
    Route::prefix('business/settings')
        ->group(function () {
            Route::get('{type}', [BusinessSettingController::class, 'show']);
            Route::post('{type}', [BusinessSettingController::class, 'update']); // accepts multipart for 'site'
        });

    Route::get('/employees/{employee}/time-offs', [EmployeeController::class, 'timeOffs']);
    Route::post('/employees/{employee}/time-offs', [EmployeeController::class, 'storeTimeOff']);
    // Route::get('/employees/{employee}/schedule', [EmployeeController::class, 'schedule']);
    Route::get('/employees/{employee}/calendar', [EmployeeController::class, 'calendar']);
    Route::get('/employees/{id}/schedule', [EmployeeController::class, 'weekSchedule']);
    Route::post('/employees/{id}/schedule', [EmployeeController::class, 'storeSchedule']);
});

Route::prefix('public')->group(function () {
    // GIFT CARD payments
    Route::post('gift-cards/stripe', [GiftCardPaymentController::class, 'stripe']);
    Route::post('gift-cards/paypal', [GiftCardPaymentController::class, 'paypal']);
    Route::post(
        'gift-cards/purchase/{purchase}/mark-paid',
        [GiftCardPaymentController::class, 'markAsPaid']
    );
});

Route::get('public/promo/validate', [PromoCodeController::class, 'validateCode']);
Route::get('public/gift-card/validate', [GiftCardController::class, 'validateCode']);

Route::get('public/gift-card/{id}', [GiftCardController::class, 'show'])
    ->where('id', '[0-9]+');

Route::prefix('public/payment')->group(function () {
    Route::post('/stripe', [AppointmentPaymentController::class, 'stripe']);
    Route::post('/paypal', [AppointmentPaymentController::class, 'paypal']);
});
Route::post('/public/payment/mark-paid/{appointmentId}', 
    [AppointmentPaymentController::class, 'markAsPaid']
);

Route::post('/customers', [CustomerController::class, 'publicStore']);

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

// Public business gift card listing
Route::get('public/gift-cards/{accountId}', [GiftCardController::class, 'publicList']);

Route::post('/payment/stripe/create-intent', [PaymentController::class, 'createStripeIntent']);
Route::post('/payment/paypal/create-order', [PaymentController::class, 'createPayPalOrder']);
Route::post('/payment/paypal/capture', [PaymentController::class, 'capturePayPalOrder']);
Route::post('/payment/confirm', [PaymentController::class, 'confirmPayment']);

// Route::middleware('auth:sanctum')->group(function(){
    Route::post('/subscription/stripe',[SubscriptionController::class,'createStripe']);
    Route::post('/subscription/paypal',[SubscriptionController::class,'createPayPal']);
// });

Route::middleware('auth:sanctum')->group(function(){
    Route::middleware('auth:sanctum')->get('/subscriptions', [SubscriptionController::class, 'myActive']);
    Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelSubscription']);
});

Route::middleware('auth:sanctum')->get('/notifications', [NotificationController::class, 'index']);



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

    //reports
    Route::prefix('business/reports')->group(function () {
        Route::get('/summary', [BusinessReportController::class, 'reportSummary']);
        Route::get('/service', [BusinessReportController::class, 'serviceReport']);
        Route::get('/client', [BusinessReportController::class, 'clientReport']);
        Route::get('/appointment-completion', [BusinessReportController::class, 'appointmentCompletionReport']);
        Route::get('/profit-loss', [BusinessReportController::class, 'profitLossReport']);
        Route::get('/cancellation', [BusinessReportController::class, 'cancellationReport']);
        Route::get('/income-sale', [BusinessReportController::class, 'incomeSaleReport']);
        Route::get('/client-retention', [BusinessReportController::class, 'clientRetentionRate']);
    });


    Route::post('/file-upload', [FileUploadController::class, 'store']);
    Route::delete('/file-upload', [FileUploadController::class, 'destroy']);


    Route::get('/income', [IncomeController::class, 'index']);
    Route::post('/income', [IncomeController::class, 'store']);
    Route::get('/income/{id}', [IncomeController::class, 'show']);
    Route::put('/income/{id}', [IncomeController::class, 'update']);
    Route::delete('/income/{id}', [IncomeController::class, 'destroy']);

    Route::get('/income/export/pdf', [IncomeController::class, 'exportPdf']);

    Route::apiResource('appointments', AppointmentController::class);

    Route::apiResource('promo-codes', PromoCodeController::class);
    Route::get('promo-codes/{id}/usages', [PromoCodeController::class, 'usages']);

    Route::get('gift-cards', [GiftCardController::class, 'index']);
    Route::post('gift-cards', [GiftCardController::class, 'store']);
    Route::get('gift-cards/{id}', [GiftCardController::class, 'show']);
    Route::put('gift-cards/{id}', [GiftCardController::class, 'update']);
    Route::delete('gift-cards/{id}', [GiftCardController::class, 'destroy']);
    Route::get('gift-cards/{id}/usages', [GiftCardController::class, 'usages']);

    Route::get('email-messages', [EmailMessageController::class, 'index']);
    Route::get('email-messages/{id}', [EmailMessageController::class, 'show']);
    Route::put('email-messages/{id}', [EmailMessageController::class, 'update']);

    Route::prefix('loyalty-card')->group(function () {
        Route::get('/', [LoyaltyCardController::class, 'show']);
        Route::post('/', [LoyaltyCardController::class, 'save']);
    });

    Route::prefix('loyalty-program')->group(function () {
        Route::get('/', [LoyaltyProgramController::class, 'show']);
        Route::post('/', [LoyaltyProgramController::class, 'save']);
        Route::get('/summary', [LoyaltyProgramController::class, 'summary']);
    });

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

Route::prefix('business/reports')->group(function () {
    Route::get('/export', [BusinessReportController::class, 'exportReport']);
});

Route::prefix('business/reports')->group(function () {
    Route::get('/export', [BusinessReportController::class, 'exportReport']);
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

//business rota and time off
Route::middleware(['auth:sanctum'])->prefix('business')->group(function () {
    Route::get('/rota',[RotaController::class,'index']);
    Route::post('/rota/store',[RotaController::class,'store']);
    Route::delete('/rota',[RotaController::class,'destroy']);
    Route::put('/rota/{id}', [RotaController::class, 'update']);

    Route::get('/time-off', [TimeOffController::class, 'index']);
    Route::post('/time-off/store', [TimeOffController::class, 'store']);
    Route::put('/time-off/{id}', [TimeOffController::class, 'update']);
    Route::delete('/time-off', [TimeOffController::class, 'destroy']);

});

Route::middleware('auth:sanctum')->group(function () {
    Route::get   ('/forms',                 [BusinessFormController::class,'index']);
    Route::get   ('/forms/{id}',            [BusinessFormController::class,'show']);
    Route::post  ('/forms',                 [BusinessFormController::class,'store']);
    Route::put   ('/forms/{id}',            [BusinessFormController::class,'update']);
    Route::delete('/forms/{id}',            [BusinessFormController::class,'destroy']);
    Route::patch ('/forms/{id}/toggle',     [BusinessFormController::class,'toggle']);
});

Route::middleware('auth:sanctum')->prefix('business/todo')->group(function () {
    Route::get('/', [BusinessToDoController::class, 'index']);
    Route::post('/', [BusinessToDoController::class, 'store']);
    Route::put('{id}', [BusinessToDoController::class, 'update']);
    Route::delete('{id}', [BusinessToDoController::class, 'destroy']);
    Route::patch('{id}/toggle', [BusinessToDoController::class, 'toggle']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/business/profile', [BusinessProfileController::class, 'show']);
    Route::post('/business/profile', [BusinessProfileController::class, 'update']);
});

Route::middleware(['auth:sanctum'])->prefix('client')->group(function () {
    Route::get('/appointments', [ClientController::class, 'appointments']);
    Route::post('/appointments/{id}/cancel', [ClientController::class, 'cancelAppointment']);
    Route::post('/appointments/{id}/reschedule', [ClientController::class, 'rescheduleAppointment']);
    Route::post('/appointments/{id}/review', [ClientController::class, 'leaveReview']);

    Route::get('/gift-cards', [ClientController::class, 'purchasedGiftCards']);
    Route::get('/profile', [ClientController::class, 'profile']);
    Route::put('/profile/update', [ClientController::class, 'updateProfile']);

});