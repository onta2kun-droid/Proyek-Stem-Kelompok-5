<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('api_key')->unique();
            $table->string('ip', 45)->nullable();
            $table->smallInteger('rssi')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('devices'); }
};
