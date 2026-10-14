<?php

use App\Http\Controllers\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

Route::controller(AttendanceRecordController::class)->prefix('attendance-record')->group(function () {
    Route::post('', 'store');
    Route::get('', 'index');
});
