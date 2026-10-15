<?php

namespace Tests\Feature;

use App\Enum\RecordAction;
use App\Enum\Remarks;
use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateMachineEventsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Same-action events within two minutes are ignored, the earliest is kept and the raw logs are preserved.
     */
    public function test_duplicate_events_within_two_minutes_are_ignored_and_flagged(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logEvents($employee, [
            [RecordAction::IN, '2026-10-01 09:00:00'],
            [RecordAction::IN, '2026-10-01 09:01:00'],
            [RecordAction::OUT, '2026-10-01 17:00:00'],
            [RecordAction::OUT, '2026-10-01 17:01:00'],
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
        $this->assertSame([Remarks::DUPLICATE_LOGS_IGNORED->value], $record->remarks);

        $this->assertDatabaseCount('attendances', 4);
    }
}
