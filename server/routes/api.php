<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\EmergencyController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\InvoiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Public Vendor Routes
Route::prefix('vendors')->group(function () {
    Route::get('/', [VendorController::class, 'index']);
    Route::get('/featured', [VendorController::class, 'featured']);
    Route::get('/categories', [VendorController::class, 'categories']);
    Route::get('/cities', [VendorController::class, 'cities']);
    Route::get('/{slug}', [VendorController::class, 'show']);
    Route::get('/{slug}/availability', [VendorController::class, 'checkAvailability']);
    Route::get('/{slug}/reviews', [ReviewController::class, 'vendorReviews']);
    Route::get('/{slug}/packages', [PackageController::class, 'vendorPackages']);
    Route::get('/{slug}/packages/{packageId}', [PackageController::class, 'showPublic']);
});

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/read', [NotificationController::class, 'destroyRead']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Client Bookings
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::get('/dashboard', [BookingController::class, 'dashboard']);
        Route::get('/upcoming', [BookingController::class, 'upcoming']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/{id}', [BookingController::class, 'show']);
        Route::post('/{id}/confirm-payment', [BookingController::class, 'confirmPayment']);
        Route::post('/{id}/cancel', [BookingController::class, 'cancel']);
        Route::post('/{id}/complete', [BookingController::class, 'markCompleted']);
    });

    // Client Reviews
    Route::prefix('reviews')->group(function () {
        Route::get('/my', [ReviewController::class, 'myReviews']);
        Route::get('/pending', [ReviewController::class, 'pendingReviews']);
        Route::post('/', [ReviewController::class, 'store']);
        Route::put('/{id}', [ReviewController::class, 'update']);
        Route::delete('/{id}', [ReviewController::class, 'destroy']);
    });

    // Client Invoices
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::get('/{id}/download', [InvoiceController::class, 'download']);
        Route::get('/{id}/view', [InvoiceController::class, 'viewPdf']);
    });

    // Booking Invoices
    Route::get('/bookings/{bookingId}/invoice', [InvoiceController::class, 'getBookingInvoice']);
    Route::get('/bookings/{bookingId}/invoice/download', [InvoiceController::class, 'downloadBookingInvoice']);
    Route::get('/bookings/{bookingId}/payments/{paymentId}/receipt', [InvoiceController::class, 'getPaymentReceipt']);

    // Emergency (Client)
    Route::prefix('emergency')->group(function () {
        Route::post('/{eventId}/trigger', [EmergencyController::class, 'triggerEmergency']);
        Route::get('/{eventId}/status', [EmergencyController::class, 'status']);
    });

    // Vendor Dashboard Routes
    Route::prefix('vendor')->middleware('role:vendor')->group(function () {
        Route::get('/dashboard', [VendorController::class, 'dashboard']);
        Route::put('/profile', [VendorController::class, 'updateProfile']);
        Route::put('/contact-profile', [VendorController::class, 'updateContactProfile']);
        Route::post('/portfolio', [VendorController::class, 'uploadPortfolio']);
        Route::delete('/portfolio/{id}', [VendorController::class, 'deletePortfolio']);

        // Emergency requests for vendors
        Route::get('/emergency-requests', [EmergencyController::class, 'vendorEmergencyRequests']);
        Route::post('/emergency/{assignmentId}/accept', [EmergencyController::class, 'acceptEmergency']);
        Route::post('/emergency/{assignmentId}/reject', [EmergencyController::class, 'rejectEmergency']);

        // Vendor reviews management
        Route::get('/reviews', [ReviewController::class, 'vendorReceivedReviews']);
        Route::post('/reviews/{id}/respond', [ReviewController::class, 'respondToReview']);

        // Vendor payouts
        Route::prefix('payouts')->group(function () {
            Route::get('/summary', [PayoutController::class, 'earningsSummary']);
            Route::get('/earnings', [PayoutController::class, 'earnings']);
            Route::get('/history', [PayoutController::class, 'payouts']);
            Route::get('/{id}', [PayoutController::class, 'showPayout']);
            Route::put('/bank-details', [PayoutController::class, 'updateBankDetails']);
            Route::post('/request', [PayoutController::class, 'requestPayout']);
            Route::post('/{id}/cancel', [PayoutController::class, 'cancelPayout']);
        });

        // Vendor calendar
        Route::prefix('calendar')->group(function () {
            Route::get('/month', [CalendarController::class, 'monthView']);
            Route::get('/range', [CalendarController::class, 'dateRange']);
            Route::get('/summary', [CalendarController::class, 'summary']);
            Route::get('/bookings', [CalendarController::class, 'upcomingBookings']);
            Route::post('/availability', [CalendarController::class, 'setAvailability']);
            Route::post('/availability/bulk', [CalendarController::class, 'bulkSetAvailability']);
            Route::post('/block', [CalendarController::class, 'blockDateRange']);
            Route::delete('/clear', [CalendarController::class, 'clearAvailability']);
        });

        // Vendor packages
        Route::prefix('packages')->group(function () {
            Route::get('/', [PackageController::class, 'index']);
            Route::post('/', [PackageController::class, 'store']);
            Route::put('/reorder', [PackageController::class, 'reorder']);
            Route::get('/{id}', [PackageController::class, 'show']);
            Route::put('/{id}', [PackageController::class, 'update']);
            Route::delete('/{id}', [PackageController::class, 'destroy']);
            Route::post('/{id}/toggle-status', [PackageController::class, 'toggleStatus']);
            Route::post('/{id}/duplicate', [PackageController::class, 'duplicate']);
        });

        // Vendor invoices/statements
        Route::get('/invoices', [InvoiceController::class, 'vendorInvoices']);
    });

    // Admin Routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        
        // Vendors
        Route::get('/vendors', [AdminController::class, 'vendors']);
        Route::post('/vendors/{id}/verify', [AdminController::class, 'verifyVendor']);
        Route::post('/vendors/{id}/suspend', [AdminController::class, 'suspendVendor']);
        Route::post('/vendors/{id}/reliability', [AdminController::class, 'adjustReliability']);
        
        // Emergencies
        Route::get('/emergencies', [AdminController::class, 'emergencies']);
        Route::post('/emergencies/{id}/override', [AdminController::class, 'overrideBackup']);
        
        // Categories
        Route::get('/categories', [AdminController::class, 'categories']);
        Route::post('/categories', [AdminController::class, 'storeCategory']);
        Route::put('/categories/{id}', [AdminController::class, 'updateCategory']);
        
        // Users
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        
        // Audit Log
        Route::get('/audit-log', [AdminController::class, 'auditLog']);
        
        // Reports
        Route::get('/reports', [AdminController::class, 'reports']);
        
        // Settings
        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);

        // Reviews moderation
        Route::get('/reviews', [ReviewController::class, 'adminIndex']);
        Route::post('/reviews/{id}/toggle-visibility', [ReviewController::class, 'toggleVisibility']);

        // Payouts management
        Route::prefix('payouts')->group(function () {
            Route::get('/', [PayoutController::class, 'adminIndex']);
            Route::get('/{id}', [PayoutController::class, 'adminShow']);
            Route::post('/{id}/process', [PayoutController::class, 'processPayout']);
            Route::post('/{id}/complete', [PayoutController::class, 'completePayout']);
            Route::post('/{id}/fail', [PayoutController::class, 'failPayout']);
        });

        // Invoice management
        Route::prefix('invoices')->group(function () {
            Route::get('/', [InvoiceController::class, 'adminIndex']);
            Route::get('/{id}', [InvoiceController::class, 'adminShow']);
            Route::get('/{id}/download', [InvoiceController::class, 'adminDownload']);
            Route::post('/{id}/mark-paid', [InvoiceController::class, 'markAsPaid']);
            Route::post('/{id}/cancel', [InvoiceController::class, 'cancelInvoice']);
        });
    });
});
