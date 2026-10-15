<?php

namespace App\Console\Commands;

use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Services\AttendanceGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('attendance:generate {from : Start date (Y-m-d)} {to : End date (Y-m-d)} {--employee=* : Limit to these employee IDs}')]
#[Description('Generates the attendance records from the specified range')]
class GenerateAttendance extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AttendanceGenerator $generator): int
    {
        $validator = Validator::make([
            'from' => $this->argument('from'),
            'to' => $this->argument('to'),
            'employee' => $this->option('employee'),
        ], [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'employee' => ['array'],
            'employee.*' => ['integer', 'exists:employees,id'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::INVALID;
        }

        $result = $generator->generate(
            CarbonImmutable::createFromFormat('!Y-m-d', $this->argument('from')),
            CarbonImmutable::createFromFormat('!Y-m-d', $this->argument('to')),
            array_map('intval', $this->option('employee')),
        );

        $records = AttendanceRecord::query()
            ->whereKey($result['records']->pluck('id'))
            ->orderedByFirstLog()
            ->get();

        $this->line(json_encode([
            'summary' => $result['summary'],
            'records' => AttendanceRecordResource::collection($records)->resolve(),
        ], JSON_PRETTY_PRINT));

        return $result['summary']['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
