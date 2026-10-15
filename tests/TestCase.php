<?php

namespace Tests;

use App\Enum\RecordAction;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the 09:00–17:00 day shift used by the exam scenarios.
     */
    protected function createDayShift(): Shift
    {
        return Shift::create([
            'name' => 'Day',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'grace_minutes' => 10,
            'break_minutes' => 60,
            'minimum_half_day_minutes' => 240,
            'minimum_full_day_minutes' => 420,
        ]);
    }

    /**
     * Creates the 22:00–06:00 overnight shift used by the exam scenarios.
     */
    protected function createNightShift(): Shift
    {
        return Shift::create([
            'name' => 'Night',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'grace_minutes' => 10,
            'break_minutes' => 60,
            'minimum_half_day_minutes' => 240,
            'minimum_full_day_minutes' => 420,
        ]);
    }

    /**
     * Creates an employee assigned to the given shift on the given date.
     */
    protected function createAssignedEmployee(Shift $shift, string $shiftDate = '2026-10-01', string $employeeCode = 'EMP001'): Employee
    {
        $employee = Employee::create(['name' => 'Employee '.$employeeCode, 'employee_code' => $employeeCode]);

        EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'shift_date' => $shiftDate,
        ]);

        return $employee;
    }

    /**
     * @param  array<int, array{0: RecordAction, 1: string}>  $events
     */
    protected function logEvents(Employee $employee, array $events): void
    {
        foreach ($events as [$action, $loggedAt]) {
            Attendance::create(['employee_id' => $employee->id, 'action' => $action, 'logged_at' => $loggedAt]);
        }
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $sessions
     */
    protected function logSessions(Employee $employee, array $sessions): void
    {
        foreach ($sessions as [$in, $out]) {
            $this->logEvents($employee, [[RecordAction::IN, $in], [RecordAction::OUT, $out]]);
        }
    }
}
