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
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PasswordResetController;


Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Lupa password: kode 6 digit dikirim ke email terdaftar
Route::get('/lupa-password', [PasswordResetController::class, 'showRequest'])->name('forgot.show');
Route::post('/lupa-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:5,1')->name('forgot.send');
Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('reset.show');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('reset.update');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


Route::middleware('auth')->group(function () {
   
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/config/update', [DashboardController::class, 'updateConfig'])->name('config.update');

 
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

   
    Route::get('/leave', [LeaveRequestController::class, 'index'])->name('leave.index');
    Route::get('/leave/{id}/document', [LeaveRequestController::class, 'document'])->whereNumber('id')->name('leave.document');
    Route::post('/leave/store', [LeaveRequestController::class, 'store'])->name('leave.store');
    Route::post('/leave/verify/{id}/{status}', [LeaveRequestController::class, 'verify'])->name('leave.verify');

    
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/schedule', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::get('/schedule/{id}/edit', [ScheduleController::class, 'edit'])->whereNumber('id')->name('schedule.edit');
    Route::put('/schedule/{id}', [ScheduleController::class, 'update'])->whereNumber('id')->name('schedule.update');
    Route::delete('/schedule/{id}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');

   
    Route::get('/journal/create/{schedule_id}', [JournalController::class, 'create'])->name('journal.create');
    Route::post('/journal/store', [JournalController::class, 'store'])->name('journal.store');
    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
    Route::get('/journal/{id}', [JournalController::class, 'show'])->whereNumber('id')->name('journal.show');
    Route::get('/journal/{id}/photo', [JournalController::class, 'photo'])->whereNumber('id')->name('journal.photo');
    Route::get('/journal/{id}/attendance', [JournalController::class, 'editAttendance'])->whereNumber('id')->name('journal.attendance.edit');
    Route::put('/journal/{id}/attendance', [JournalController::class, 'updateAttendance'])->whereNumber('id')->name('journal.attendance.update');

    Route::get('/presensi', [AttendanceController::class, 'mine'])->name('attendance.mine');

    
    Route::get('/reports/attendance', [ReportController::class, 'attendanceReport'])->name('reports.attendance');
    Route::get('/reports/attendance/export-csv', [ReportController::class, 'exportAttendanceCsv'])->name('reports.attendance.export');
    Route::get('/reports/leave-daily', [ReportController::class, 'leaveDaily'])->name('reports.leave.daily');
    Route::get('/reports/leave-daily/export-csv', [ReportController::class, 'exportLeaveDailyCsv'])->name('reports.leave.daily.export');

    
    Route::get('/master/users', [MasterDataController::class, 'indexUsers'])->name('users.index');
    Route::post('/master/users', [MasterDataController::class, 'storeUser'])->name('master.users.store');
    Route::delete('/master/users/{id}', [MasterDataController::class, 'destroyUser'])->name('master.users.destroy');

    Route::get('/master/classes', [MasterDataController::class, 'indexClasses'])->name('classes.index');
    Route::post('/master/classes', [MasterDataController::class, 'storeClass'])->name('master.classes.store');
    Route::delete('/master/classes/{id}', [MasterDataController::class, 'destroyClass'])->name('master.classes.destroy');

    Route::get('/master/rooms', [MasterDataController::class, 'indexRooms'])->name('rooms.index');
    Route::post('/master/rooms', [MasterDataController::class, 'storeRoom'])->name('master.rooms.store');
    Route::delete('/master/rooms/{id}', [MasterDataController::class, 'destroyRoom'])->name('master.rooms.destroy');

    // Import data sekolah (CSV)
    Route::get('/master/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/master/import', [ImportController::class, 'run'])->name('import.run');
    Route::get('/master/import/template/{type}', [ImportController::class, 'template'])->name('import.template');
});
