<?php

use App\Http\Controllers\ChargingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardEntryController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationStatusController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
| Open to any logged-in user, including Pending and Rejected renters,
| so they can still reach their status page and manage their profile.
*/

Route::middleware('auth')->group(function () {
    // Single entry point after login; sends each user to the dashboard
    // that matches their role and registration status.
    Route::get('/dashboard', [
        DashboardEntryController::class,
        'index',
    ])->name('dashboard');

    // Kept outside 'approved.renter' so unapproved renters are not
    // caught in a redirect loop.
    Route::get('/registration/status', [
        RegistrationStatusController::class,
        'show',
    ])->name('registration.status');

    Route::get('/profile', [
        ProfileController::class,
        'edit',
    ])->name('profile.edit');

    Route::patch('/profile', [
        ProfileController::class,
        'update',
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy',
    ])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
| Restricted by the 'admin' middleware; renters receive 403.
*/

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [
        DashboardController::class,
        'index',
    ])->name('admin.dashboard');

    Route::get('/garages', [
        GarageController::class,
        'index',
    ])->name('garages.index');

    Route::get('/garages/create', [
        GarageController::class,
        'create',
    ])->name('garages.create');

    Route::post('/garages', [
        GarageController::class,
        'store',
    ])->name('garages.store');

    // Only admins may extend a rental; renters request it offline.
    Route::post('/admin/rentals/{rental}/extend', [
        RentalController::class,
        'extend',
    ])
        ->whereUuid('rental')
        ->name('admin.rentals.extend');
});

/*
|--------------------------------------------------------------------------
| Approved Renter Routes
|--------------------------------------------------------------------------
| Renters reach these only after an admin approves their registration.
| whereUuid() rejects malformed IDs before they reach the controller.
*/

Route::middleware(['auth', 'approved.renter'])->group(function () {
    
    Route::view('/renter/dashboard', 'dashboard')
        ->name('renter.dashboard');

    // Vehicles

    Route::get('/vehicles', [
        VehicleController::class,
        'index',
    ])->name('vehicles.index');

    Route::get('/vehicles/create', [
        VehicleController::class,
        'create',
    ])->name('vehicles.create');

    Route::post('/vehicles', [
        VehicleController::class,
        'store',
    ])->name('vehicles.store');

    Route::get('/vehicles/{vehicle}', [
        VehicleController::class,
        'show',
    ])
        ->whereUuid('vehicle')
        ->name('vehicles.show');

    // Rentals

    Route::get('/rentals', [
        RentalController::class,
        'index',
    ])->name('rentals.index');

    Route::get('/rentals/create', [
        RentalController::class,
        'create',
    ])->name('rentals.create');

    Route::post('/rentals', [
        RentalController::class,
        'store',
    ])->name('rentals.store');

    // Charging is nested under a rental because every charge
    // belongs to one active rental.

    Route::get('/rentals/{rental}/charging', [
        ChargingController::class,
        'index',
    ])
        ->whereUuid('rental')
        ->name('charging.index');

    Route::get('/rentals/{rental}/charging/create', [
        ChargingController::class,
        'create',
    ])
        ->whereUuid('rental')
        ->name('charging.create');

    // Shows the calculated amount before the renter confirms payment.
    Route::post('/rentals/{rental}/charging/preview', [
        ChargingController::class,
        'preview',
    ])
        ->whereUuid('rental')
        ->name('charging.preview');

    Route::post('/rentals/{rental}/charging', [
        ChargingController::class,
        'store',
    ])
        ->whereUuid('rental')
        ->name('charging.store');

    // Declared after '/rentals/create' so "create" is never
    // treated as a rental ID.
    Route::get('/rentals/{rental}', [
        RentalController::class,
        'show',
    ])
        ->whereUuid('rental')
        ->name('rentals.show');
});

require __DIR__.'/auth.php';