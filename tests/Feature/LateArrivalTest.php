<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LateArrivalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Arriving at 09:25 gives 15 late minutes after grace, and status follows worked time rather than lateness.
     */
    public function test_late_arrival_subtracts_grace_from_late_minutes_and_status_follows_worked_time(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:25:00', '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 09:25', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-01 17:00', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(15, $record->late_minutes);
        $this->assertSame(395, $record->worked_minutes);
        $this->assertSame(0, $record->early_departure_minutes);
        $this->assertSame(0, $record->overtime_minutes);
        $this->assertSame(Status::HALF_DAY, $record->status);
    }
}
