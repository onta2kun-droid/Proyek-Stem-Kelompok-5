<?php

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Http\Controllers\Api\SensorApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('readings', [SensorApiController::class, 'store']);
    Route::get('readings/latest', [SensorApiController::class, 'latest']);

    // ESP32: mode pompa
    Route::get('pump/mode', function (\Illuminate\Http\Request $request) {
        $device = Device::where('api_key', $request->header('X-Device-Key'))->first();
        if (! $device) {
            return response()->json(['message' => 'API key tidak valid.'], 401);
        }
        return response()->json(['mode' => DeviceCommand::currentFor($device)->mode]);
    });

    // ESP32: status alarm (BARU) — 1 = armed, 0 = disarmed
    Route::get('alarm/state', function (\Illuminate\Http\Request $request) {
        $device = Device::where('api_key', $request->header('X-Device-Key'))->first();
        if (! $device) {
            return response()->json(['message' => 'API key tidak valid.'], 401);
        }
        return response()->json([
            'enabled' => (int) \App\Models\Setting::get('alarm_enabled', '1') === 1,
        ]);
    });
});
