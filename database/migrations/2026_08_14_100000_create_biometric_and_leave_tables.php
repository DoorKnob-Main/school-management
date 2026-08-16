<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBiometricAndLeaveTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Biometric Devices
        if (!Schema::hasTable('biometric_devices')) {
            Schema::create('biometric_devices', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('device_identifier')->unique();
                $table->string('ip_address');
                $table->integer('port')->default(5005);
                $table->integer('machine_number')->default(1);
                $table->string('communication_password')->default('0');
                $table->string('location')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('status')->default('offline'); // online, offline, error
                $table->timestamp('last_connected_at')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();
            });
        }

        // 2. Biometric Device User Mappings
        if (!Schema::hasTable('biometric_device_user_mappings')) {
            Schema::create('biometric_device_user_mappings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('device_id');
                $table->integer('device_user_id');
                $table->string('enrollment_status')->default('not_enrolled'); // not_enrolled, pending, enrolled, sync_error, removed
                $table->timestamp('enrolled_at')->nullable();
                $table->timestamps();

                $table->unique(['device_id', 'device_user_id'], 'uniq_device_user');
                $table->index('student_id');
            });
        }

        // 3. Biometric Punch Logs
        if (!Schema::hasTable('biometric_punch_logs')) {
            Schema::create('biometric_punch_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('device_id');
                $table->integer('device_user_id');
                $table->unsignedBigInteger('student_id')->nullable();
                $table->dateTime('punch_time');
                $table->string('verify_type')->default('1');
                $table->integer('sensor_no')->default(1);
                $table->json('raw_payload')->nullable();
                $table->boolean('is_processed')->default(false);
                $table->string('sync_batch')->nullable();
                $table->timestamps();

                $table->unique(['device_id', 'device_user_id', 'punch_time', 'verify_type'], 'uniq_punch_log');
                $table->index('student_id');
                $table->index('punch_time');
            });
        }

        // 4. Leave Types
        if (!Schema::hasTable('leave_types')) {
            Schema::create('leave_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 5. Student Leaves
        if (!Schema::hasTable('student_leaves')) {
            Schema::create('student_leaves', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('leave_type_id');
                $table->date('start_date');
                $table->date('end_date');
                $table->text('reason')->nullable();
                $table->string('status')->default('pending'); // pending, approved, rejected
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('rejection_reason')->nullable();
                $table->timestamps();

                $table->index('student_id');
                $table->index(['start_date', 'end_date']);
            });
        }

        // 6. Biometric Audit Logs
        if (!Schema::hasTable('biometric_audit_logs')) {
            Schema::create('biometric_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->json('details')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }

        // 7. Extend Attendances Table safely
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'in_time')) {
                $table->dateTime('in_time')->nullable()->after('status');
            }
            if (!Schema::hasColumn('attendances', 'out_time')) {
                $table->dateTime('out_time')->nullable()->after('in_time');
            }
            if (!Schema::hasColumn('attendances', 'attendance_source')) {
                $table->string('attendance_source')->default('manual')->after('out_time');
            }
            if (!Schema::hasColumn('attendances', 'late_minutes')) {
                $table->integer('late_minutes')->default(0)->after('attendance_source');
            }
            if (!Schema::hasColumn('attendances', 'early_leave_minutes')) {
                $table->integer('early_leave_minutes')->default(0)->after('late_minutes');
            }
            if (!Schema::hasColumn('attendances', 'is_corrected')) {
                $table->boolean('is_corrected')->default(false)->after('early_leave_minutes');
            }
            if (!Schema::hasColumn('attendances', 'corrected_by')) {
                $table->unsignedBigInteger('corrected_by')->nullable()->after('is_corrected');
            }
            if (!Schema::hasColumn('attendances', 'correction_reason')) {
                $table->string('correction_reason')->nullable()->after('corrected_by');
            }
            if (!Schema::hasColumn('attendances', 'remarks')) {
                $table->string('remarks')->nullable()->after('correction_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'in_time',
                'out_time',
                'attendance_source',
                'late_minutes',
                'early_leave_minutes',
                'is_corrected',
                'corrected_by',
                'correction_reason',
                'remarks'
            ]);
        });

        Schema::dropIfExists('biometric_audit_logs');
        Schema::dropIfExists('student_leaves');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('biometric_punch_logs');
        Schema::dropIfExists('biometric_device_user_mappings');
        Schema::dropIfExists('biometric_devices');
    }
}
