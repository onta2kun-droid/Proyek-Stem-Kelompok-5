<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->enum('mode', ['auto', 'on', 'off'])->default('auto');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('commands'); }
};
