<?php

namespace Database\Seeders;

use App\Models\Shift;
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
        $shifts = $rows->collect()
            ->sortBy('id')
            ->map(fn (array $row): array => [
                'id' => $row['id'],
                'name' => $row['name'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'grace_minutes' => $row['grace_minutes'],
                'break_minutes' => $row['break_minutes'],
                'minimum_half_day_minutes' => $row['minimum_half_day_minutes'],
                'minimum_full_day_minutes' => $row['minimum_full_day_minutes'],
            ])
            ->values()
            ->all();
        Shift::whereNotIn('id', ['id'])->delete();
        Shift::upsert(
            $shifts,
            uniqueBy: ['id'],
            update: ['name', 'start_time', 'end_time', 'grace_minutes', 'break_minutes', 'minimum_half_day_minutes', 'minimum_full_day_minutes'],
        );
    }
}
