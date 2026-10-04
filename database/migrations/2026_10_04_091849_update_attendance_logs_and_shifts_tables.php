<?php

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
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('device_log_index')->nullable()->after('device_id');
            $table->string('unmapped_bio_id')->nullable()->after('employee_id');
            $table->boolean('is_ignored')->default(false)->after('punch_type');
            $table->unsignedBigInteger('employee_id')->nullable()->change();
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->time('earliest_checkout_time')->nullable()->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('earliest_checkout_time');
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn(['device_log_index', 'unmapped_bio_id', 'is_ignored']);
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
        });
    }
};
