<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Services\ShiftWindow;
use App\Services\UpdatingFirstInLastOut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PostShiftOvertimeTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A test to see if ot counts only after 30 minutes of normal shift end time
     */
    public function test_if_staying_longer_than_30_minutes_gives_ot(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $records = AttendanceRecord::with('shift')
            ->where('overtime_minutes', '>', 0)
            ->get();

        $this->assertNotEmpty($records);

        $records->each(function (AttendanceRecord $record) {
            $shiftEnd = ShiftWindow::for($record->shift, $record->shift_date)->end;
            $expectedOvertime = (int) $shiftEnd->diffInMinutes($record->last_out);

            $this->assertGreaterThanOrEqual(UpdatingFirstInLastOut::MINIMUM_OVERTIME_MINUTES, $record->overtime_minutes);
            $this->assertSame($expectedOvertime, $record->overtime_minutes);
        });
    }
}
