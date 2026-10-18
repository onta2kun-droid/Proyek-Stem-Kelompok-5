<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class SensorApiController extends Controller
{
    // POST /api/v1/readings
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelembapan' => 'required|numeric|between:0,100',
            'suhu'       => 'nullable|numeric|between:-50,120',
            'rssi'       => 'nullable|integer',
            'ip'         => 'nullable|ip',
            'jarak'      => 'nullable|numeric|between:0,1000',
            'gerakan'    => 'nullable|boolean',
            'pompa'      => 'nullable|boolean',
            'alarm'      => 'nullable|boolean',
        ]);

        $device = Device::where('api_key', $request->header('X-Device-Key'))->first();

        if (! $device) {
            return response()->json(['message' => 'API key perangkat tidak valid.'], 401);
        }

        $reading = $device->readings()->create($validated);

        $device->forceFill([
            'rssi'         => $validated['rssi'] ?? $device->rssi,
            'ip'           => $validated['ip'] ?? $device->ip,
            'last_seen_at' => now(),
        ])->save();

        return response()->json(['message' => 'Data tersimpan.', 'data' => $reading], 201);
    }

    // GET /api/v1/readings/latest?device=DEV-01
    public function latest(Request $request)
    {
        $device  = Device::where('code', $request->query('device', 'DEV-01'))->firstOrFail();
        $reading = $device->readings()->orderByDesc('created_at')->first();

        return response()->json(['data' => $reading]);
    }
}
