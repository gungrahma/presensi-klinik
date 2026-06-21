<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamp('clock_in_at')->nullable();
            $table->string('clock_in_photo_path')->nullable();
            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_lng', 10, 7)->nullable();
            $table->unsignedInteger('clock_in_distance_m')->nullable();
            $table->unsignedInteger('clock_in_accuracy_m')->nullable();
            $table->enum('clock_in_status', ['on_time', 'late'])->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->string('clock_out_photo_path')->nullable();
            $table->decimal('clock_out_lat', 10, 7)->nullable();
            $table->decimal('clock_out_lng', 10, 7)->nullable();
            $table->unsignedInteger('clock_out_distance_m')->nullable();
            $table->unsignedInteger('clock_out_accuracy_m')->nullable();
            $table->enum('clock_out_status', ['on_time', 'early'])->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
