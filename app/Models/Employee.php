<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
#[Fillable(['name', 'employee_code'])]
class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;
    /**
     * returns the shift assignments of this user
     * @return HasMany<EmployeeShiftAssignment, Employee>
     */
    public function employeeShiftAssignments(): HasMany 
    {
        return $this->hasMany(EmployeeShiftAssignment::class, 'employee_id');
    }

    /**
     * returns the attendances of this user
     * @return HasMany<Attendance, Employee>
     */
    public function attendances(): HasMany 
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }
    /**
     * Summary of attendanceRecord
     * @return HasMany<AttendanceRecord, Employee>
     */
    public function attendanceRecord(): HasMany 
    {
        return $this->hasMany(AttendanceRecord::class, 'employee_id');
    }
    
}
