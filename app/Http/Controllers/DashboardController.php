<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\SensorReading;
use App\Models\Setting;

class DashboardController extends Controller
{
    public function kelembapan()
    {
        $device = Device::orderBy('id')->firstOrFail();

        return view('dashboard.kelembapan', $this->buildData($device));
    }

    // endpoint JSON berat: grafik penuh + tabel (dipakai tiap 60 detik)
    public function data()
    {
        $device = Device::orderBy('id')->firstOrFail();
        $data   = $this->buildData($device);

        return response()->json(collect($data)->except(['device'])->all());
    }

    // endpoint RINGAN untuk live update tiap 1 detik
    public function latest()
    {
        $device = Device::orderBy('id')->firstOrFail();
        $latest = $device->readings()->orderByDesc('created_at')->first();

        return response()->json([
            'latest' => $latest ? [
                'kelembapan' => (float) $latest->kelembapan,
                'suhu'       => $latest->suhu !== null ? (float) $latest->suhu : null,
                'rssi'       => $latest->rssi,
                'time'       => $latest->created_at->format('H:i:s'),
                'jarak'      => $latest->jarak !== null ? (float) $latest->jarak : null,
                'gerakan'    => (bool) $latest->gerakan,
                'pompa'      => (bool) $latest->pompa,
                'alarm'      => (bool) $latest->alarm,
            ] : null,
            'status'       => $latest?->status(),
            'status_label' => $latest ? SensorReading::statusLabel($latest->status()) : null,
            'online'       => $device->isOnline(),
            'pompa_mode'   => DeviceCommand::currentFor($device)->mode,
            'human'        => $latest
                ? ($latest->created_at->diffInSeconds() < 90 ? 'baru saja' : $latest->created_at->diffForHumans())
                : '-',
            'sync_time'    => now()->format('H:i:s'),
        ]);
    }

    private function buildData(Device $device): array
    {
        $latest = $device->readings()->orderByDesc('created_at')->first();

        $hourAgo = $device->readings()
            ->where('created_at', '<=', now()->subHour())
            ->orderByDesc('created_at')
            ->first();

        $delta = ($latest && $hourAgo)
            ? round($latest->kelembapan - $hourAgo->kelembapan, 1)
            : null;

        // rata-rata per menit, maksimal 1440 titik
        $rows = $device->readings()
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at')
            ->get(['kelembapan', 'created_at']);

        $bucket = [];
        foreach ($rows as $r) {
            $key = $r->created_at->format('H:i');
            $bucket[$key][] = (float) $r->kelembapan;
        }
        $series = collect($bucket)
            ->map(fn ($vals, $k) => ['t' => $k, 'v' => round(array_sum($vals) / count($vals), 1)])
            ->values();

        $daily = $device->readings()
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($r) => $r->created_at->format('Y-m-d'))
            ->map(fn ($g) => [
                'label' => $g->first()->created_at->minDayName,
                'value' => (int) round($g->avg('kelembapan')),
            ])
            ->values();

        $history = $device->readings()
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'time'         => $r->created_at->format('H:i:s'),
                'value'        => number_format($r->kelembapan, 1, ',', ''),
                'status'       => $r->status(),
                'status_label' => SensorReading::statusLabel($r->status()),
                'rssi'         => $r->rssi,
            ])
            ->values();

        return [
            'device'       => $device,
            'latest'       => $latest,
            'delta'        => $delta,
            'online'       => $device->isOnline(),
            'human'        => $latest
                ? ($latest->created_at->diffInSeconds() < 90 ? 'baru saja' : $latest->created_at->diffForHumans())
                : '-',
            'series'       => $series,
            'daily'        => $daily,
            'avg'          => $daily->isNotEmpty() ? (int) round($daily->avg('value')) : null,
            'history'      => $history,
            'sync_time'    => now()->format('H:i:s'),
            'dry'          => SensorReading::dryThreshold(),
            'wet'          => SensorReading::wetThreshold(),
            'interval'     => (int) Setting::get('send_interval', 2),
            'status'       => $latest?->status(),
            'status_label' => $latest ? SensorReading::statusLabel($latest->status()) : null,
            'jarak'        => $latest?->jarak,
            'gerakan'      => (bool) $latest?->gerakan,
            'pompa'        => (bool) $latest?->pompa,
            'alarm'        => (bool) $latest?->alarm,
            'pompa_mode'   => DeviceCommand::currentFor($device)->mode,
        ];
    }
}
