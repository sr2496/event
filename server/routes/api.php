<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\EmergencyController;
use App\Http\Controllers\Api\AdminController;

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
    });
});
