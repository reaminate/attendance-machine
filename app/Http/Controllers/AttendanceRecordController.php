<?php

namespace App\Http\Controllers;

use App\Events\CreateAttendanceRecord;
use App\Events\UpdateAttendanceRecord;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Http\Requests\StoreAttendanceRecordRequest;
use App\Http\Requests\UpdateAttendanceRecordRequest;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;

class AttendanceRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attendanceRecords = AttendanceRecord::query()
        ->withMin(['attendances' => fn ($query) => $query
            ->whereColumn('attendances.logged_at', '>=', 'attendance_records.shift_date'),
        ], 'logged_at')
        ->orderBy('attendances_min_logged_at')
        ->orderBy('id')
        ->get();

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        CreateAttendanceRecord::dispatch();

        return response('', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AttendanceRecord $attendanceRecord)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update()
    {
        UpdateAttendanceRecord::dispatch();

        return response('', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttendanceRecord $attendanceRecord)
    {
        //
    }
}
