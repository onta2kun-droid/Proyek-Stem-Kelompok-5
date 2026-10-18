<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceCommand;

class PumpController extends Controller
{
    public function index()
    {
        $device = Device::orderBy('id')->firstOrFail();
        $cmd    = DeviceCommand::currentFor($device);
        $latest = $device->readings()->orderByDesc('created_at')->first();

        return view('pompa', [
            'device' => $device,
            'cmd'    => $cmd,
            'latest' => $latest,
        ]);
    }

    public function setMode(string $mode)
    {
        $device = Device::orderBy('id')->firstOrFail();
        $cmd    = DeviceCommand::currentFor($device);

        $cmd->update(['mode' => $mode, 'issued_at' => now()]);

        return back()->with('success', 'Perintah pompa: ' . strtoupper($mode) . ' dikirim — alat mengecek server tiap ±2 detik.');
    }
}
