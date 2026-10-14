<?php

namespace Database\Seeders;

use App\Enum\RecordAction;
use App\Models\Attendance;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        $ordered_rows = $rows->collect()->sortBy('id');
        $ordered_rows->each(function(array $ordered_rows){
            Attendance::fillAndInsert([
                'employee_id' => $ordered_rows['employee_id'],
                'action' => ($ordered_rows['action'] === 'IN')? RecordAction::IN : RecordAction::OUT,
                'logged_at' => $ordered_rows['logged_at'],
            ]);
        });
    }
}
