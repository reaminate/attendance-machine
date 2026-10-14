<?php

namespace App\Listeners;

use App\Enum\RecordAction;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateTheAttendanceRecord
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $attendances = Attendance::get()->groupBy('employee_id');
        
    }
}
