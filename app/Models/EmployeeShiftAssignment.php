<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['employee_id', 'shift_id', 'shift_date'])]
class EmployeeShiftAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeShiftAssignmentFactory> */
    use HasFactory;
    protected $casts = 
    [
        'shift_date' => 'datetime:Y-m-d',
    ];
    /**
     * returns the employee this belongs to
     * @return BelongsTo<Employee, EmployeeShiftAssignment>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    /**
     * returns the shift this belongs to
     * @return BelongsTo<Shift, EmployeeShiftAssignment>
     */
    public function shift(): BelongsTo 
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
    

}
