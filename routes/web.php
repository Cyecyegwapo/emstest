<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [EventController::class, 'index'])->name('home');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    // Common authenticated routes
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Events registration for all authenticated users
    Route::post('/events/{event}/register', [EventRegistrationController::class, 'register'])->name('events.register');
    Route::get('/my-events', [EventRegistrationController::class, 'myEvents'])->name('events.my');

    // User dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin and Super Admin routes
    Route::middleware('role:' . User::ROLE_ADMIN . ',' . User::ROLE_SUPER_ADMIN)->group(function () {
        // Events management
        Route::resource('admin/events', EventController::class, ['as' => 'admin'])
            ->except(['index', 'show']);

        // Event registrations management
        Route::get('admin/events/{event}/registrations', [EventRegistrationController::class, 'index'])
            ->name('admin.events.registrations.index');
        Route::patch('admin/registrations/{registration}/status', [EventRegistrationController::class, 'updateStatus'])
            ->name('admin.registrations.status');
        Route::patch('admin/registrations/{registration}/attendance', [EventRegistrationController::class, 'updateAttendance'])
            ->name('admin.registrations.attendance');

        // Categories management
        Route::resource('admin/categories', EventCategoryController::class, ['as' => 'admin']);

        // Admin dashboard
        Route::get('admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
    });

    // Super Admin only routes
    Route::middleware('role:' . User::ROLE_SUPER_ADMIN)->group(function () {
        // User management
        Route::resource('admin/users', UserController::class, ['as' => 'admin']);

        // Super admin dashboard
        Route::get('admin/super-dashboard', [DashboardController::class, 'superAdmin'])->name('admin.super-dashboard');
    });
});
