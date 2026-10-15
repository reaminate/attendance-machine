<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ArrivalWithinGraceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * testing if arriving within the grace period gives 0 late minutes, but still counts
     * towards worked hours
     */
    public function test_arrival_within_grace_0_late_minutes(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        AttendanceRecord::where('worked_minutes', 410)
        ->each(function($query){
            $this->assertSame(Status::HALF_DAY, $query->status);
            $this->assertSame(0, $query->late_minutes);
        });

        


    }
}
