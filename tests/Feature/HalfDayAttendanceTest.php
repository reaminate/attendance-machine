<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalfDayAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A 09:00–14:00 session deducts the break once, measures early departure against 17:00,
     * and is insufficient_hours because worked time must exceed the half-day threshold.
     */
    public function test_session_ending_at_1400_deducts_break_once_and_measures_early_departure_against_shift_end(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 14:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 09:00', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-01 14:00', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(240, $record->worked_minutes);
        $this->assertSame(180, $record->early_departure_minutes);
        $this->assertSame(0, $record->late_minutes);
        $this->assertSame(0, $record->overtime_minutes);
        $this->assertSame(Status::INSUFFICIENT_HOURS, $record->status);
    }

    /**
     * Worked time above the half-day threshold but below the full-day threshold is half_day.
     */
    public function test_worked_minutes_above_half_day_threshold_is_half_day(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 14:01:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame(241, $record->worked_minutes);
        $this->assertSame(Status::HALF_DAY, $record->status);
    }
}
