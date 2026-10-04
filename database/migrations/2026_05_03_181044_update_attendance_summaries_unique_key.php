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
        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'summary_date']);
            $table->unique(['employee_id', 'summary_date', 'shift_id']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'summary_date', 'shift_id']);
            $table->unique(['employee_id', 'summary_date']);
        });
    }
};
