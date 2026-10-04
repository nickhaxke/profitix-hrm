<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Altering ENUM natively in MySQL
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'hr', 'manager') DEFAULT 'admin'");

        Schema::table('licenses', function (Blueprint $table) {
            $table->string('client_phone')->nullable()->after('client_email');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('client_phone');
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'hr', 'manager') DEFAULT 'admin'");
    }
};
