<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OvernightShiftTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A test to check if overnight shift gives correct last out time
     */
    public function test_if_overnight_shift_gives_correct_stats(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        AttendanceRecord::where('shift_id', 2)
        ->each(function($query){
            $start_date = $query->first_in->format('Y-m-d');
            $end_date = $query->last_out->format('Y-m-d');
            $end_time = $query->last_out->format('H-i');
            $this->assertSame($query->shift_date->format('Y-m-d'), $start_date);
            $this->assertSame($query->shift_date->addDay()->format('Y-m-d'), $end_date);
            $this->assertSame('06-00', $end_time);
            $this->assertSame(420, $query->worked_minutes);
        });
    }
}
