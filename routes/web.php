<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::redirect('/', '/attendance');

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/list', [AttendanceController::class, 'list']);
    Route::get('/attendance/detail/{attendanceRecord}', [AttendanceController::class, 'show']);
    Route::post('/attendance/detail/{attendanceRecord}', [AttendanceController::class, 'requestCorrection']);

    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index']);
});

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'create']);
    Route::post('/admin/login', [AdminAuthController::class, 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'destroy']);
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index']);
    Route::get('/admin/attendance/staff/{user}', [AdminAttendanceController::class, 'staff']);
    Route::get('/admin/attendance/{attendanceRecord}', [AdminAttendanceController::class, 'show']);
    Route::post('/admin/attendance/{attendanceRecord}', [AdminAttendanceController::class, 'update']);
    Route::get('/admin/staff/list', [StaffController::class, 'index']);
    Route::get('/stamp_correction_request/approve/{stampCorrectionRequest}', [StampCorrectionRequestController::class, 'show']);
    Route::post('/stamp_correction_request/approve/{stampCorrectionRequest}', [StampCorrectionRequestController::class, 'approve']);
});
