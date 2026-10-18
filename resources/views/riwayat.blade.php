@extends('layouts.app')

@section('content')

<section class="grid-stats">
    <div class="card stat">
        <span class="label">Total Data</span>
        <div class="value">{{ number_format($stats->total) }}</div>
        <p class="note">pengukuran sesuai filter</p>
    </div>
    <div class="card stat">
        <span class="label">Rata-rata Kelembapan</span>
        <div class="value">{{ number_format($stats->rata, 1, ',', '') }}%</div>
        <p class="note">pada periode terpilih</p>
    </div>
    <div class="card stat">
        <span class="label">Terendah – Tertinggi</span>
        <div class="value" style="font-size:30px">{{ number_format($stats->min, 1, ',', '') }} – {{ number_format($stats->max, 1, ',', '') }}%</div>
        <p class="note">rentang kelembapan</p>
    </div>
    <div class="card stat">
        <span class="label">Suhu Rata-rata</span>
        <div class="value">{{ number_format($stats->rata_suhu, 1, ',', '') }} °C</div>
        <p class="note">sensor DHT22</p>
    </div>
</section>

<div class="card" style="margin-top:22px">
    <form method="GET" action="{{ route('riwayat') }}" class="filter-bar">
        <div class="filter-field">
            <label for="range">Periode</label>
            <select name="range" id="range">
                <option value="1" @selected($range == 1)>24 jam terakhir</option>
                <option value="7" @selected($range == 7)>7 hari terakhir</option>
                <option value="30" @selected($range == 30)>30 hari terakhir</option>
                <option value="90" @selected($range == 90)>90 hari terakhir</option>
            </select>
        </div>
        <div class="filter-field">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="semua" @selected($status == 'semua')>Semua status</option>
                <option value="optimal" @selected($status == 'optimal')>Optimal</option>
                <option value="kering" @selected($status == 'kering')>Kering</option>
                <option value="basah" @selected($status == 'basah')>Terlalu basah</option>
            </select>
        </div>
        <button type="submit" class="btn">Terapkan Filter</button>
        <a href="{{ route('riwayat.export', request()->query()) }}" class="btn-outline">Ekspor CSV</a>
        <span class="filter-note">{{ number_format($readings->total()) }} data</span>
    </form>
</div>

@if ($daily->count())
<div class="card" style="margin-top:22px">
    <h2>Rata-rata Kelembapan Harian</h2>
    <div class="chart-wrap-sm"><canvas id="chartRiwayat"></canvas></div>
</div>
@endif

<div class="card" style="margin-top:22px">
    <h2>Detail Pengukuran</h2>
    <table>
        <thead>
            <tr><th>Waktu</th><th>Kelembapan</th><th>Suhu</th><th>Status</th><th>Sinyal</th></tr>
        </thead>
        <tbody>
            @forelse ($readings as $r)
            <tr>
                <td>{{ $r['time'] }}</td>
                <td class="value-strong">{{ $r['value'] }} %</td>
                <td>{{ $r['temp'] }} °C</td>
                <td><span class="pill pill-sm pill-{{ $r['status'] }}">{{ $r['label'] }}</span></td>
                <td>{{ $r['rssi'] ?? '—' }} dBm</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:30px">Tidak ada data untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $readings->links() }}
</div>
@endsection

@push('scripts')
<script>
const DAILY_R = @json($daily);
@if ($daily->count())
const MONO_R = "'JetBrains Mono', monospace";
const barLabelsR = {
    id: 'barLabelsR',
    afterDatasetsDraw(c) {
        const m = c.getDatasetMeta(0);
        if (!m.data.length) return;
        const ctx = c.ctx;
        ctx.save();
        ctx.font = `700 12px ${MONO_R}`;
        ctx.fillStyle = '#4b473c';
        ctx.textAlign = 'center';
        m.data.forEach((b, i) => ctx.fillText(String(c.data.datasets[0].data[i]), b.x, b.y - 8));
        ctx.restore();
    }
};
new Chart(document.getElementById('chartRiwayat'), {
    type: 'bar',
    data: {
        labels: DAILY_R.map(d => d.label),
        datasets: [{
            data: DAILY_R.map(d => d.value),
            backgroundColor: DAILY_R.map((_, i) => i === DAILY_R.length - 1 ? '#c15a2b' : '#6d7e52'),
            borderRadius: 7, maxBarThickness: 46
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#33302a' } },
        scales: {
            y: { display: false, suggestedMax: Math.max(...DAILY_R.map(d => d.value)) + 12 },
            x: { grid: { display: false }, border: { color: '#d9d2bc' },
                 ticks: { font: { family: MONO_R, size: 11 },
                          color(c) { return c.tick.label === DAILY_R[DAILY_R.length - 1].label ? '#c15a2b' : '#8b8677'; } } }
        }
    },
    plugins: [barLabelsR]
});
@endif
</script>
@endpush
