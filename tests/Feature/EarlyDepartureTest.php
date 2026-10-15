<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EarlyDepartureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A test to see early last_out gives early departure
     */
    public function test_to_see_if_leaving_early_gives_early_departure_and_correct_work_tiem(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();
        AttendanceRecord::where('early_departure_minutes', '!=', 0)
        ->where('overtime_minutes', 0)->where('worked_minutes', '>', 390)
        ->each(function($query){
            $this->assertLessThan(420, $query->worked_minutes);
            $this->assertSame(Status::HALF_DAY, $query->status);
        });

        
    }
}
