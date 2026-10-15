<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleSessionsAndUnpaidBreakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Two sessions with a one-hour gap use the gap as the break and do not deduct a second break.
     */
    public function test_one_hour_gap_between_sessions_counts_as_the_break(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 12:00:00'],
            ['2026-10-01 13:00:00', '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 09:00', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-01 17:00', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(420, $record->worked_minutes);
        $this->assertSame(Status::PRESENT, $record->status);
    }

    /**
     * A two-hour gap covers the break completely, so worked time is just the sum of the sessions.
     */
    public function test_two_hour_gap_between_sessions_reduces_worked_minutes_without_extra_deduction(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 13:00:00'],
            ['2026-10-01 15:00:00', '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame(360, $record->worked_minutes);
        $this->assertSame(Status::HALF_DAY, $record->status);
    }

    /**
     * A thirty-minute gap covers half the break, so the remaining thirty minutes are still deducted.
     */
    public function test_short_gap_between_sessions_deducts_the_remaining_break(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 12:00:00'],
            ['2026-10-01 12:30:00', '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame(420, $record->worked_minutes);
        $this->assertSame(Status::PRESENT, $record->status);
    }
}
