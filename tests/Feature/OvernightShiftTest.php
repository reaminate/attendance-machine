<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvernightShiftTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A 22:00–06:00 shift pairs the OUT on the next day and stores the record under the shift start date.
     */
    public function test_overnight_shift_pairs_across_midnight_under_the_start_date(): void
    {
        $employee = $this->createAssignedEmployee($this->createNightShift());
        $this->logSessions($employee, [
            ['2026-10-01 22:00:00', '2026-10-02 06:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01', $record->shift_date->format('Y-m-d'));
        $this->assertSame('2026-10-01 22:00', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-02 06:00', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(420, $record->worked_minutes);
        $this->assertSame(0, $record->late_minutes);
        $this->assertSame(0, $record->early_departure_minutes);
        $this->assertSame(0, $record->overtime_minutes);
        $this->assertSame(Status::PRESENT, $record->status);
    }
}
