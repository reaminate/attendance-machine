<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArrivalWithinGraceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Arriving at 09:10 is within grace so late minutes are zero.
     */
    public function test_arrival_within_grace_gives_zero_late_minutes_but_reduces_worked_minutes(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:10:00', '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 09:10', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-01 17:00', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(0, $record->late_minutes);
        $this->assertSame(410, $record->worked_minutes);
        $this->assertSame(Status::HALF_DAY, $record->status);
    }

    /**
     * Arriving one minute after grace ends counts that minute as late.
     */
    public function test_arrival_one_minute_after_grace_gives_one_late_minute(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:11:00', '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame(1, $record->late_minutes);
    }
}
