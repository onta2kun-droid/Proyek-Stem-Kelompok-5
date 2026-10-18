<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Setting;

class RadarController extends Controller
{
    public function index()
    {
        $device = Device::orderBy('id')->firstOrFail();
        $latest = $device->readings()->orderByDesc('created_at')->first();

        // variabel sederhana utk blade/JS (hindari ekspresi di dalam @json)
        $jarak   = ($latest && $latest->jarak !== null && $latest->jarak < 999)
            ? (float) $latest->jarak : null;
        $gerakan = (bool) ($latest?->gerakan ?? false);
        $alarm   = (bool) ($latest?->alarm ?? false);
        $armed   = (int) Setting::get('alarm_enabled', '1') === 1;

        return view('radar', [
            'device'  => $device,
            'latest'  => $latest,
            'jarak'   => $jarak,
            'gerakan' => $gerakan,
            'alarm'   => $alarm,
            'armed'   => $armed,
        ]);
    }

    public function setAlarm(string $state)
    {
        Setting::set('alarm_enabled', $state === 'on' ? '1' : '0');

        return back()->with('success', $state === 'on'
            ? 'Sistem alarm DI-AKTIFKAN — proteksi aktif.'
            : 'Sistem alarm DIMATIKAN — sirene tidak akan menyala.');
    }
}
