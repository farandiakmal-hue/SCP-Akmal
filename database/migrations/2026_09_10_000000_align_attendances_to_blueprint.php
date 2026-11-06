<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'date')) {
                $table->date('date')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('attendances', 'source')) {
                $table->string('source')->default('manual')->after('status'); 
            }
        });

        \Illuminate\Support\Facades\DB::statement(
            'UPDATE attendances SET date = (SELECT date FROM journals WHERE journals.id = attendances.journal_id) WHERE date IS NULL'
        );

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'proof_file')) {
                $table->dropColumn('proof_file');
            }
            if (Schema::hasColumn('attendances', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('attendances', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'date')) {
                $table->dropColumn('date');
            }
            if (Schema::hasColumn('attendances', 'source')) {
                $table->dropColumn('source');
            }
            $table->string('proof_file')->nullable();
            $table->text('notes')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
        });
    }
};
