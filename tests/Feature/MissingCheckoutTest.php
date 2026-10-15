<?php

namespace Tests\Feature;

use App\Enum\Remarks;
use App\Enum\Status;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MissingCheckoutTest extends TestCase
{
    use RefreshDatabase;
    /**
     * test to see if checkout is missing,  no working hours is given
     */
    public function test_if_no_checkout_no_working_minutes(): void
    {
        $this->seed();
        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        AttendanceRecord::whereNull('last_out')->whereNotNull('first_in')
        ->each(function($query){
            $this->assertNull($query->worked_minutes);
            $this->assertSame(Status::INCOMPLETE, $query->status);
            $this->assertSame([Remarks::MISSING_CHECKOUT->value], $query->remarks);
        });
    }
}
