<?php

namespace Tests\Feature;

use App\Enum\Remarks;
use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DuplicateMachineEventsTest extends TestCase
{
    use RefreshDatabase;
    /**
     * testing if duplicate machine events give correct status and remark
     */
    public function test_if_duplicate_within_grace_perioud_gives_correct_status_and_remark(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        AttendanceRecord::whereJsonContains('remarks', Remarks::DUPLICATE_LOGS_IGNORED->value)
        ->each(function($query){
            $this->assertSame(Status::PRESENT, $query->status);
            $this->assertNotNull($query->first_in);
            $this->assertNotNull($query->last_out);
        });
    }
    
}
