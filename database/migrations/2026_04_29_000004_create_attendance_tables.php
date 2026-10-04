<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('crosses_midnight')->default(false);
            $table->integer('grace_minutes')->default(15);
            $table->integer('late_threshold_minutes')->default(30);
            $table->decimal('required_hours', 4, 2)->default(8.00);
            $table->decimal('half_day_hours', 4, 2)->default(4.00);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
        });

        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('punch_time');
            $table->enum('punch_type', ['in', 'out', 'break_in', 'break_out', 'unknown'])->default('unknown');
            $table->enum('source', ['device', 'manual', 'import'])->default('device');
            $table->string('raw_data')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'punch_time']);
            $table->index(['punch_time']);
            $table->index(['is_processed']);
        });

        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('summary_date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->decimal('total_hours', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_out_minutes')->default(0);
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'leave', 'holiday', 'off_day', 'missing_checkout', 'early_out'])->default('absent');
            $table->boolean('is_late')->default(false);
            $table->boolean('is_overnight')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'summary_date']);
            $table->index(['summary_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('employee_shifts');
        Schema::dropIfExists('shifts');
    }
};
