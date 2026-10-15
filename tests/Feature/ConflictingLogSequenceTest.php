<?php

namespace Tests\Feature;

use App\Enum\Remarks;
use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ConflictingLogSequenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * testing if conflicing log sequences gives the needs review
     * status with the correct remark
     */
    public function test_if_two_checkin_outside_given_period_gives_correct_remark(): void 
    {
        $this->seed();

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        AttendanceRecord::whereJsonContains('remarks', Remarks::CONFLICTING_CHECKIN_SEQUENCE->value)
        ->each(function($query){
            $this->assertSame(Status::NEEDS_REVIEW, $query->status);
            $this->assertNull($query->wored_hours);
            $this->assertNotNull($query->first_in);
            $this->assertNotNull($query->last_out);
        });
    }
    
}
