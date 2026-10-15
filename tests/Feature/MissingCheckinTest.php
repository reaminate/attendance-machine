<?php

namespace Tests\Feature;

use App\Enum\RecordAction;
use App\Enum\Remarks;
use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingCheckinTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A single OUT is incomplete, keeps the observed OUT, invents no IN and leaves every minute metric null.
     */
    public function test_single_checkout_is_incomplete_with_null_metrics(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logEvents($employee, [
            [RecordAction::OUT, '2026-10-01 17:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertNull($record->first_in);
        $this->assertSame('2026-10-01 17:00', $record->last_out->format('Y-m-d H:i'));
        $this->assertNull($record->worked_minutes);
        $this->assertNull($record->late_minutes);
        $this->assertNull($record->early_departure_minutes);
        $this->assertNull($record->overtime_minutes);
        $this->assertSame(Status::INCOMPLETE, $record->status);
        $this->assertSame([Remarks::MISSING_CHECKIN->value], $record->remarks);
    }
}
