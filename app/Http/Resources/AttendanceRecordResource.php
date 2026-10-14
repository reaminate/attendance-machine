<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->__get('id'),
            'employee_id' => $this->__get('employee_id'),
            'shift_id' => $this->__get('shift_id'),
            'shift_date' => $this->__get('shift_date')?->format('Y:m:d'),
            'first_in' => $this->__get('first_in')?->format('H:i'),
            'last_out' => $this->__get('last_out')?->format('Y:m:d H:i'),
            'worked_minutes' => $this->__get('worked_minutes'),
            'late_minutes' => $this->__get('late_minutes'),
            'early_departure_minutes' => $this->__get('early_departure_minutes'),
            'overtime_minutes' => $this->__get('overtime_minutes'),
            'status' => $this->__get('status'),
            'remarks' => $this->__get('remarks'),
            'created_at' => $this->__get('created_at'),
            'updated_at' => $this->__get('updated_at'),
        ];
    }
}
