<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyArrivalIsNotOvertimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Arriving at 08:30 keeps 08:30 as first IN and counts toward worked time, but gives no overtime.
     */
    public function test_early_arrival_counts_as_worked_time_but_not_overtime(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 08:30:00', '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 08:30', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-01 17:00', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(450, $record->worked_minutes);
        $this->assertSame(0, $record->overtime_minutes);
        $this->assertSame(0, $record->late_minutes);
        $this->assertSame(0, $record->early_departure_minutes);
        $this->assertSame(Status::PRESENT, $record->status);
    }
}
