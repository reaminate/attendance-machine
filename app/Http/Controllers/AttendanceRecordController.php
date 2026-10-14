<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Services\AttendanceGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class AttendanceRecordController extends Controller
{
    public function __construct(private AttendanceGenerator $generator) {}

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
     * Generate or refresh the attendance records for the requested range.
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $result = $this->generator->generate(
            CarbonImmutable::createFromFormat('!Y-m-d', $request->validated('from')),
            CarbonImmutable::createFromFormat('!Y-m-d', $request->validated('to')),
            array_map('intval', $request->validated('employee', [])),
        );

        return response()->json([
            'summary' => $result['summary'],
            'records' => AttendanceRecordResource::collection($result['records'])->resolve($request),
        ], 201);
    }
}
