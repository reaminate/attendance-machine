<?php

namespace App\Services;

use App\Enum\Status;
use App\Models\AttendanceRecord;
use App\Models\EmployeeShiftAssignment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class AttendanceGenerator
{
    public function __construct(private UpdatingFirstInLastOut $calculator) {}

    /**
     * @param  array<int, int>  $employeeIds
     * @return array{summary: array{processed: int, created: int, updated: int, insufficient_hours: int, absent: int, incomplete: int, needs_review: int, failed: int, errors: array<int, array{assignment_id: int, error: string}>}, records: Collection<int, AttendanceRecord>}
     */
    public function generate(CarbonInterface $from, CarbonInterface $to, array $employeeIds = []): array
    {
        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'insufficient_hours' => 0,
            'absent' => 0,
            'incomplete' => 0,
            'needs_review' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        $records = collect();

        EmployeeShiftAssignment::query()
            ->with('shift')
            ->whereDate('shift_date', '>=', $from->toDateString())
            ->whereDate('shift_date', '<=', $to->toDateString())
            ->when($employeeIds, fn ($query) => $query->whereIn('employee_id', $employeeIds))
            ->lazyById()
            ->each(function (EmployeeShiftAssignment $assignment) use (&$summary, $records) {
                $summary['processed']++;

                try {
                    $record = DB::transaction(fn () => AttendanceRecord::updateOrCreate(
                        [
                            'employee_id' => $assignment->employee_id,
                            'shift_id' => $assignment->shift_id,
                            'shift_date' => $assignment->shift_date->toDateString(),
                        ],
                        $this->calculator->evaluate($assignment),
                    ));
                } catch (Throwable $e) {
                    report($e);
                    $summary['failed']++;
                    $summary['errors'][] = ['assignment_id' => $assignment->id, 'error' => $e->getMessage()];

                    return;
                }

                $records->push($record);
                $summary[$record->wasRecentlyCreated ? 'created' : 'updated']++;

                match ($record->status) {
                    Status::INSUFFICIENT_HOURS => $summary['insufficient_hours']++,
                    Status::ABSENT => $summary['absent']++,
                    Status::INCOMPLETE => $summary['incomplete']++,
                    Status::NEEDS_REVIEW => $summary['needs_review']++,
                    default => null,
                };
            });

        return ['summary' => $summary, 'records' => $records];
    }
}
