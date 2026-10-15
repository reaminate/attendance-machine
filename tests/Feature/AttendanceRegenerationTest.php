<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRegenerationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Running the command twice updates the existing record instead of creating a duplicate.
     */
    public function test_rerunning_generation_does_not_create_duplicate_records(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 17:00:00'],
        ]);
        $this->artisan('attendance:generate', ['from' => '2026-10-01', 'to' => '2026-10-01'])->assertSuccessful();
        $firstRecordId = AttendanceRecord::sole()->id;

        $this->artisan('attendance:generate', ['from' => '2026-10-01', 'to' => '2026-10-01'])->assertSuccessful();

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertSame($firstRecordId, AttendanceRecord::sole()->id);
    }

    /**
     * Changing a log and regenerating updates the existing record with the new values.
     */
    public function test_regeneration_reflects_updated_logs(): void
    {
        $employee = $this->createAssignedEmployee($this->createDayShift());
        $this->logSessions($employee, [
            ['2026-10-01 09:00:00', '2026-10-01 17:00:00'],
        ]);
        $this->artisan('attendance:generate', ['from' => '2026-10-01', 'to' => '2026-10-01'])->assertSuccessful();
        Attendance::where('logged_at', '2026-10-01 17:00:00')->update(['logged_at' => '2026-10-01 16:30:00']);

        $this->artisan('attendance:generate', ['from' => '2026-10-01', 'to' => '2026-10-01'])->assertSuccessful();

        $record = AttendanceRecord::sole();
        $this->assertSame('2026-10-01 16:30', $record->last_out->format('Y-m-d H:i'));
        $this->assertSame(390, $record->worked_minutes);
        $this->assertSame(30, $record->early_departure_minutes);
        $this->assertSame(Status::HALF_DAY, $record->status);
    }

    /**
     * The employee filter only generates records for the given employees.
     */
    public function test_employee_filter_only_generates_records_for_given_employees(): void
    {
        $shift = $this->createDayShift();
        $includedEmployee = $this->createAssignedEmployee($shift, '2026-10-01', 'EMP001');
        $this->createAssignedEmployee($shift, '2026-10-01', 'EMP002');

        $this->artisan('attendance:generate', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
            '--employee' => [$includedEmployee->id],
        ])->assertSuccessful();

        $this->assertSame($includedEmployee->id, AttendanceRecord::sole()->employee_id);
    }

    /**
     * Only assignments inside the inclusive date range are processed.
     */
    public function test_generation_only_processes_assignments_inside_the_date_range(): void
    {
        $shift = $this->createDayShift();
        $this->createAssignedEmployee($shift, '2026-10-01', 'EMP001');
        $this->createAssignedEmployee($shift, '2026-10-02', 'EMP002');
        $this->createAssignedEmployee($shift, '2026-10-03', 'EMP003');

        $this->artisan('attendance:generate', ['from' => '2026-10-01', 'to' => '2026-10-02'])->assertSuccessful();

        $this->assertSame(
            ['2026-10-01', '2026-10-02'],
            AttendanceRecord::orderBy('shift_date')->get()->map(fn (AttendanceRecord $record) => $record->shift_date->format('Y-m-d'))->all(),
        );
    }
}
