<?php

namespace App\Models;

use App\Enum\RecordAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['employee_id', 'action', 'logged_at'])]
class Attendance extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceFactory> */
    use HasFactory;
    protected $casts = 
    [
        'action' => RecordAction::class,
        'logged_at' => 'datetime:Y-m-d H-i',
    ];
    /**
     * returns the employee this belongs to
     * @return BelongsTo<Employee, EmployeeShiftAssignment>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
