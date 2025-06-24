<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompletionStatusController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HealthFacilityController;
use App\Http\Controllers\MedicalDeviceCategoryController;
use App\Http\Controllers\MedicalDeviceController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TypeOfHealthFacilityController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndonesiaController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TypeOfWorkController;

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

Route::get('/provinces', [IndonesiaController::class, 'getProvinces']);
Route::get('/cities', [IndonesiaController::class, 'getCities']);
Route::get('/districts', [IndonesiaController::class, 'getDistricts']);
Route::get('/villages', [IndonesiaController::class, 'getVillages']);

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
    Route::delete('/permission/{id}', [PermissionController::class, 'destroy'])->middleware('check.permission:delete-permissions');

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
    Route::put('health-facility/{id}', [HealthFacilityController::class, 'update'])->middleware('check.permission:update-health-facility');
    Route::delete('health-facility/{id}', [HealthFacilityController::class, 'destroy'])->middleware('check.permission:delete-health-facility');

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

    //Region
    Route::get('region', [RegionController::class, 'index'])->middleware('check.permission:view-region');
    Route::get('region/{slug}', [RegionController::class, 'show'])->middleware('check.permission:show-region');
    Route::post('region', [RegionController::class, 'store'])->middleware('check.permission:create-region');
    Route::put('region/{slug}', [RegionController::class, 'update'])->middleware('check.permission:update-region');
    Route::delete('region/{slug}', [RegionController::class, 'destroy'])->middleware('check.permission:delete-region');

    //Division
    Route::get('division', [DivisionController::class, 'index'])->middleware('check.permission:view-division');
    Route::get('division/{slug}', [DivisionController::class, 'show'])->middleware('check.permission:show-division');
    Route::post('division', [DivisionController::class, 'store'])->middleware('check.permission:create-division');
    Route::put('division/{slug}', [DivisionController::class, 'update'])->middleware('check.permission:update-division');
    Route::delete('division/{slug}', [DivisionController::class, 'destroy'])->middleware('check.permission:delete-division');

    //Position
    Route::get('position', [PositionController::class, 'index'])->middleware('check.permission:view-position');
    Route::get('position/{slug}', [PositionController::class, 'show'])->middleware('check.permission:show-position');
    Route::post('position', [PositionController::class, 'store'])->middleware('check.permission:create-position');
    Route::put('position/{slug}', [PositionController::class, 'update'])->middleware('check.permission:update-position');
    Route::delete('position/{slug}', [PositionController::class, 'destroy'])->middleware('check.permission:delete-position');

    //Employee
    Route::get('employee', [EmployeeController::class, 'index'])->middleware('check.permission:view-employee');
    Route::get('employee/{id}', [EmployeeController::class, 'show'])->middleware('check.permission:show-employee');
    Route::post('employee', [EmployeeController::class, 'store'])->middleware('check.permission:create-employee');
    Route::put('employee/{id}', [EmployeeController::class, 'update'])->middleware('check.permission:update-employee');
    Route::delete('employee/{id}', [EmployeeController::class, 'destroy'])->middleware('check.permission:delete-employee');

    // Type Of Works
    Route::get('type-of-work', [TypeOfWorkController::class, 'index'])->middleware('check.permission:view-type-of-work');
    Route::get('type-of-work/{slug}', [TypeOfWorkController::class, 'show'])->middleware('check.permission:show-type-of-work');
    Route::post('type-of-work', [TypeOfWorkController::class, 'store'])->middleware('check.permission:create-type-of-work');
    Route::put('type-of-work/{id}', [TypeOfWorkController::class, 'update'])->middleware('check.permission:update-type-of-work');
    Route::delete('type-of-work/{slug}', [TypeOfWorkController::class, 'destroy'])->middleware('check.permission:delete-type-of-work');

    //Completion Status
    Route::get('completion-status', [CompletionStatusController::class, 'index'])->middleware('check.permission:view-completion-status');
    Route::get('completion-status/{slug}', [CompletionStatusController::class, 'show'])->middleware('check.permission:show-completion-status');
    Route::post('completion-status', [CompletionStatusController::class, 'store'])->middleware('check.permission:create-completion-status');
    Route::put('completion-status/{id}', [CompletionStatusController::class, 'update'])->middleware('check.permission:update-completion-status');
    Route::delete('completion-status/{slug}', [CompletionStatusController::class, 'destroy'])->middleware('check.permission:delete-completion-status');

    //Report
    Route::get('report', [ReportController::class, 'index'])->middleware('check.permission:view-report');
    Route::get('report/{id}', [ReportController::class, 'show'])->middleware('check.permission:show-report');
    Route::post('report', [ReportController::class, 'store'])->middleware('check.permission:create-report');
    Route::put('report/{id}', [ReportController::class, 'update'])->middleware('check.permission:update-report');
    Route::delete('report/{id}', [ReportController::class, 'destroy'])->middleware('check.permission:delete-report');
    Route::put('report/{reportId}/details', [ReportController::class, 'updateReportDetail'])->middleware('check.permission:update-report');
    Route::delete('report/{id}', [ReportController::class, 'destroy'])->middleware('check.permission:delete-report');

    Route::put('report/{reportId}/complete', [ReportController::class, 'completeReport'])->middleware('check.permission:update-report');
    Route::get('employees/{employeeId}/report', [ReportController::class, 'getReportsByEmployee'])->middleware('check.permission:view-report');

    // Route untuk serving signature files
    Route::get('signatures/{type}/{fileName}', [ReportController::class, 'getSignatureFile'])->middleware('check.permission:view-report')
        ->name('signature.file')
        ->where('type', 'employee|customer');
});
