<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Collection;

class UpdatingFirstInLastOut
{
    public const integer DUPLICATE_TOLERANCE_SECONDS = 120;

    public function removingDupes(Employee $employee)
    {
        $events = Attendance::where('employee_id', $employee->__get('id'))
        ->orderBy('logged_at')->get('action');
        $kept = collect();
        $anchor = null;          
        $duplicatesFound = false;

        foreach ($events as $event) {
            $isDuplicate = $anchor !== null
                && $event->action === $anchor->action
                && $anchor->logged_at->diffInSeconds($event->logged_at) <= self::DUPLICATE_TOLERANCE_SECONDS;

            if ($isDuplicate) {
                $duplicatesFound = true;
                continue;            
            }

            $kept->push($event);
            $anchor = $event;        
        }
        return ['events' => $kept, 'duplicates_ignored' => $duplicatesFound];
    }
}
