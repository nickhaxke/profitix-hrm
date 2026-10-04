<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key', 30)->unique();
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('bound_domain')->nullable();
            $table->string('bound_ip')->nullable();
            $table->string('machine_id')->nullable();
            $table->integer('max_employees')->default(50);
            $table->enum('plan', ['starter', 'business', 'enterprise'])->default('business');
            $table->enum('status', ['active', 'expired', 'suspended', 'pending'])->default('pending');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_heartbeat')->nullable();
            $table->text('activation_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
