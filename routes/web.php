<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PasswordResetController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.forgot');
Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp'])->middleware('throttle:10,1')->name('password.send');
Route::get('/forgot-password/verify', [PasswordResetController::class, 'showVerify'])->name('password.verify');
Route::post('/forgot-password/verify', [PasswordResetController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('password.verify.submit');
Route::get('/forgot-password/reset', [PasswordResetController::class, 'showReset'])->name('password.reset.form');
Route::post('/forgot-password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.reset.submit');


Route::middleware('auth')->group(function () {
   
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/pengaturan/sistem', [DashboardController::class, 'updateConfig'])->name('config.update');

 
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

   
    Route::get('/leave', [LeaveRequestController::class, 'index'])->name('leave.index');
    Route::get('/leave/{id}/document', [LeaveRequestController::class, 'document'])->whereNumber('id')->name('leave.document');
    Route::post('/leave/store', [LeaveRequestController::class, 'store'])->name('leave.store');
    Route::post('/leave/verify/{id}/{status}', [LeaveRequestController::class, 'verify'])->name('leave.verify');

    
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/schedule', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::delete('/schedule/{id}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');

   
    Route::get('/journal/create/{schedule_id}', [JournalController::class, 'create'])->name('journal.create');
    Route::post('/journal/store', [JournalController::class, 'store'])->name('journal.store');
    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
    Route::get('/journal/{id}', [JournalController::class, 'show'])->whereNumber('id')->name('journal.show');
    Route::get('/journal/{id}/edit', [JournalController::class, 'edit'])->whereNumber('id')->name('journal.edit');
    Route::put('/journal/{id}', [JournalController::class, 'update'])->whereNumber('id')->name('journal.update');
    Route::get('/journal/{id}/photo', [JournalController::class, 'photo'])->whereNumber('id')->name('journal.photo');

    Route::get('/presensi', [AttendanceController::class, 'mine'])->name('attendance.mine');

    
    Route::get('/reports/attendance', [ReportController::class, 'attendanceReport'])->name('reports.attendance');
    Route::get('/reports/attendance/export-csv', [ReportController::class, 'exportAttendanceCsv'])->name('reports.attendance.export');
    Route::get('/reports/daily', [ReportController::class, 'dailyRecap'])->name('reports.daily');

    
    Route::get('/master/users', [MasterDataController::class, 'indexUsers'])->name('users.index');
    Route::post('/master/users', [MasterDataController::class, 'storeUser'])->name('master.users.store');
    Route::delete('/master/users/{id}', [MasterDataController::class, 'destroyUser'])->name('master.users.destroy');

    Route::get('/master/classes', [MasterDataController::class, 'indexClasses'])->name('classes.index');
    Route::post('/master/classes', [MasterDataController::class, 'storeClass'])->name('master.classes.store');
    Route::delete('/master/classes/{id}', [MasterDataController::class, 'destroyClass'])->name('master.classes.destroy');

    Route::get('/master/rooms', [MasterDataController::class, 'indexRooms'])->name('rooms.index');
    Route::post('/master/rooms', [MasterDataController::class, 'storeRoom'])->name('master.rooms.store');
    Route::delete('/master/rooms/{id}', [MasterDataController::class, 'destroyRoom'])->name('master.rooms.destroy');
    Route::post('/master/users/import', [MasterDataController::class, 'importUsers'])->name('master.users.import');
    Route::post('/master/classes/import', [MasterDataController::class, 'importClasses'])->name('master.classes.import');
    Route::post('/master/rooms/import', [MasterDataController::class, 'importRooms'])->name('master.rooms.import');
    Route::post('/master/users/delete-all', [MasterDataController::class, 'destroyAllUsers'])->name('master.users.destroyAll');
    Route::post('/master/classes/delete-all', [MasterDataController::class, 'destroyAllClasses'])->name('master.classes.destroyAll');
    Route::post('/master/rooms/delete-all', [MasterDataController::class, 'destroyAllRooms'])->name('master.rooms.destroyAll');
    Route::get('/master/template/{type}', [MasterDataController::class, 'template'])->name('master.template');

    Route::post('/pengaturan/sistem', [DashboardController::class, 'updateConfig'])
    ->middleware('role:1')->name('config.update');

    Route::post('/schedule', [ScheduleController::class, 'store'])
    ->middleware('role:2')->name('schedule.store');

    Route::delete('/schedule/{id}', [ScheduleController::class, 'destroy'])
    ->middleware('role:1,2')->name('schedule.destroy');

    Route::post('/leave/verify/{id}/{status}', [LeaveRequestController::class, 'verify'])
    ->middleware('role:3,4')->name('leave.verify');
});