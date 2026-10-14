<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\SimpleExcel\SimpleExcelReader;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = 'shifts.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $ordered_rows = $rows->collect()->sortBy('id');
        $ordered_rows->each(function(array $ordered_rows){
            Shift::fillAndInsert([
                'name' => $ordered_rows['name'],
                'start_time' => $ordered_rows['start_time'],
                'end_time' => $ordered_rows['end_time'],
                'grace_minutes' => $ordered_rows['grace_minutes'],
                'break_minutes' => $ordered_rows['break_minutes'],
                'minimum_half_day_minutes' => $ordered_rows['minimum_half_day_minutes'],
                'minimum_full_day_minutes' => $ordered_rows['minimum_full_day_minutes'],

            ]);
        });
    }
}
