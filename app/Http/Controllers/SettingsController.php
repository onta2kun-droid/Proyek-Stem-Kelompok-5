<?php

namespace App\Http\Controllers;

use App\Models\SensorReading;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('pengaturan', [
            'dry'      => SensorReading::dryThreshold(),
            'wet'      => SensorReading::wetThreshold(),
            'interval' => (int) Setting::get('send_interval', 1),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'dry'      => ['required', 'integer', 'between:0,100'],
            'wet'      => ['required', 'integer', 'between:0,100'],
            'interval' => ['required', 'integer', 'between:1,3600'],
        ]);

        if ($data['dry'] >= $data['wet']) {
            return back()->withInput()
                ->withErrors(['dry' => 'Batas kering harus lebih kecil dari batas optimal atas.']);
        }

        Setting::set('threshold_dry', $data['dry']);
        Setting::set('threshold_wet', $data['wet']);
        Setting::set('send_interval', $data['interval']);

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
