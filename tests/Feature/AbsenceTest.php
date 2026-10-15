<?php

namespace Tests\Feature;

use App\Enum\Remarks;
use App\Enum\Status;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An assigned employee with no logs is absent with zero minute metrics and null IN/OUT.
     */
    public function test_assigned_employee_with_no_logs_is_absent_with_zero_metrics(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertNull($record->first_in);
        $this->assertNull($record->last_out);
        $this->assertSame(0, $record->worked_minutes);
        $this->assertSame(0, $record->late_minutes);
        $this->assertSame(0, $record->early_departure_minutes);
        $this->assertSame(0, $record->overtime_minutes);
        $this->assertSame(Status::ABSENT, $record->status);
        $this->assertSame([Remarks::NO_ATTENDANCE_LOGS->value], $record->remarks);
    }

    /**
     * An employee without a shift assignment never gets an absence record.
     */
    public function test_employee_without_shift_assignment_gets_no_record(): void
    {
        Employee::create(['name' => 'Unassigned', 'employee_code' => 'EMP001']);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $this->assertDatabaseEmpty('attendance_records');
    }
}
