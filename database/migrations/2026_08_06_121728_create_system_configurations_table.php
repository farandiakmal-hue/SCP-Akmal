<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('active_cycle')->default('Putaran 1');
            $table->string('current_academic_year')->default('2025/2026');
            $table->enum('current_semester', ['1', '2'])->default('1');
            $table->boolean('is_schedule_locked')->default(false); // TRUE = Waka kunci, FALSE = Waka buka
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configurations');
    }
};