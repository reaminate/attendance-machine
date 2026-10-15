<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A full 09:00–17:00 session deducts the break once and is present with no late, early or overtime minutes.
     */
    public function test_full_shift_is_present_with_break_deducted_once(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 09:00', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-01 17:00', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(420, $record->worked_minutes);
        $this->assertSame(0, $record->late_minutes);
        $this->assertSame(0, $record->early_departure_minutes);
        $this->assertSame(0, $record->overtime_minutes);
        $this->assertSame(Status::PRESENT, $record->status);
    }
}
