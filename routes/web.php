<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminVendorController;
use App\Http\Controllers\Admin\AdminPackageController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerBookingController;
use App\Http\Controllers\Customer\CustomerPaymentController;
use App\Http\Controllers\Customer\CustomerVendorController;
use App\Http\Controllers\Customer\CustomerTimelineController;
use App\Http\Controllers\Customer\CustomerBudgetController;
use App\Http\Controllers\Vendor\VendorDashboardController;
use App\Http\Controllers\Vendor\VendorBookingController;
use App\Http\Controllers\Vendor\VendorEarningsController;
use App\Http\Controllers\Vendor\VendorProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ToyyibPayCallbackController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Bookings
    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{id}', [AdminBookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{id}/edit', [AdminBookingController::class, 'edit'])->name('bookings.edit');
    Route::put('/bookings/{id}', [AdminBookingController::class, 'update'])->name('bookings.update');
    Route::delete('/bookings/{id}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');

    // Vendors
    Route::get('/vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
    Route::post('/vendors/{id}/approve', [AdminVendorController::class, 'approve'])->name('vendors.approve');
    Route::post('/vendors/{id}/reject', [AdminVendorController::class, 'reject'])->name('vendors.reject');

    // Packages
    Route::resource('packages', AdminPackageController::class);

    // Customers
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{id}', [AdminCustomerController::class, 'show'])->name('customers.show');

    // Payments
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{id}', [AdminPaymentController::class, 'show'])->name('payments.show');

    // Reports
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
});

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');

    // Bookings
    Route::get('/bookings', [CustomerBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [CustomerBookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [CustomerBookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{id}', [CustomerBookingController::class, 'show'])->name('bookings.show');

    // Payments
    Route::get('/payment/{booking}', [CustomerPaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment/{booking}', [CustomerPaymentController::class, 'process'])->name('payment.process');
    Route::get('/payment/return', [CustomerPaymentController::class, 'return'])->name('payment.return');

    // Vendors
    Route::get('/vendors', [CustomerVendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/{id}', [CustomerVendorController::class, 'show'])->name('vendors.show');

    // Timeline
    Route::get('/timeline', [CustomerTimelineController::class, 'index'])->name('timeline.index');
    Route::post('/timeline', [CustomerTimelineController::class, 'store'])->name('timeline.store');
    Route::put('/timeline/{id}', [CustomerTimelineController::class, 'update'])->name('timeline.update');
    Route::delete('/timeline/{id}', [CustomerTimelineController::class, 'destroy'])->name('timeline.destroy');

    // Budget
    Route::get('/budget', [CustomerBudgetController::class, 'index'])->name('budget.index');
    Route::post('/budget', [CustomerBudgetController::class, 'store'])->name('budget.store');
    Route::post('/budget/expense', [CustomerBudgetController::class, 'addExpense'])->name('budget.expense');
    Route::delete('/budget/expense/{id}', [CustomerBudgetController::class, 'deleteExpense'])->name('budget.expense.destroy');

    // Profile
    Route::get('/profile', [CustomerDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [CustomerDashboardController::class, 'updateProfile'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Vendor Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('dashboard');

    // Bookings
    Route::get('/bookings', [VendorBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{id}', [VendorBookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{id}/accept', [VendorBookingController::class, 'accept'])->name('bookings.accept');
    Route::post('/bookings/{id}/reject', [VendorBookingController::class, 'reject'])->name('bookings.reject');

    // Earnings
    Route::get('/earnings', [VendorEarningsController::class, 'index'])->name('earnings.index');

    // Profile
    Route::get('/profile', [VendorProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [VendorProfileController::class, 'update'])->name('profile.update');

    // Calendar
    Route::get('/calendar', [VendorDashboardController::class, 'calendar'])->name('calendar');

    // Services
    Route::get('/services', [VendorProfileController::class, 'services'])->name('services.index');
    Route::post('/services', [VendorProfileController::class, 'storeService'])->name('services.store');
    Route::delete('/services/{id}', [VendorProfileController::class, 'deleteService'])->name('services.destroy');
});

/*
|--------------------------------------------------------------------------
| Payment Gateway Routes
|--------------------------------------------------------------------------
*/

// ToyyibPay Callback (must be public)
Route::post('/toyyibpay/callback', [ToyyibPayCallbackController::class, 'handle'])->name('toyyibpay.callback');
