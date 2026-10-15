<?php

namespace App\Console\Commands;

use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('attendance:list {--from= : Start date (Y-m-d)} {--to= : End date (Y-m-d)} {--employee=* : Limit to these employee IDs}')]
#[Description('Prints the generated attendance records, optionally filtered by date range and employee')]
class ListAttendance extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $employeeIds = array_map('intval', $this->option('employee'));

        $validator = Validator::make([
            'from' => $from,
            'to' => $to,
            'employee' => $this->option('employee'),
        ], [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => array_filter(['nullable', 'date_format:Y-m-d', $from ? 'after_or_equal:from' : null]),
            'employee' => ['array'],
            'employee.*' => ['integer', 'exists:employees,id'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::INVALID;
        }

        $records = AttendanceRecord::query()
            ->when($from, fn ($query) => $query->whereDate('shift_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('shift_date', '<=', $to))
            ->when($employeeIds, fn ($query) => $query->whereIn('employee_id', $employeeIds))
            ->orderedByFirstLog()
            ->get();

        $this->line(json_encode(AttendanceRecordResource::collection($records)->resolve(), JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
