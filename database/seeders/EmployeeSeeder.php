<?php

namespace Database\Seeders;

use App\Models\Employee;
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
        $employees = $rows->collect()
            ->sortBy('id')
            ->map(fn (array $row): array => [
                'id' => $row['id'],
                'name' => $row['name'],
                'employee_code' => $row['employee_code'],
            ])
            ->values()
            ->all();
        Employee::whereNotIn('id', ['id'])->delete();
        Employee::upsert(
            $employees,
            uniqueBy: ['id'],
            update: ['name', 'employee_code'],
        );
    }
}
