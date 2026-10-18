@extends('layouts.app')

@section('content')

<div class="grid-main">
    <div class="card">
        <h2>Pengaturan Sistem</h2>
        <p class="mono muted" style="margin:8px 0 18px">Atur batas status tanah & interval pengiriman data.</p>

        <form method="POST" action="{{ route('pengaturan.update') }}">
            @csrf
            <div class="form-grid">
                <div class="filter-field">
                    <label for="dry">Batas Kering (%)</label>
                    <input type="number" id="dry" name="dry" min="0" max="100"
                           value="{{ old('dry', $dry) }}" oninput="updateZones()">
                    @error('dry')<small style="color:var(--orange);font-family:var(--mono);font-size:11px">{{ $message }}</small>@enderror
                </div>
                <div class="filter-field">
                    <label for="wet">Batas Optimal Atas (%)</label>
                    <input type="number" id="wet" name="wet" min="0" max="100"
                           value="{{ old('wet', $wet) }}" oninput="updateZones()">
                    @error('wet')<small style="color:var(--orange);font-family:var(--mono);font-size:11px">{{ $message }}</small>@enderror
                </div>
                <div class="filter-field">
                    <label for="interval">Interval Kirim (detik)</label>
                    <input type="number" id="interval" name="interval" min="1" max="3600"
                           value="{{ old('interval', $interval) }}">
                    @error('interval')<small style="color:var(--orange);font-family:var(--mono);font-size:11px">{{ $message }}</small>@enderror
                </div>
            </div>

            <div class="zone-preview">
                <div class="zone zone-dry"><h4>KERING</h4><p id="zoneDry">0 – {{ $dry }}%</p></div>
                <div class="zone zone-opt"><h4>OPTIMAL</h4><p id="zoneOpt">{{ $dry }} – {{ $wet }}%</p></div>
                <div class="zone zone-wet"><h4>TERLALU BASAH</h4><p id="zoneWet">{{ $wet }} – 100%</p></div>
            </div>

            <div style="margin-top:24px">
                <button type="submit" class="btn">Simpan Pengaturan</button>
            </div>
        </form>
    </div>

    <div>
        <div class="card">
            <h2>Catatan</h2>
            <ul class="note-list">
                <li>Batas kering = kelembapan di bawah nilai ini dianggap <b>kering</b>.</li>
                <li>Batas optimal atas = di atas nilai ini dianggap <b>terlalu basah</b>.</li>
                <li>Perubahan langsung berlaku di Dashboard & Riwayat.</li>
                <li>Interval kirim hanya catatan/acuan — sesuaikan juga <code>KIRIM_TIAP</code> di bridge/delay di ESP32.</li>
            </ul>
        </div>

        <div class="card" style="margin-top:22px">
            <h2>Info Sistem</h2>
            <ul class="note-list">
                <li>Laravel: {{ app()->version() }}</li>
                <li>PHP: {{ PHP_VERSION }}</li>
                <li>Database: {{ config('database.default') }}</li>
                <li>Zona waktu: {{ config('app.timezone') }}</li>
                <li>Endpoint API: <code>{{ url('api/v1/readings') }}</code></li>
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateZones() {
    const dry = +document.getElementById('dry').value || 0;
    const wet = +document.getElementById('wet').value || 100;
    document.getElementById('zoneDry').textContent = '0 – ' + dry + '%';
    document.getElementById('zoneOpt').textContent = dry + ' – ' + wet + '%';
    document.getElementById('zoneWet').textContent = wet + ' – 100%';
}
</script>
@endpush
