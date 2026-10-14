<?php

namespace App\Models;

use App\Casts\DateCast;
use App\Enum\Status;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['employee_id', 'shift_id', 'shift_date', 'first_in', 'last_out', 'worked_minutes', 'late_minutes', 'early_departure_minutes', 'overtime_minutes', 'status', 'remarks'])]
class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory;

    protected $casts =
        [
            'shift_date' => DateCast::class,
            'first_in' => 'datetime',
            'last_out' => 'datetime',
            'status' => Status::class,
            'remarks' => 'json',
        ];

    /**
     * returns the employee model
     *
     * @return BelongsTo<Employee, AttendanceRecord>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Summary of shift
     *
     * @return BelongsTo<Shift, AttendanceRecord>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * Summary of attendances
     *
     * @return HasMany<Attendance, AttendanceRecord>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'employee_id');
    }
}
