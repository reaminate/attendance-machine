<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LateArrivalAndEarlyDepartureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Arriving at 09:30 and leaving at 16:30 records both late and early departure minutes without either overwriting the other.
     */
    public function test_late_arrival_and_early_departure_are_both_recorded(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:30:00', '2026-10-01 16:30:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 09:30', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-01 16:30', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(20, $record->late_minutes);
        $this->assertSame(30, $record->early_departure_minutes);
        $this->assertSame(360, $record->worked_minutes);
        $this->assertSame(0, $record->overtime_minutes);
        $this->assertSame(Status::HALF_DAY, $record->status);
    }

    /**
     * A late, short session below the half-day threshold is insufficient_hours.
     */
    public function test_late_and_early_session_below_half_day_threshold_is_insufficient_hours(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 11:00:00', '2026-10-01 14:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame(110, $record->late_minutes);
        $this->assertSame(180, $record->early_departure_minutes);
        $this->assertSame(120, $record->worked_minutes);
        $this->assertSame(Status::INSUFFICIENT_HOURS, $record->status);
    }
}
