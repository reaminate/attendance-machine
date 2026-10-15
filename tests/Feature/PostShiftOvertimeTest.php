<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostShiftOvertimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Leaving at 18:30 gives 90 overtime minutes, included in worked time with the break deducted once.
     */
    public function test_work_after_shift_end_counts_as_overtime_and_worked_time(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 18:30:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 09:00', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-01 18:30', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(90, $record->overtime_minutes);
        $this->assertSame(510, $record->worked_minutes);
        $this->assertSame(0, $record->late_minutes);
        $this->assertSame(0, $record->early_departure_minutes);
        $this->assertSame(Status::PRESENT, $record->status);
    }

    /**
     * Less than 30 minutes after shift end gives zero overtime but still counts as worked time.
     */
    public function test_under_thirty_minutes_after_shift_end_gives_zero_overtime(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 17:29:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame(0, $record->overtime_minutes);
        $this->assertSame(449, $record->worked_minutes);
    }

    /**
     * Exactly 30 minutes after shift end meets the minimum and counts all 30 minutes.
     */
    public function test_exactly_thirty_minutes_after_shift_end_gives_thirty_overtime_minutes(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 17:30:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame(30, $record->overtime_minutes);
        $this->assertSame(450, $record->worked_minutes);
    }
}
