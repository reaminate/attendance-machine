<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyDepartureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Leaving at 16:30 gives 30 early departure minutes and the status follows worked time.
     */
    public function test_leaving_at_1630_gives_30_early_departure_minutes(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 16:30:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 09:00', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-01 16:30', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(30, $record->early_departure_minutes);
        $this->assertSame(390, $record->worked_minutes);
        $this->assertSame(0, $record->late_minutes);
        $this->assertSame(0, $record->overtime_minutes);
        $this->assertSame(Status::HALF_DAY, $record->status);
    }
}
