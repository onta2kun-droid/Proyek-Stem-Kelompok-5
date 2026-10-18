@extends('layouts.app')

@section('content')

<style>
    .radar-wrap{margin-top:14px;border:1px solid var(--line);border-radius:14px;
                background:rgba(255,255,255,.45);padding:8px}
    #radarSvg{width:100%;height:auto;display:block}
    #radarCard.alarm{border-color:var(--orange);animation:radarAlarm 1.1s infinite}
    @keyframes radarAlarm{0%,100%{box-shadow:0 0 0 0 rgba(193,90,43,.30)}50%{box-shadow:0 0 0 10px rgba(193,90,43,0)}}
    .pill-idle{background:#b3ac97}
    .pill-alarm{background:var(--orange);animation:blinkA .9s infinite}
    @keyframes blinkA{50%{opacity:.45}}
    .prot-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
    .prot-box{border:1px solid var(--line);border-radius:12px;padding:14px 16px;background:rgba(255,255,255,.5)}
    .prot-box .label{font-family:var(--mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
    .prot-box .pv{font-size:30px;font-weight:600;margin:6px 0 2px}
    .prot-box .pv small{font-size:15px;color:var(--muted);font-weight:400}
</style>

<section class="grid-stats">
    <div class="card stat">
        <span class="label">Kelembapan Tanah</span>
        <div class="value" id="statKelembapan">{{ (int) round($latest->kelembapan) }}%</div>
        <p class="note {{ $delta !== null && $delta < 0 ? 'down' : 'up' }}" id="statTrend">
            @if ($delta !== null)
                {{ $delta < 0 ? 'turun' : 'naik' }} {{ number_format(abs($delta), 1, ',', '') }}% dari 1 jam lalu
            @else
                data 1 jam lalu belum tersedia
            @endif
        </p>
    </div>

    <div class="card stat">
        <span class="label">Status Tanah</span>
        <div style="margin:16px 0 20px">
            <span class="pill pill-{{ $latest->status() }}" id="statStatus">{{ $status_label }}</span>
        </div>
        <p class="note">rentang ideal {{ $dry }}–{{ $wet }}%</p>
    </div>

    <div class="card stat">
        <span class="label">Suhu Tanah</span>
        <div class="value" id="statSuhu">{{ $latest->suhu !== null ? number_format($latest->suhu, 1, ',', '') . ' °C' : '—' }}</div>
        <p class="note">sensor DHT · stabil</p>
    </div>

    <div class="card stat">
        <span class="label">Pembaruan Terakhir</span>
        <div class="value" id="statUpdated">{{ $human }}</div>
        <p class="note">interval kirim {{ $interval }} detik</p>
    </div>
</section>

<section class="grid-main">
    <div class="card">
        <div class="card-head">
            <h2>Kelembapan Tanah — 24 Jam Terakhir</h2>
            <div class="legend">
                <span><i class="lg-line"></i>Kelembapan</span>
                <span><i class="lg-band"></i>Rentang optimal</span>
                <span><i class="lg-dash"></i>Batas kering {{ $dry }}%</span>
            </div>
        </div>
        <div class="chart-wrap"><canvas id="chart24h"></canvas></div>
    </div>

    <div class="card gauge-card">
        <h2>Kelembapan Saat Ini</h2>
        <div id="gauge"></div>
        <div class="chips">
            <span class="chip">ID: {{ $device->code }}</span>
            <span class="chip">RSSI: {{ $latest->rssi ?? '—' }} dBm</span>
            <span class="chip">IP: {{ $latest->ip ?? '—' }}</span>
        </div>
    </div>
</section>

{{-- ===== RADAR PROTEKSI + PANEL POMPA ===== --}}
<section class="grid-main" style="grid-template-columns:1.25fr 1fr">
    <div class="card" id="radarCard">
        <div class="card-head">
            <h2>Radar Proteksi — HC-SR04 + PIR</h2>
            <div class="legend">
                <span><i class="lg-line" style="border-color:var(--blue)"></i>Sweep</span>
                <span><i class="lg-band" style="background:rgba(193,90,43,.30)"></i>PIR ±45°</span>
                <span><i class="lg-dash"></i>Zona ancaman 60 cm</span>
            </div>
        </div>
        <div class="radar-wrap">
            <svg id="radarSvg" viewBox="0 0 520 368"></svg>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Proteksi & Pompa</h2>
            <a href="{{ route('pompa') }}" class="btn-outline btn-sm">Kontrol Pompa →</a>
        </div>
        <div class="prot-grid">
            <div class="prot-box">
                <span class="label">Jarak Objek</span>
                <div class="pv" id="protJarak">{{ $latest && $latest->jarak !== null && $latest->jarak < 999 ? number_format($latest->jarak, 0, ',', '').' cm' : '—' }}</div>
                <p class="note">zona ancaman &lt; 60 cm</p>
            </div>
            <div class="prot-box">
                <span class="label">Sensor Gerak (PIR)</span>
                <div style="margin:14px 0 10px">
                    <span class="pill {{ $latest?->gerakan ? 'pill-kering' : 'pill-idle' }}" id="protPir">{{ $latest?->gerakan ? 'MENDETEKSI' : 'SIAGA' }}</span>
                </div>
            </div>
            <div class="prot-box">
                <span class="label">Alarm Penghalau</span>
                <div style="margin:14px 0 10px">
                    <span class="pill {{ $latest?->alarm ? 'pill-alarm' : 'pill-optimal' }}" id="protAlarm">{{ $latest?->alarm ? 'MENGHALAU' : 'AMAN' }}</span>
                </div>
            </div>
            <div class="prot-box">
                <span class="label">Pompa Air</span>
                <div style="margin:14px 0 6px">
                    <span class="pill {{ $latest?->pompa ? 'pill-optimal' : 'pill-idle' }}" id="protPompa">{{ $latest?->pompa ? 'ON' : 'OFF' }}</span>
                </div>
                <p class="note">mode web: <b id="protMode">{{ $pompa_mode }}</b></p>
            </div>
        </div>
    </div>
</section>

<section class="grid-bottom">
    <div class="card">
        <h2>Rata-rata Kelembapan Harian (7 Hari)</h2>
        <div class="chart-wrap-sm"><canvas id="chart7d"></canvas></div>
    </div>

    <div class="card">
        <h2>Riwayat Pengukuran Terakhir</h2>
        <table>
            <thead>
                <tr><th>Waktu</th><th>Kelembapan</th><th>Status</th><th>Sinyal</th></tr>
            </thead>
            <tbody id="historyBody">
                @foreach ($history as $h)
                <tr>
                    <td>{{ $h['time'] }}</td>
                    <td class="value-strong">{{ $h['value'] }} %</td>
                    <td><span class="pill pill-sm pill-{{ $h['status'] }}">{{ $h['status_label'] }}</span></td>
                    <td>{{ $h['rssi'] ?? '—' }} dBm</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<script>
const SERIES = @json($series);
const DAILY  = @json($daily);
const DRY    = @json($dry);
const WET    = @json($wet);
const MONO   = "'JetBrains Mono', monospace";
const STATUS_CLASS = { 'OPTIMAL': 'pill-optimal', 'KERING': 'pill-kering', 'TERLALU BASAH': 'pill-basah' };
const fmt1 = n => n.toFixed(1).replace('.', ',');
const RADAR_INIT = @json(['jarak' => $jarak, 'gerakan' => $gerakan, 'alarm' => $alarm]);

/* ============ GAUGE SVG ============ */
function polar(cx, cy, r, deg) {
    const rad = deg * Math.PI / 180;
    return [cx + r * Math.cos(rad), cy - r * Math.sin(rad)];
}
function arcPath(cx, cy, r, v1, v2) {
    const a1 = 180 - v1 * 1.8, a2 = 180 - v2 * 1.8;
    const [x1, y1] = polar(cx, cy, r, a1);
    const [x2, y2] = polar(cx, cy, r, a2);
    return `M ${x1.toFixed(1)} ${y1.toFixed(1)} A ${r} ${r} 0 ${(a1 - a2) > 180 ? 1 : 0} 1 ${x2.toFixed(1)} ${y2.toFixed(1)}`;
}
function renderGauge(value, statusLabel) {
    const cx = 170, cy = 158, r = 118;
    const segs = [[0, DRY, '#c15a2b'], [DRY, WET, '#6d7e52'], [WET, 100, '#4e7d8c']];
    const paths = segs.map(([a, b, c]) =>
        `<path d="${arcPath(cx, cy, r, a, b)}" stroke="${c}" stroke-width="26" fill="none"/>`).join('');
    const ticks = [0, 25, 50, 75, 100].map(v => {
        const [x, y] = polar(cx, cy, r + 24, 180 - v * 1.8);
        return `<text x="${x.toFixed(1)}" y="${(y + 4).toFixed(1)}" text-anchor="middle" class="gauge-tick">${v}</text>`;
    }).join('');
    const ang = 180 - Math.max(0, Math.min(100, value)) * 1.8;
    const [tx, ty] = polar(cx, cy, r - 32, ang);
    const cls = STATUS_CLASS[statusLabel] || 'pill-optimal';
    document.getElementById('gauge').innerHTML = `
        <svg viewBox="0 0 340 224">
            ${paths}${ticks}
            <line x1="${cx}" y1="${cy - 4}" x2="${tx.toFixed(1)}" y2="${ty.toFixed(1)}"
                  stroke="#33302a" stroke-width="4.5" stroke-linecap="round"/>
            <circle cx="${cx}" cy="${cy - 4}" r="6.5" fill="#33302a"/>
            <text x="${cx}" y="${cy + 48}" text-anchor="middle" class="gauge-value">${Math.round(value)}%</text>
        </svg>
        <div class="gauge-badge"><span class="pill ${cls}">${statusLabel}</span></div>`;
}
renderGauge({{ (float) $latest->kelembapan }}, '{{ $status_label }}');

/* ============ PLUGIN: pita optimal + batas kering ============ */
const optimalBand = {
    id: 'optimalBand',
    beforeDatasetsDraw(chart, args, opts) {
        const { ctx, chartArea, scales: { y } } = chart;
        if (!chartArea) return;
        const top = y.getPixelForValue(opts.to ?? WET);
        const bottom = y.getPixelForValue(opts.from ?? DRY);
        ctx.save();
        ctx.fillStyle = 'rgba(109,126,82,.16)';
        ctx.fillRect(chartArea.left, top, chartArea.right - chartArea.left, bottom - top);
        ctx.strokeStyle = '#c15a2b'; ctx.lineWidth = 1.6; ctx.setLineDash([7, 6]);
        ctx.beginPath();
        ctx.moveTo(chartArea.left, y.getPixelForValue(DRY));
        ctx.lineTo(chartArea.right, y.getPixelForValue(DRY));
        ctx.stroke();
        ctx.restore();
    }
};

/* ============ GRAFIK 24 JAM ============ */
const labels24 = SERIES.map(p => p.t);
if (labels24.length) labels24[labels24.length - 1] = 'Sekarang';
const values24 = SERIES.map(p => p.v);

window.chart24h = new Chart(document.getElementById('chart24h'), {
    type: 'line',
    data: {
        labels: labels24,
        datasets: [{
            data: values24,
            borderColor: '#6d7e52', borderWidth: 2.5, tension: .35,
            pointRadius: values24.map((_, i) => i === values24.length - 1 ? 5 : 0),
            pointBackgroundColor: '#fbf9f2', pointBorderColor: '#c15a2b', pointBorderWidth: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            optimalBand: { from: DRY, to: WET },
            tooltip: {
                backgroundColor: '#33302a',
                titleFont: { family: MONO, size: 11 }, bodyFont: { family: MONO, size: 12 },
                callbacks: { label: c => ` Kelembapan: ${c.parsed.y}%` }
            }
        },
        scales: {
            y: { min: 0, max: 100,
                 ticks: { stepSize: 20, color: '#8b8677', font: { family: MONO, size: 11 } },
                 grid: { color: '#ece6d3' }, border: { display: false } },
            x: { grid: { display: false }, border: { display: false },
                 ticks: { autoSkip: false, maxRotation: 0, font: { family: MONO, size: 11 },
                     callback(value, index) {
                         const total = this.getLabels().length;
                         const step = Math.max(1, Math.ceil(total / 8));
                         return (index === total - 1 || index % step === 0) ? this.getLabelForValue(value) : '';
                     },
                     color(c) { return c.tick.label === 'Sekarang' ? '#c15a2b' : '#8b8677'; }
                 } }
        }
    },
    plugins: [optimalBand]
});

/* ============ GRAFIK 7 HARI ============ */
const dLabels = DAILY.map(d => d.label);
const dValues = DAILY.map(d => d.value);
window.avg7     = dValues.length ? dValues.reduce((a, b) => a + b, 0) / dValues.length : 0;
window.lastDay7 = dLabels.length ? dLabels[dLabels.length - 1] : '';

const barValueLabels = {
    id: 'barValueLabels',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        const meta = chart.getDatasetMeta(0);
        if (!meta.data.length) return;
        ctx.save();
        ctx.font = `700 12px ${MONO}`; ctx.fillStyle = '#4b473c'; ctx.textAlign = 'center';
        meta.data.forEach((bar, i) => ctx.fillText(String(chart.data.datasets[0].data[i]), bar.x, bar.y - 8));
        ctx.restore();
    }
};
const avgLineLabel = {
    id: 'avgLineLabel',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        const meta = chart.getDatasetMeta(1);
        if (!meta.data.length) return;
        const p = meta.data[meta.data.length - 1];
        ctx.save();
        ctx.font = `600 12px ${MONO}`; ctx.fillStyle = '#5c584c'; ctx.textAlign = 'right';
        ctx.fillText(`rata-rata ${Math.round(window.avg7)}%`, p.x + 4, p.y - 9);
        ctx.restore();
    }
};

window.chart7d = new Chart(document.getElementById('chart7d'), {
    data: {
        labels: dLabels,
        datasets: [
            { type: 'bar', data: dValues,
              backgroundColor: dValues.map((_, i) => i === dValues.length - 1 ? '#c15a2b' : '#6d7e52'),
              borderRadius: 7, maxBarThickness: 58 },
            { type: 'line', data: dValues.map(() => +window.avg7.toFixed(1)),
              borderColor: '#55514a', borderDash: [6, 5], borderWidth: 1.5, pointRadius: 0 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false },
                   tooltip: { backgroundColor: '#33302a',
                              titleFont: { family: MONO, size: 11 }, bodyFont: { family: MONO, size: 12 } } },
        scales: {
            y: { display: false, min: 0, suggestedMax: Math.max(...dValues, window.avg7) + 12 },
            x: { grid: { display: false }, border: { color: '#d9d2bc' },
                 ticks: { font: { family: MONO, size: 12 },
                          color(c) { return c.tick.label === window.lastDay7 ? '#c15a2b' : '#8b8677'; } } }
        }
    },
    plugins: [barValueLabels, avgLineLabel]
});

/* ================================================================
   RADAR PROTEKSI — SVG, gaya sesuai desain web (kertas/hijau/oranye)
   Sweep 180° · ring 30/60/90/120cm · sektor PIR ±45° · beam ±15°
   ================================================================ */
const RADAR = {
    jarak: RADAR_INIT.jarak, gerakan: RADAR_INIT.gerakan, alarm: RADAR_INIT.alarm,
    MAXCM: 120, CX: 260, CY: 305, R: 245,
};
const NSR = 'http://www.w3.org/2000/svg';
const svgR = document.getElementById('radarSvg');
const ptR = (deg, r) => {
    const a = deg * Math.PI / 180;
    return [RADAR.CX + Math.sin(a) * r, RADAR.CY - Math.cos(a) * r];
};
function svgEl(n, attrs, parent) {
    const e = document.createElementNS(NSR, n);
    for (const k in attrs) e.setAttribute(k, attrs[k]);
    (parent || svgR).appendChild(e);
    return e;
}
function arcD(r, a1, a2) {
    const [x1, y1] = ptR(a1, r), [x2, y2] = ptR(a2, r);
    return `M ${x1} ${y1} A ${r} ${r} 0 ${a2 - a1 > 180 ? 1 : 0} 1 ${x2} ${y2}`;
}
function wedgeD(half) {
    const [x1, y1] = ptR(-half, RADAR.R), [x2, y2] = ptR(half, RADAR.R);
    return `M ${RADAR.CX} ${RADAR.CY} L ${x1} ${y1} A ${RADAR.R} ${RADAR.R} 0 0 1 ${x2} ${y2} Z`;
}

let pirSector, trailLines = [], blipsG;
const TRAIL = 26;

function buildRadar() {
    svgR.innerHTML = '';
    const C = RADAR.CX, CY = RADAR.CY, R = RADAR.R;
    // garis tanah + arsir
    svgEl('line', { x1: C - R - 20, y1: CY, x2: C + R + 20, y2: CY, stroke: '#33302a', 'stroke-width': 2 });
    for (let x = -R - 14; x <= R + 14; x += 22)
        svgEl('line', { x1: C + x, y1: CY, x2: C + x - 9, y2: CY + 9, stroke: '#8b8677', 'stroke-width': 1, opacity: .55 });
    // arc utama
    svgEl('path', { d: arcD(R, -90, 90), fill: 'none', stroke: '#33302a', 'stroke-width': 2 });
    // ring jarak (60cm = zona ancaman, oranye putus-putus)
    [30, 60, 90, 120].forEach(cm => {
        const r = R * cm / RADAR.MAXCM;
        svgEl('path', { d: arcD(r, -90, 90), fill: 'none',
            stroke: cm === 60 ? '#c15a2b' : '#33302a',
            'stroke-width': cm === 60 ? 1.6 : 1,
            'stroke-dasharray': cm === 60 ? '6 5' : 'none',
            opacity: cm === 60 ? .9 : .22 });
    });
    // label ring
    [30, 60, 90, 120].forEach(cm => {
        const r = R * cm / RADAR.MAXCM, [x, y] = ptR(-45, r);
        svgEl('text', { x: x - 8, y: y + 4, fill: '#8b8677', 'font-size': 10,
            'text-anchor': 'end', 'font-family': MONO }).textContent = cm === 120 ? '120 cm' : cm;
    });
    // tick & label derajat
    for (let d = -90; d <= 90; d += 15) {
        const [x1, y1] = ptR(d, R), [x2, y2] = ptR(d, R + 9);
        svgEl('line', { x1, y1, x2, y2, stroke: '#33302a', 'stroke-width': 1, opacity: .5 });
    }
    [-90, -45, 0, 45, 90].forEach(d => {
        const [x, y] = ptR(d, R + 20);
        svgEl('text', { x, y: y + 4, fill: '#8b8677', 'font-size': 9.5, 'text-anchor': 'middle',
            'font-family': MONO }).textContent = d === 0 ? '0°' : (d > 0 ? '+' : '') + d + '°';
    });
    // sektor PIR (dinamis) + sektor beam
    pirSector = svgEl('path', { d: wedgeD(45), fill: '#c15a2b', opacity: .06 });
    svgEl('path', { d: wedgeD(15), fill: '#6d7e52', opacity: .12 });
    let p = ptR(-45, R * 0.55);
    svgEl('text', { x: p[0], y: p[1], fill: '#8b8677', 'font-size': 9.5, 'text-anchor': 'middle',
        'font-family': MONO }).textContent = 'PIR ±45°';
    p = ptR(14, R * 0.32);
    svgEl('text', { x: p[0], y: p[1], fill: '#5c6b44', 'font-size': 9.5, 'text-anchor': 'middle',
        'font-family': MONO }).textContent = 'HC-SR04 ±15°';
    // jejak sweep
    trailLines = [];
    for (let i = 0; i < TRAIL; i++)
        trailLines.push(svgEl('line', { x1: C, y1: CY, x2: C, y2: CY - R,
            stroke: '#4e7d8c', 'stroke-width': 2.4 - 1.6 * i / TRAIL, opacity: 0 }));
    // grup blip
    blipsG = svgEl('g', {});
    // modul sensor di garis dasar
    svgEl('rect', { x: C - 26, y: CY + 8, width: 52, height: 26, rx: 5, fill: '#6d7e52' });
    [-12, 12].forEach(dx => {
        svgEl('circle', { cx: C + dx, cy: CY + 19, r: 5, fill: '#f1ecdf' });
        svgEl('circle', { cx: C + dx, cy: CY + 19, r: 2.2, fill: '#33302a' });
    });
    svgEl('text', { x: C, y: CY + 52, fill: '#8b8677', 'font-size': 10, 'text-anchor': 'middle',
        'font-family': MONO }).textContent = 'HC-SR04 + PIR · menghadap depan';
}

let sweepDeg = -90, sweepDir = 1, lastRaf = 0;
function radarTick(ts) {
    if (!lastRaf) lastRaf = ts;
    const dt = Math.min(60, ts - lastRaf); lastRaf = ts;
    sweepDeg += sweepDir * dt * 0.13;
    if (sweepDeg >= 90) { sweepDeg = 90; sweepDir = -1; }
    if (sweepDeg <= -90) { sweepDeg = -90; sweepDir = 1; }
    const col = RADAR.alarm ? '#c15a2b' : '#4e7d8c';
    for (let i = 0; i < TRAIL; i++) {
        const a = Math.max(-90, Math.min(90, sweepDeg - sweepDir * i * 1.4));
        const [x, y] = ptR(a, RADAR.R);
        const l = trailLines[i];
        l.setAttribute('x1', RADAR.CX); l.setAttribute('y1', RADAR.CY);
        l.setAttribute('x2', x); l.setAttribute('y2', y);
        l.setAttribute('stroke', col);
        l.setAttribute('opacity', (1 - i / TRAIL) * (RADAR.alarm ? .95 : .85));
    }
    const pul = RADAR.gerakan ? .30 + .18 * Math.abs(Math.sin(ts * .006)) : .06;
    pirSector.setAttribute('opacity', RADAR.alarm ? .42 : pul);
    const now = performance.now();
    for (let i = blipsG.childNodes.length - 1; i >= 0; i--) {
        const b = blipsG.childNodes[i];
        const age = now - b._t;
        if (age > 4000) { blipsG.removeChild(b); continue; }
        b.setAttribute('opacity', String(1 - age / 4000));
    }
    requestAnimationFrame(radarTick);
}

let lastBlipLabel = null;
function addBlip() {
    const d = RADAR.jarak;
    if (d == null || d < 2 || d > RADAR.MAXCM) return;
    const ang = (Math.random() * 2 - 1) * 10;
    const [x, y] = ptR(ang, RADAR.R * d / RADAR.MAXCM);
    const c = svgEl('circle', { cx: x, cy: y, r: 6, fill: RADAR.gerakan ? '#c15a2b' : '#4e7d8c' }, blipsG);
    c._t = performance.now();
    if (lastBlipLabel) lastBlipLabel.remove();
    lastBlipLabel = svgEl('text', { x, y: y - 12, fill: '#33302a', 'font-size': 11,
        'text-anchor': 'middle', 'font-weight': 700, 'font-family': MONO }, blipsG);
    lastBlipLabel.textContent = Math.round(d) + ' cm';
    lastBlipLabel._t = performance.now();
}
function setPill(id, text, cls) {
    const e = document.getElementById(id);
    e.textContent = text;
    e.className = 'pill ' + cls;
}

buildRadar();
requestAnimationFrame(radarTick);

/* ============ LIVE UPDATE — cepat (1 detik) ============ */
async function fastRefresh() {
    try {
        const res = await fetch('{{ route('dashboard.kelembapan.latest') }}?_=' + Date.now(), { cache: 'no-store' });
        if (!res.ok) return;
        const d = await res.json();
        if (!d.latest) return;

        document.getElementById('statKelembapan').textContent = Math.round(d.latest.kelembapan) + '%';

        const pill = document.getElementById('statStatus');
        pill.textContent = d.status_label;
        pill.className = 'pill pill-' + d.status;

        document.getElementById('statSuhu').textContent =
            d.latest.suhu === null ? '—' : fmt1(d.latest.suhu) + ' °C';
        document.getElementById('statUpdated').textContent = d.human;

        const badge = document.getElementById('deviceBadge');
        badge.classList.toggle('offline', !d.online);
        badge.querySelector('.badge-text').textContent = d.online ? 'ESP32 ONLINE' : 'ESP32 OFFLINE';

        renderGauge(d.latest.kelembapan, d.status_label);

        // ---- radar & proteksi ----
        if (d.latest.jarak !== null && d.latest.jarak !== undefined) {
            RADAR.jarak = d.latest.jarak;
            if (d.latest.jarak < RADAR.MAXCM) addBlip();
        }
        RADAR.gerakan = !!d.latest.gerakan;
        RADAR.alarm   = !!d.latest.alarm;
        document.getElementById('radarCard').classList.toggle('alarm', RADAR.alarm);

        const pj = document.getElementById('protJarak');
        pj.innerHTML = (d.latest.jarak !== null && d.latest.jarak < 999)
            ? Math.round(d.latest.jarak) + ' cm' : '—';
        setPill('protPir',   d.latest.gerakan ? 'MENDETEKSI' : 'SIAGA',    d.latest.gerakan ? 'pill-kering' : 'pill-idle');
        setPill('protAlarm', d.latest.alarm   ? 'MENGHALAU'  : 'AMAN',     d.latest.alarm   ? 'pill-alarm'  : 'pill-optimal');
        setPill('protPompa', d.latest.pompa   ? 'ON'          : 'OFF',     d.latest.pompa   ? 'pill-optimal': 'pill-idle');
        const pm = document.getElementById('protMode');
        if (pm && d.pompa_mode) pm.textContent = d.pompa_mode;

        const label  = d.latest.time.slice(0, 5);
        const labels = chart24h.data.labels;
        const data   = chart24h.data.datasets[0].data;
        const ds     = chart24h.data.datasets[0];

        if (labels.length) {
            if (labels[labels.length - 1] === 'Sekarang') {
                labels[labels.length - 1] = label;
                data[data.length - 1]     = d.latest.kelembapan;
            } else if (label === labels[labels.length - 1]) {
                data[data.length - 1] = d.latest.kelembapan;
            } else {
                labels.push(label);
                data.push(d.latest.kelembapan);
                if (labels.length > 1440) { labels.shift(); data.shift(); }
            }
            ds.pointRadius = labels.map((_, i) => i === labels.length - 1 ? 5 : 0);
            chart24h.update('none');
        }

        document.getElementById('syncTime').textContent = 'Sinkronisasi: ' + d.sync_time + ' WIB';
    } catch (e) { /* coba lagi detik depan */ }
}

/* ============ LIVE UPDATE — berat (60 detik) ============ */
async function heavyRefresh() {
    try {
        const res = await fetch('{{ route('dashboard.kelembapan.data') }}?_=' + Date.now(), { cache: 'no-store' });
        if (!res.ok) return;
        const d = await res.json();
        if (!d.latest) return;

        const trend = document.getElementById('statTrend');
        if (d.delta === null) {
            trend.textContent = 'data 1 jam lalu belum tersedia';
        } else {
            trend.textContent = (d.delta < 0 ? 'turun ' : 'naik ') + fmt1(Math.abs(d.delta)) + '% dari 1 jam lalu';
            trend.classList.toggle('down', d.delta < 0);
            trend.classList.toggle('up', d.delta >= 0);
        }

        const l = d.series.map(p => p.t);
        if (l.length) l[l.length - 1] = 'Sekarang';
        chart24h.data.labels = l;
        chart24h.data.datasets[0].data = d.series.map(p => p.v);
        const n = l.length;
        chart24h.data.datasets[0].pointRadius = d.series.map((_, i) => i === n - 1 ? 5 : 0);
        chart24h.update('none');

        window.avg7     = d.avg ?? window.avg7;
        window.lastDay7 = d.daily.length ? d.daily[d.daily.length - 1].label : window.lastDay7;
        chart7d.data.labels = d.daily.map(x => x.label);
        chart7d.data.datasets[0].data = d.daily.map(x => x.value);
        chart7d.data.datasets[0].backgroundColor =
            d.daily.map((_, i) => i === d.daily.length - 1 ? '#c15a2b' : '#6d7e52');
        chart7d.data.datasets[1].data = d.daily.map(() => +(d.avg ?? 0));
        chart7d.options.scales.y.suggestedMax = Math.max(...d.daily.map(x => x.value), d.avg ?? 0) + 12;
        chart7d.update();

        document.getElementById('historyBody').innerHTML = d.history.map(r => `
            <tr>
                <td>${r.time}</td>
                <td class="value-strong">${r.value} %</td>
                <td><span class="pill pill-sm pill-${r.status}">${r.status_label}</span></td>
                <td>${r.rssi ?? '—'} dBm</td>
            </tr>`).join('');
    } catch (e) {
        console.warn('Gagal memuat data', e);
    }
}

setInterval(fastRefresh, 1000);
setInterval(heavyRefresh, 60000);
fastRefresh();
heavyRefresh();
</script>
@endsection
