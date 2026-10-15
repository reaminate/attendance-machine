<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EarlyArrivalIsNotOvertimeTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Asserting that early arrival does not mean ot
     */
    public function test_if_early_arrival_doest_give_ot(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        AttendanceRecord::where('first_in', '<', '2026-10-01 09:00:00')
        ->each(function($query){
            $this->assertSame(Status::PRESENT, $query->status);
            $this->assertGreaterThan(420, $query->worked_minutes);
            $this->assertSame(0, $query->overtime_minutes);
        });

        
    }
    
    
}
