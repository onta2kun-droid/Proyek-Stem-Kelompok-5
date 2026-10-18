<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $device = Device::updateOrCreate(
            ['code' => 'DEV-01'],
            [
                'name'         => 'ESP32 Kelembapan Tanah',
                'api_key'      => 'k6-dev01-rahasia-123',
                'ip'           => '192.168.1.24',
                'rssi'         => -62,
                'last_seen_at' => now(),
            ]
        );

        $device->readings()->delete();

        $target = [49, 55, 52, 61, 57, 45, 53];
        $now = now();

        for ($day = 7; $day >= 0; $day--) {
            $base  = $target[7 - $day] ?? 53;
            $start = $now->copy()->subDays($day)->startOfDay();

            for ($slot = 0; $slot < 96; $slot++) {
                $time = $start->copy()->addMinutes($slot * 15);
                if ($time->gt($now)) break;

                $hour = $time->hour + $time->minute / 60;

                $wave      = -cos(($hour - 14) / 24 * 2 * M_PI) * 6;
                $moisture  = max(8, min(95, $base + $wave + random_int(-30, 30) / 10));
                $temp      = 27 + sin(($hour - 13) / 24 * 2 * M_PI) * 4 + random_int(-15, 15) / 10;

                $device->readings()->create([
                    'kelembapan' => round($moisture, 1),
                    'suhu'       => round($temp, 1),
                    'rssi'       => random_int(-68, -55),
                    'ip'         => '192.168.1.24',
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);
            }
        }
    }
}
