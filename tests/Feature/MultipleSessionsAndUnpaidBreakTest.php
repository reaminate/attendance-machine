<?php

namespace Tests\Feature;

use App\Enum\RecordAction;
use App\Enum\Status;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleSessionsAndUnpaidBreakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * testing if break is more than 1 hour, total work time gets reduced. otherwise its not
     */
    public function test_multiple_sessions_with_one_and_two_hour_breaks(): void
    {
        $shift = Shift::create([
            'name' => 'Day',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'grace_minutes' => 10,
            'break_minutes' => 60,
            'minimum_half_day_minutes' => 240,
            'minimum_full_day_minutes' => 420,
        ]);

        $employee1 = Employee::create(['name' => 'Employee 1', 'employee_code' => 'EMP001']);
        $employee2 = Employee::create(['name' => 'Employee 2', 'employee_code' => 'EMP002']);

        foreach ([$employee1, $employee2] as $employee) {
            EmployeeShiftAssignment::create([
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'shift_date' => '2026-10-01',
            ]);
        }

        $this->logSessions($employee1, [
            ['2026-10-01 09:00:00', '2026-10-01 13:00:00'],
            ['2026-10-01 14:00:00', '2026-10-01 17:00:00'],
        ]);
        $this->logSessions($employee2, [
            ['2026-10-01 09:00:00', '2026-10-01 13:00:00'],
            ['2026-10-01 15:00:00', '2026-10-01 17:00:00'],
        ]);

        $this->assertDatabaseCount('attendances', 8);

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ])->assertSuccessful();

        $oneHourRecord = AttendanceRecord::where('employee_id', $employee1->id)->sole();
        $this->assertSame(420, $oneHourRecord->worked_minutes);
        $this->assertSame(Status::PRESENT, $oneHourRecord->status);
        $twoHourRecord = AttendanceRecord::where('employee_id', $employee2->id)->sole();
        $this->assertSame(360, $twoHourRecord->worked_minutes);
        $this->assertSame(Status::HALF_DAY, $twoHourRecord->status);
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $sessions
     */
    private function logSessions(Employee $employee, array $sessions): void
    {
        foreach ($sessions as [$in, $out]) {
            Attendance::create(['employee_id' => $employee->id, 'action' => RecordAction::IN, 'logged_at' => $in]);
            Attendance::create(['employee_id' => $employee->id, 'action' => RecordAction::OUT, 'logged_at' => $out]);
        }
    }
}
