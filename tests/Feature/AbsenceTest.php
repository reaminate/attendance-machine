<?php

namespace Tests\Feature;

use App\Enum\Remarks;
use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An employee assigned to a shift with no attendance logs is marked absent.
     */
    public function test_employee_with_no_logs_is_marked_absent(): void
    {
        $this->seed();

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();
        AttendanceRecord::where('status', Status::ABSENT)
        ->each(function($query){
            $this->assertSame([Remarks::NO_ATTENDANCE_LOGS->value], $query->remarks);
            $this->assertNull($query->first_in);
            $this->assertNull($query->last_out);
        });
    }
}
