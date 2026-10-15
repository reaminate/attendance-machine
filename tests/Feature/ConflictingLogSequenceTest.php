<?php

namespace Tests\Feature;

use App\Enum\RecordAction;
use App\Enum\Remarks;
use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConflictingLogSequenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A second IN beyond the duplicate tolerance needs review, keeps observed timestamps and leaves every minute metric null.
     */
    public function test_second_checkin_beyond_tolerance_needs_review_with_null_metrics(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logEvents($employee, [
            [RecordAction::IN, '2026-10-01 09:00:00'],
            [RecordAction::IN, '2026-10-01 11:00:00'],
            [RecordAction::OUT, '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 09:00', $record->first_in->format('Y-m-d H:i'));
        $this->assertSame('2026-10-01 17:00', $record->last_out->format('Y-m-d H:i'));
        $this->assertNull($record->worked_minutes);
        $this->assertNull($record->late_minutes);
        $this->assertNull($record->early_departure_minutes);
        $this->assertNull($record->overtime_minutes);
        $this->assertSame(Status::NEEDS_REVIEW, $record->status);
        $this->assertSame([Remarks::CONFLICTING_CHECKIN_SEQUENCE->value], $record->remarks);
    }

    /**
     * A second IN three minutes later is beyond the two-minute tolerance and is a conflict, not a duplicate.
     */
    public function test_second_checkin_three_minutes_later_is_a_conflict_not_a_duplicate(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logEvents($employee, [
            [RecordAction::IN, '2026-10-01 09:00:00'],
            [RecordAction::IN, '2026-10-01 09:03:00'],
            [RecordAction::OUT, '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame(Status::NEEDS_REVIEW, $record->status);
        $this->assertSame([Remarks::CONFLICTING_CHECKIN_SEQUENCE->value], $record->remarks);
    }
}
