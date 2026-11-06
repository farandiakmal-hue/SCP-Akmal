<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('schedules', function (Blueprint $table) {
        $table->id();
        $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
        $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
        $table->string('subject');
        
        $table->enum('day_of_week', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']);
        $table->time('start_time');
        $table->time('end_time');
        
        $table->string('cycle_type')->default('Putaran 1'); 

        $table->string('academic_year')->default('2025/2026');
        $table->enum('semester', ['1', '2'])->default('1');

        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
