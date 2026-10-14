<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\SimpleExcel\SimpleExcelReader;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = 'employees.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $ordered_rows = $rows->collect()->sortBy('id');
        $ordered_rows->each(function(array $ordered_rows){
            Employee::fillAndInsert([
                'name' => $ordered_rows['name'],
                'employee_code' => $ordered_rows['employee_code'],
            ]);
        });
    }
}
