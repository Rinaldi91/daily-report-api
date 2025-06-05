<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthFacilityController;
use App\Http\Controllers\MedicalDeviceCategoryController;
use App\Http\Controllers\MedicalDeviceController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TypeOfHealthFacilityController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // User routes
    Route::get('/users', [UserController::class, 'index'])->middleware('check.permission:view-users');
    Route::get('/users/{id}', [UserController::class, 'show'])->middleware('check.permission:view-users');
    Route::post('/users', [UserController::class, 'store'])->middleware('check.permission:create-users');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('check.permission:update-users');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('check.permission:delete-users');
    
    // Role routes
    Route::get('/roles', [RoleController::class, 'index'])->middleware('check.permission:view-roles');
    Route::get('/roles/{id}', [RoleController::class, 'show'])->middleware('check.permission:show-roles');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('check.permission:create-roles');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->middleware('check.permission:update-roles');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->middleware('check.permission:delete-roles');

    // Permission routes
    Route::get('/permission', [PermissionController::class, 'index'])->middleware('check.permission:view-permissions');
    Route::get('/permission-with-pagination', [PermissionController::class, 'paginate'])->middleware('check.permission:view-permissions');
    Route::get('/permission/{id}', [PermissionController::class, 'show'])->middleware('check.permission:show-permissions');
    Route::post('/permission', [PermissionController::class, 'store'])->middleware('check.permission:create-permissions');
    Route::put('/permission/{id}', [PermissionController::class, 'update'])->middleware('check.permission:update-permissions');
    Route::delete('/permission/{id}', [PermissionController::class, 'delete'])->middleware('check.permission:delete-permissions');

    //Type Of Health Facility
    Route::get('type-of-health-facility', [TypeOfHealthFacilityController::class, 'index'])->middleware('check.permission:view-type-of-health-facility');
    Route::get('type-of-health-facility/{slug}', [TypeOfHealthFacilityController::class, 'show'])->middleware('check.permission:show-type-of-health-facility');
    Route::post('type-of-health-facility', [TypeOfHealthFacilityController::class, 'store'])->middleware('check.permission:create-type-of-health-facility');
    Route::put('type-of-health-facility/{slug}', [TypeOfHealthFacilityController::class, 'update'])->middleware('check.permission:update-type-of-health-facility');
    Route::delete('type-of-health-facility/{slug}', [TypeOfHealthFacilityController::class, 'destroy'])->middleware('check.permission:delete-type-of-health-facility');

    //Health Facility
    Route::get('health-facility', [HealthFacilityController::class, 'index'])->middleware('check.permission:view-health-facility');
    Route::get('health-facility/{slug}', [HealthFacilityController::class, 'show'])->middleware('check.permission:show-health-facility');
    Route::post('health-facility', [HealthFacilityController::class, 'store'])->middleware('check.permission:create-health-facility');
    Route::put('health-facility/{slug}', [HealthFacilityController::class, 'update'])->middleware('check.permission:update-health-facility');
    Route::delete('health-facility/{slug}', [HealthFacilityController::class, 'destroy'])->middleware('check.permission:delete-health-facility');

    //Medical Device Category
    Route::get('medical-device-category', [MedicalDeviceCategoryController::class, 'index'])->middleware('check.permission:view-medical-device-category');
    Route::get('medical-device-category/{slug}', [MedicalDeviceCategoryController::class, 'show'])->middleware('check.permission:show-medical-device-category');
    Route::post('medical-device-category', [MedicalDeviceCategoryController::class, 'store'])->middleware('check.permission:create-medical-device-category');
    Route::put('medical-device-category/{slug}', [MedicalDeviceCategoryController::class, 'update'])->middleware('check.permission:update-medical-device-category');
    Route::delete('medical-device-category/{slug}', [MedicalDeviceCategoryController::class, 'destroy'])->middleware('check.permission:delete-medical-device-category');
    
    //Medical Device
    Route::get('medical-device', [MedicalDeviceController::class, 'index'])->middleware('check.permission:view-medical-device');
    Route::get('medical-device/{id}', [MedicalDeviceController::class, 'show'])->middleware('check.permission:show-medical-device');
    Route::post('medical-device', [MedicalDeviceController::class, 'store'])->middleware('check.permission:create-medical-device');
    Route::put('medical-device/{id}', [MedicalDeviceController::class, 'update'])->middleware('check.permission:update-medical-device');
    Route::delete('medical-device/{id}', [MedicalDeviceController::class, 'destroy'])->middleware('check.permission:delete-medical-device');
});