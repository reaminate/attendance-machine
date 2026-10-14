<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\TimeCast;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'start_time', 'end_time', 'grace_minutes', 'break_minutes', 'minimum_half_day_minutes', 'minimum_full_day_minutes'])]
class Shift extends Model
{
    /** @use HasFactory<\Database\Factories\ShiftFactory> */
    use HasFactory;
    protected $casts = 
    [
        'start_time' => TimeCast::class,
        'end_time' => TimeCast::class,
    ];
    /**
     * returns all the related shifts
     * @return HasMany<EmployeeShiftAssignment, Shift>
     */
    public function employeeShiftAssignments():HasMany 
    {
        return $this->hasMany(EmployeeShiftAssignment::class, 'shift_id');
    }
    /**
     * Summary of attendaceRecord
     * @return HasMany<AttendanceRecord, Shift>
     */
    public function attendaceRecord(): HasMany 
    {
        return $this->hasMany(AttendanceRecord::class, 'shift_id');
    }
    
}
