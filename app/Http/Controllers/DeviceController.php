<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::withCount('readings')->orderBy('id')->get()
            ->each(function (Device $d) {
                $d->latest      = $d->readings()->orderByDesc('created_at')->first();
                $today          = $d->readings()->whereDate('created_at', today());
                $d->today_count = (clone $today)->count();
                $d->today_avg   = (clone $today)->avg('kelembapan');
            });

        return view('perangkat', compact('devices'));
    }

    public function regenerateKey(Device $device)
    {
        $device->update(['api_key' => 'k6-' . Str::random(24)]);

        return back()->with('success',
            "API key {$device->code} berhasil diperbarui. Jangan lupa ubah API key di kode ESP32!");
    }
}
