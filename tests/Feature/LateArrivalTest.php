<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LateArrivalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A test to see late first_int gives late arrival
     */
    public function test_to_see_if_ariving_late_gives_late_minutes_and_correct_work_tiem(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();
        $records = AttendanceRecord::where('late_minutes', '!=', 0)
        ->where('overtime_minutes', 0)
        ->each(function($query){
            $this->assertLessThan(420, $query->worked_minutes);
            $this->assertSame(Status::HALF_DAY, $query->status);
        });

        
    }
}
