<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\SensorReading;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        [$q, $range, $status] = $this->filteredQuery($request);

        $stats = (clone $q)->selectRaw(
            'COUNT(*) AS total, COALESCE(AVG(kelembapan),0) AS rata,
             COALESCE(MIN(kelembapan),0) AS min, COALESCE(MAX(kelembapan),0) AS max,
             COALESCE(AVG(suhu),0) AS rata_suhu'
        )->first();

        $stats->total     = (int) $stats->total;
        $stats->rata      = (float) $stats->rata;
        $stats->min       = (float) $stats->min;
        $stats->max       = (float) $stats->max;
        $stats->rata_suhu = (float) $stats->rata_suhu;

        $daily = (clone $q)
            ->selectRaw('DATE(created_at) AS d, ROUND(AVG(kelembapan)) AS v')
            ->groupBy('d')->orderBy('d')->get()
            ->map(fn ($r) => [
                'label' => Carbon::parse($r->d)->translatedFormat('d M'),
                'value' => (int) $r->v,
            ])->values();

        $readings = $q->orderByDesc('created_at')->paginate(20)->withQueryString();

        $readings->getCollection()->transform(fn ($r) => [
            'time'   => $r->created_at->format('d M Y, H:i:s'),
            'value'  => number_format($r->kelembapan, 1, ',', ''),
            'temp'   => $r->suhu === null ? '—' : number_format($r->suhu, 1, ',', ''),
            'status' => $r->status(),
            'label'  => SensorReading::statusLabel($r->status()),
            'rssi'   => $r->rssi,
        ]);

        return view('riwayat', compact('range', 'status', 'stats', 'daily', 'readings'));
    }

    public function export(Request $request)
    {
        [$q, $range, $status] = $this->filteredQuery($request);

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['waktu', 'kelembapan (%)', 'suhu (C)', 'status', 'rssi (dBm)']);

            $q->orderBy('created_at')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->created_at->format('Y-m-d H:i:s'),
                        $r->kelembapan,
                        $r->suhu,
                        SensorReading::statusLabel($r->status()),
                        $r->rssi,
                    ]);
                }
            });
            fclose($out);
        }, "riwayat-kelembapan-{$range}hari.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredQuery(Request $request): array
    {
        $range  = in_array((int) $request->query('range'), [1, 7, 30, 90], true)
            ? (int) $request->query('range') : 7;
        $status = in_array($request->query('status'), ['semua', 'optimal', 'kering', 'basah'], true)
            ? $request->query('status') : 'semua';

        $device = Device::orderBy('id')->firstOrFail();

        $q = $device->readings()
            ->where('created_at', '>=', now()->subDays($range)->startOfDay());

        $dry = SensorReading::dryThreshold();
        $wet = SensorReading::wetThreshold();

        if ($status === 'kering')       { $q->where('kelembapan', '<', $dry); }
        elseif ($status === 'optimal')  { $q->whereBetween('kelembapan', [$dry, $wet]); }
        elseif ($status === 'basah')    { $q->where('kelembapan', '>', $wet); }

        return [$q, $range, $status];
    }
}
