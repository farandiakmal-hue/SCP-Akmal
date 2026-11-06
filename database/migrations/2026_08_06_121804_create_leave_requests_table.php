<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
    
    
    $table->enum('type', ['sakit', 'izin', 'izin_tengah_jam', 'dispensasi']);
    
   
    $table->date('start_date');
    $table->date('end_date');
    
    
    $table->time('exit_time')->nullable(); 
    $table->integer('from_lesson_hour')->nullable(); 
    $table->text('reason');
    $table->string('document_path')->nullable(); 
    
    $table->enum('status', ['pending', 'approved', 'revisi', 'rejected'])->default('pending');
    $table->text('verification_note')->nullable(); 
    $table->foreignId('verified_by')->nullable()->constrained('users'); 
    
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
