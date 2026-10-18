<?php

namespace Database\Seeders;

use App\Enum\RecordAction;
use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Spatie\SimpleExcel\SimpleExcelReader;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = 'attendance_logs.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $attendances = $rows->collect()
            ->sortBy('id')
            ->map(fn (array $row): array => [
                'id' => $row['id'],
                'employee_id' => $row['employee_id'],
                'action' => ($row['action'] === 'IN') ? RecordAction::IN->value : RecordAction::OUT->value,
                'logged_at' => $row['logged_at'],
            ])
            ->values()
            ->all();
        Attendance::whereNotIn('id', ['id'])->delete();
        Attendance::upsert(
            $attendances,
            ['id'],
            ['employee_id', 'action', 'logged_at'],
        );
    }
}
