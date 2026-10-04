<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address', 45);
            $table->integer('port')->default(4370);
            $table->string('serial_number')->nullable();
            $table->string('device_model')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('communication_type', ['TCP/IP', 'USB', 'RS232'])->default('TCP/IP');
            $table->boolean('is_online')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync')->nullable();
            $table->timestamp('last_ping')->nullable();
            $table->timestamps();
        });

        Schema::create('device_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->enum('sync_type', ['attendance', 'user', 'fingerprint', 'full']);
            $table->integer('records_count')->default(0);
            $table->enum('status', ['pending', 'running', 'success', 'failed']);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_sync_logs');
        Schema::dropIfExists('devices');
    }
};
