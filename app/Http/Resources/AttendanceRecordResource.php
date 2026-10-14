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
            'first_in' => $this->__get('frist_in')??null,
            'last_out' => $this->__get('last_out')??null,
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
