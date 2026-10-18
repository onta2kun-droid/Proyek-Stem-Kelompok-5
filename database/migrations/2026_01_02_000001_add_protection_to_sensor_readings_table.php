<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->decimal('jarak', 6, 1)->nullable()->after('suhu');
            $table->boolean('gerakan')->default(false)->after('jarak');
            $table->boolean('pompa')->default(false)->after('gerakan');
            $table->boolean('alarm')->default(false)->after('pompa');
        });
    }
    public function down(): void {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropColumn(['jarak', 'gerakan', 'pompa', 'alarm']);
        });
    }
};
