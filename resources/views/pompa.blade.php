@extends('layouts.app')

@section('content')

<div class="grid-main">
    <div class="card" style="text-align:center">
        <div style="text-align:left">
            <h2>Kontrol Pompa — Jarak Jauh</h2>
            <p class="mono muted" style="margin:8px 0 0">Perintah diteruskan ke alat saat ESP32 mengecek server (maks ±2 detik).</p>
        </div>

        @php
            $isAuto = $cmd->mode === 'auto';
            $isOn   = $cmd->mode === 'on';
            $isOff  = $cmd->mode === 'off';
        @endphp

        <p class="mono muted" style="margin:26px 0 6px">MODE AKTIF</p>
        <div class="stat value" style="font-size:40px">
            @if ($isAuto) ⚙️ AUTO
            @elseif ($isOn) 💧 ON (MANUAL)
            @else 🛑 OFF (PAKSA)
            @endif
        </div>
        <p class="note">{{ $cmd->issued_at ? 'diperbarui '.$cmd->issued_at->diffForHumans() : 'belum pernah diubah' }}</p>

        <div style="display:flex;gap:14px;justify-content:center;margin-top:26px;flex-wrap:wrap">
            <form action="{{ route('pompa.mode', 'on') }}" method="POST">
                @csrf
                <button type="submit" class="btn" style="padding:14px 24px;{{ $isOn ? 'opacity:.5' : '' }}">💧 Nyalakan Pompa</button>
            </form>
            <form action="{{ route('pompa.mode', 'off') }}" method="POST">
                @csrf
                <button type="submit" class="btn-outline" style="padding:14px 24px;{{ $isOff ? 'opacity:.5' : '' }}">🛑 Matikan Pompa</button>
            </form>
            <form action="{{ route('pompa.mode', 'auto') }}" method="POST">
                @csrf
                <button type="submit" class="btn-outline" style="padding:14px 24px;{{ $isAuto ? 'opacity:.5' : '' }}">⚙️ Mode Otomatis</button>
            </form>
        </div>

        <p class="note" style="margin-top:22px">
            AUTO = ikut sensor (histeresis) · ON = paksa menyiram · OFF = paksa berhenti
        </p>
    </div>

    <div class="card">
        <h2>Status Terakhir Alat</h2>
        <div class="device-stats">
            <div><span class="label">Kelembapan</span><strong>{{ $latest ? number_format($latest->kelembapan, 1, ',', '').'%' : '—' }}</strong></div>
            <div><span class="label">Pompa (dari alat)</span><strong>{{ $latest?->pompa ? 'ON' : 'OFF' }}</strong></div>
            <div><span class="label">Jarak objek</span><strong>{{ $latest && $latest->jarak !== null && $latest->jarak < 999 ? number_format($latest->jarak, 0, ',', '').' cm' : '—' }}</strong></div>
            <div><span class="label">PIR</span><strong>{{ $latest?->gerakan ? 'MENDETEKSI' : 'SIAGA' }}</strong></div>
            <div><span class="label">Alarm</span><strong>{{ $latest?->alarm ? 'AKTIF' : 'AMAN' }}</strong></div>
            <div><span class="label">Perangkat</span><strong>{{ $device->isOnline() ? 'ESP32 ONLINE' : 'OFFLINE' }}</strong></div>
        </div>
        <p class="note" style="margin-top:18px">
            Mode <b>OFF (paksa)</b> berguna saat perawatan — pompa tidak akan menyala walau tanah kering.
        </p>
    </div>
</div>
@endsection
