<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LateArrivalAndEarlyDepartureTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A test to test the late arrival with early departure test but still completes min work min
     */
    public function test_to_see_if_earl_departure_and_late_arrival_gives_half_day_if_min_workminutes_reach(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        AttendanceRecord::where('worked_minutes', '<', 400)
        ->where('worked_minutes','>', 240)->each(function($query){
            $this->assertSame(Status::HALF_DAY, $query->status);
        });
    }
    /**
     * A test to test the late arrival with early departure test but still completes min work min
     */
    public function test_to_see_if_earl_departure_and_late_arrival_gives_insufficient_if_min_workminutes_not_reach(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        AttendanceRecord::where('worked_minutes', '<', 240 )->whereNotNull('worked_minutes')
        ->where('worked_minutes', '>', 0)
        ->each(function($query){
            $this->assertSame(Status::INSUFFICIENT_HOURS, $query->status);
        });
    }

}
