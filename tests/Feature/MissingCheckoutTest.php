<?php

namespace Tests\Feature;

use App\Enum\RecordAction;
use App\Enum\Remarks;
use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A single IN is incomplete, keeps the observed IN, invents no OUT and leaves every minute metric null.
     */
    public function test_single_checkin_is_incomplete_with_null_metrics(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logEvents($employee, [
            [RecordAction::IN, '2026-10-01 09:00:00'],
        ]);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('2026-10-01 09:00', $record->first_in->format('Y-m-d H:i'));
        $this->assertNull($record->last_out);
        $this->assertNull($record->worked_minutes);
        $this->assertNull($record->late_minutes);
        $this->assertNull($record->early_departure_minutes);
        $this->assertNull($record->overtime_minutes);
        $this->assertSame(Status::INCOMPLETE, $record->status);
        $this->assertSame([Remarks::MISSING_CHECKOUT->value], $record->remarks);
    }
}
