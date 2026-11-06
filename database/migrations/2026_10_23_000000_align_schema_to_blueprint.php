<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyelaraskan skema dengan Blueprint soal UKK:
 *  - users.parent_id            (relasi Siswa -> Orang Tua)
 *  - system_configurations.updated_by
 *  - leave_requests.applicant_id (pengaju: siswa atau orang tua)
 *  - leave_requests.status      -> status_approval
 *  - attendances.status         + nilai 'hadir' (agar rekap kehadiran siswa lengkap)
 */
return new class extends Migration
{
    private function isMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    public function up(): void
    {
        if (!Schema::hasColumn('users', 'parent_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->after('role_id')
                    ->constrained('users')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('system_configurations', 'updated_by')) {
            Schema::table('system_configurations', function (Blueprint $table) {
                $table->foreignId('updated_by')->nullable()
                    ->constrained('users')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('leave_requests', 'applicant_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->foreignId('applicant_id')->nullable()->after('student_id')
                    ->constrained('users')->nullOnDelete();
            });
            DB::table('leave_requests')->whereNull('applicant_id')
                ->update(['applicant_id' => DB::raw('student_id')]);
        }

        if (Schema::hasColumn('leave_requests', 'status') && !Schema::hasColumn('leave_requests', 'status_approval')) {
            if ($this->isMysql()) {
                DB::statement("ALTER TABLE leave_requests CHANGE `status` `status_approval` ENUM('pending','approved','revisi','rejected') NOT NULL DEFAULT 'pending'");
            } else {
                Schema::table('leave_requests', function (Blueprint $table) {
                    $table->renameColumn('status', 'status_approval');
                });
            }
        }

        if ($this->isMysql()) {
            DB::statement("ALTER TABLE attendances MODIFY `status` ENUM('hadir','sakit','izin','izin_tengah_jam','dispensasi','alpha') NOT NULL");
        }
    }

    public function down(): void
    {
        if ($this->isMysql()) {
            DB::table('attendances')->where('status', 'hadir')->delete();
            DB::statement("ALTER TABLE attendances MODIFY `status` ENUM('sakit','izin','izin_tengah_jam','dispensasi','alpha') NOT NULL");
        }

        if (Schema::hasColumn('leave_requests', 'status_approval')) {
            if ($this->isMysql()) {
                DB::statement("ALTER TABLE leave_requests CHANGE `status_approval` `status` ENUM('pending','approved','revisi','rejected') NOT NULL DEFAULT 'pending'");
            } else {
                Schema::table('leave_requests', function (Blueprint $table) {
                    $table->renameColumn('status_approval', 'status');
                });
            }
        }

        if (Schema::hasColumn('leave_requests', 'applicant_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('applicant_id');
            });
        }

        if (Schema::hasColumn('system_configurations', 'updated_by')) {
            Schema::table('system_configurations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('updated_by');
            });
        }

        if (Schema::hasColumn('users', 'parent_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_id');
            });
        }
    }
};
