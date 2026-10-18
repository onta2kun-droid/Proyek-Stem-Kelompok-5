<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->decimal('kelembapan', 5, 2);
            $table->decimal('suhu', 5, 2)->nullable();
            $table->smallInteger('rssi')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['device_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('sensor_readings'); }
};
