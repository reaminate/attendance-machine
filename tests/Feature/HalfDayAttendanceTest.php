<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HalfDayAttendanceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A test to see, if the worked hours isnt 420, it gives half day
     */
    public function test_example(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        AttendanceRecord::where('worked_minutes', '<', 420)
        ->whereNotNull('worked_hours')
        ->where('worked_minutes', '>', 390 )
        ->each(function($query){
            $this->assertSame(Status::HALF_DAY, $query->status);
        });
    }
}
