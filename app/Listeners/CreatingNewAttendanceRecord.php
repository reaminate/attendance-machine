<?php

namespace App\Listeners;

use App\Events\CreateAttendanceRecord;
use App\Models\AttendanceRecord;
use App\Models\EmployeeShiftAssignment;

class CreatingNewAttendanceRecord
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CreateAttendanceRecord $event): void
    {
        $now = now();

        $rows = EmployeeShiftAssignment::query()
            ->distinct()
            ->get(['employee_id', 'shift_id', 'shift_date'])
            ->map(fn ($assignment) => [
                'employee_id' => $assignment->employee_id,
                'shift_id' => $assignment->shift_id,
                'shift_date' => $assignment->shift_date->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();
        AttendanceRecord::insertOrIgnore($rows);
    }
}
