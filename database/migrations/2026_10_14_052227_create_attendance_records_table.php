<?php

use App\Enum\Status;
use App\RecordAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->date('shift_date');
            $table->unique(['employee_id', 'shift_id', 'shift_date']);
            $table->time('first_in')->nullable();
            $table->time('last_out')->nullable();
            $table->integer('worked_minutes')->unsigned();
            $table->integer('late_minutes')->unsigned();
            $table->integer('early_departure_minutes')->unsigned();
            $table->integer('overtime_minutes')->unsigned();
            $table->enum('status', Status::cases())->default(Status::PRESENT);
            $table->json('remarks');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
