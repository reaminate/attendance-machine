<?php

namespace Database\Seeders;

use App\Models\EmployeeShiftAssignment;
use Illuminate\Database\Seeder;
use Spatie\SimpleExcel\SimpleExcelReader;

class EmployeeShiftAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = 'employee_shift_assignments.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $assignments = $rows->collect()
            ->sortBy('id')
            ->map(fn (array $row): array => [
                'id' => $row['id'],
                'employee_id' => $row['employee_id'],
                'shift_id' => $row['shift_id'],
                'shift_date' => $row['shift_date'],
            ])
            ->values()
            ->all();

        EmployeeShiftAssignment::upsert(
            $assignments,
            uniqueBy: ['id'],
            update: ['employee_id', 'shift_id', 'shift_date'],
        );
    }
}
