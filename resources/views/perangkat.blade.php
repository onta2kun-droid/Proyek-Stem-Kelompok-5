@extends('layouts.app')

@section('content')

<div class="device-grid">
    @foreach ($devices as $device)
    <div class="card">
        <div class="card-head">
            <div>
                <h2>{{ $device->name }}</h2>
                <p class="mono muted" style="margin:6px 0 0">ID: {{ $device->code }}</p>
            </div>
            <span class="device-badge {{ $device->isOnline() ? '' : 'offline' }}">
                <span class="dot"></span> ESP32 {{ $device->isOnline() ? 'ONLINE' : 'OFFLINE' }}
            </span>
        </div>

        <div class="device-stats">
            <div><span class="label">Pembaruan terakhir</span><strong>{{ $device->latest?->created_at?->diffForHumans() ?? 'belum ada data' }}</strong></div>
            <div><span class="label">Alamat IP</span><strong>{{ $device->latest?->ip ?? '—' }}</strong></div>
            <div><span class="label">Sinyal (RSSI)</span><strong>{{ $device->latest?->rssi ?? '—' }} dBm</strong></div>
            <div><span class="label">Total pengukuran</span><strong>{{ number_format($device->readings_count) }} data</strong></div>
            <div><span class="label">Hari ini</span><strong>{{ $device->today_count }} data</strong></div>
            <div><span class="label">Rata-rata hari ini</span><strong>{{ number_format($device->today_avg ?? 0, 1, ',', '') }}%</strong></div>
        </div>

        <div class="key-row">
            <span class="label">API Key</span>
            <code class="key-box" id="key-{{ $device->id }}">••••••••••••••••••••</code>
            <button type="button" class="btn-outline btn-sm" onclick="toggleKey({{ $device->id }}, this)">Tampilkan</button>
        </div>

        <form action="{{ route('perangkat.regenerate', $device) }}" method="POST" style="margin-top:18px"
              onsubmit="return confirm('Buat API key baru? Perangkat dengan key lama tidak bisa mengirim data lagi.')">
            @csrf
            <button type="submit" class="btn-outline btn-danger">Regenerate API Key</button>
        </form>
    </div>
    @endforeach
</div>

<div class="card" style="margin-top:22px">
    <h2>Cara Menghubungkan Alat (ESP32)</h2>
    <p style="font-family:var(--mono);font-size:12.5px;color:var(--muted);margin:10px 0 14px">
        Kirim POST request ke endpoint berikut setiap interval pengiriman. Header <b>X-Device-Key</b> wajib diisi API key perangkat.
    </p>
    <div class="api-sample">POST {{ url('api/v1/readings') }}
Content-Type: application/json
X-Device-Key: API_KEY_ANDA

{"kelembapan": 56.2, "suhu": 27.4, "rssi": -62, "ip": "192.168.1.24"}</div>
</div>
@endsection

@push('scripts')
<script>
const KEYS = @json($devices->mapWithKeys(fn ($d) => [$d->id => $d->api_key]));
function toggleKey(id, btn) {
    const el = document.getElementById('key-' + id);
    if (el.dataset.shown) {
        el.textContent = '••••••••••••••••••••';
        el.dataset.shown = '';
        btn.textContent = 'Tampilkan';
    } else {
        el.textContent = KEYS[id];
        el.dataset.shown = '1';
        btn.textContent = 'Sembunyikan';
    }
}
</script>
@endpush
