<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NormalAttendanceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic attendance test, with required or more amount of working minutes
     */
    public function test_attendance_with_minimum_working_minutes(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        AttendanceRecord::where('worked_minutes', '>=', '420')
        ->each(function($query){
            $this->assertSame(Status::PRESENT, $query->status);
            $this->assertNotNull('first_in');
            $this->assertNotNull('last_out');
        });
    }
}
