<?php

namespace Database\Seeders;

use App\Models\EmployeeShiftAssignment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        $ordered_rows = $rows->collect()->sortBy('id');

        $ordered_rows->each(function(array $ordered_rows){
            EmployeeShiftAssignment::fillAndInsert([
                'employee_id' => $ordered_rows['employee_id'],
                'shift_id' => $ordered_rows['shift_id'],
                'shift_date' => $ordered_rows['shift_date'],
            ]);
        });
    }
}
