<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'employee'])->default('employee')->after('email');
            $table->string('employee_id')->nullable()->unique()->after('role');
            $table->string('position')->nullable()->after('employee_id');
            $table->string('phone')->nullable()->after('position');
            $table->string('photo')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('photo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'employee_id', 'position', 'phone', 'photo', 'is_active']);
        });
    }
};
