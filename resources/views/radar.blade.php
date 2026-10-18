@extends('layouts.app')

@section('content')

@php
    $radarInit = [
        'jarak'   => $jarak,
        'gerakan' => $gerakan,
        'alarm'   => $alarm,
        'armed'   => $armed,
    ];
@endphp

<style>
    .radar-wrap{margin-top:14px;border:1px solid var(--line);border-radius:14px;
                background:rgba(255,255,255,.45);padding:8px}
    #radarSvg{width:100%;height:auto;display:block}
    #radarCard.alarm{border-color:var(--orange);animation:radarAlarm 1.1s infinite}
    @keyframes radarAlarm{0%,100%{box-shadow:0 0 0 0 rgba(193,90,43,.30)}50%{box-shadow:0 0 0 10px rgba(193,90,43,0)}}
    .prot-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
    .prot-box{border:1px solid var(--line);border-radius:12px;padding:14px 16px;background:rgba(255,255,255,.5)}
    .prot-box .label{font-family:var(--mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
    .prot-box .pv{font-size:30px;font-weight:600;margin:6px 0 2px}
    .btn-big{width:100%;padding:18px;font-size:14px}
</style>

<section class="grid-main" style="grid-template-columns:1.35fr 1fr">
    <div class="card" id="radarCard">
        <div class="card-head">
            <h2>Radar Proteksi — 180°</h2>
            <div class="legend">
                <span><i class="lg-line" style="border-color:var(--blue)"></i>Sweep</span>
                <span><i class="lg-band" style="background:rgba(193,90,43,.30)"></i>PIR ±45°</span>
                <span><i class="lg-dash"></i>Zona &lt; 90 cm</span>
            </div>
        </div>
        <div class="radar-wrap">
            <svg id="radarSvg" viewBox="0 0 520 368"></svg>
        </div>
        <p class="note" style="margin-top:10px">
            HC-SR04 (jarak) + PIR (gerakan) — sweep biru = armed · oranye = alarm berbunyi · abu = disarmed
        </p>
    </div>

    <div>
        <div class="card">
            <h2>Sistem Alarm</h2>
            <p class="mono muted" style="margin:8px 0 0">Status diteruskan ke alat saat ESP32 mengecek server (maks ±2 dtk).</p>

            <p class="mono muted" style="margin:22px 0 6px">STATUS SAAT INI</p>
            <div class="stat value" style="font-size:38px">
                {{ $armed ? '🛡️ ARMED' : '⚪ DISARMED' }}
            </div>
            <p class="note">{{ $armed ? 'Alarm aktif — penyusup memicu sirene.' : 'Alarm dimatikan — radar tetap memantau, sirene tidak menyala.' }}</p>

            <div style="display:flex;gap:12px;margin-top:22px;flex-wrap:wrap">
                <form action="{{ route('radar.alarm', 'on') }}" method="POST" style="flex:1;min-width:140px">
                    @csrf
                    <button type="submit" class="btn btn-big" @if($armed) disabled style="opacity:.5" @endif>
                        🛡️ ARM (Aktifkan)
                    </button>
                </form>
                <form action="{{ route('radar.alarm', 'off') }}" method="POST" style="flex:1;min-width:140px">
                    @csrf
                    <button type="submit" class="btn-outline btn-big" @if(!$armed) disabled style="opacity:.5" @endif>
                        ⚪ DISARM (Matikan)
                    </button>
                </form>
            </div>
        </div>

        <div class="card" style="margin-top:22px">
            <h2>Data Live</h2>
            <div class="prot-grid">
                <div class="prot-box">
                    <span class="label">Jarak Objek</span>
                    <div class="pv" id="protJarak">{{ $latest && $latest->jarak !== null && $latest->jarak < 999 ? number_format($latest->jarak, 0, ',', '').' cm' : '—' }}</div>
                </div>
                <div class="prot-box">
                    <span class="label">PIR</span>
                    <div style="margin:14px 0 8px">
                        <span class="pill {{ $gerakan ? 'pill-kering' : 'pill-idle' }}" id="protPir">{{ $gerakan ? 'MENDETEKSI' : 'SIAGA' }}</span>
                    </div>
                </div>
                <div class="prot-box">
                    <span class="label">Alarm (alat)</span>
                    <div style="margin:14px 0 8px">
                        <span class="pill {{ $alarm ? 'pill-alarm' : 'pill-optimal' }}" id="protAlarm">{{ $alarm ? 'MENGHALAU' : 'AMAN' }}</span>
                    </div>
                </div>
                <div class="prot-box">
                    <span class="label">Pompa</span>
                    <div style="margin:14px 0 8px">
                        <span class="pill {{ $latest?->pompa ? 'pill-optimal' : 'pill-idle' }}" id="protPompa">{{ $latest?->pompa ? 'ON' : 'OFF' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const RADAR_INIT = @json($radarInit);

const MONO = "'JetBrains Mono', monospace";
const NSR = 'http://www.w3.org/2000/svg';
const RADAR = { jarak: RADAR_INIT.jarak, gerakan: RADAR_INIT.gerakan, alarm: RADAR_INIT.alarm, armed: RADAR_INIT.armed, MAXCM: 120, CX: 260, CY: 305, R: 245 };
const svgR = document.getElementById('radarSvg');
const ptR = (deg, r) => { const a = deg * Math.PI / 180; return [RADAR.CX + Math.sin(a) * r, RADAR.CY - Math.cos(a) * r]; };
function svgEl(n, attrs, parent) { const e = document.createElementNS(NSR, n); for (const k in attrs) e.setAttribute(k, attrs[k]); (parent || svgR).appendChild(e); return e; }
function arcD(r, a1, a2) { const [x1,y1]=ptR(a1,r),[x2,y2]=ptR(a2,r); return `M ${x1} ${y1} A ${r} ${r} 0 ${a2-a1>180?1:0} 1 ${x2} ${y2}`; }
function wedgeD(half) { const [x1,y1]=ptR(-half,RADAR.R),[x2,y2]=ptR(half,RADAR.R); return `M ${RADAR.CX} ${RADAR.CY} L ${x1} ${y1} A ${RADAR.R} ${RADAR.R} 0 0 1 ${x2} ${y2} Z`; }

let pirSector, trailLines = [], blipsG; const TRAIL = 26;

function buildRadar() {
    svgR.innerHTML = '';
    const C = RADAR.CX, CY = RADAR.CY, R = RADAR.R;
    svgEl('line', {x1:C-R-20,y1:CY,x2:C+R+20,y2:CY,stroke:'#33302a','stroke-width':2});
    for (let x=-R-14;x<=R+14;x+=22) svgEl('line',{x1:C+x,y1:CY,x2:C+x-9,y2:CY+9,stroke:'#8b8677','stroke-width':1,opacity:.55});
    svgEl('path', {d:arcD(R,-90,90),fill:'none',stroke:'#33302a','stroke-width':2});
    [30,60,90,120].forEach(cm => {
        const r = R*cm/RADAR.MAXCM;
        svgEl('path', {d:arcD(r,-90,90),fill:'none',stroke:cm===60?'#c15a2b':'#33302a','stroke-width':cm===60?1.6:1,'stroke-dasharray':cm===60?'6 5':'none',opacity:cm===60?.9:.22});
    });
    [30,60,90,120].forEach(cm => { const r=R*cm/RADAR.MAXCM,[x,y]=ptR(-45,r);
        svgEl('text',{x:x-8,y:y+4,fill:'#8b8677','font-size':10,'text-anchor':'end','font-family':MONO}).textContent=cm; });
    for (let d=-90;d<=90;d+=15){ const [x1,y1]=ptR(d,R),[x2,y2]=ptR(d,R+9);
        svgEl('line',{x1,y1,x2,y2,stroke:'#33302a','stroke-width':1,opacity:.5}); }
    [-90,-45,0,45,90].forEach(d=>{ const [x,y]=ptR(d,R+20);
        svgEl('text',{x,y:y+4,fill:'#8b8677','font-size':9.5,'text-anchor':'middle','font-family':MONO}).textContent=(d===0?'0°':(d>0?'+':'')+d+'°'); });
    pirSector = svgEl('path',{d:wedgeD(45),fill:'#c15a2b',opacity:.06});
    svgEl('path',{d:wedgeD(15),fill:'#6d7e52',opacity:.12});
    let p=ptR(-45,R*.55);
    svgEl('text',{x:p[0],y:p[1],fill:'#8b8677','font-size':9.5,'text-anchor':'middle','font-family':MONO}).textContent='PIR ±45°';
    p=ptR(14,R*.32);
    svgEl('text',{x:p[0],y:p[1],fill:'#5c6b44','font-size':9.5,'text-anchor':'middle','font-family':MONO}).textContent='HC-SR04 ±15°';
    trailLines=[];
    for (let i=0;i<TRAIL;i++) trailLines.push(svgEl('line',{x1:C,y1:CY,x2:C,y2:CY-R,stroke:'#4e7d8c','stroke-width':2.4-1.6*i/TRAIL,opacity:0}));
    blipsG=svgEl('g',{});
    svgEl('rect',{x:C-26,y:CY+8,width:52,height:26,rx:5,fill:'#6d7e52'});
    [-12,12].forEach(dx=>{ svgEl('circle',{cx:C+dx,cy:CY+19,r:5,fill:'#f1ecdf'}); svgEl('circle',{cx:C+dx,cy:CY+19,r:2.2,fill:'#33302a'}); });
    svgEl('text',{x:C,y:CY+52,fill:'#8b8677','font-size':10,'text-anchor':'middle','font-family':MONO}).textContent='HC-SR04 + PIR · menghadap depan';
}

let sweepDeg=-90, sweepDir=1, lastRaf=0;
function radarTick(ts){
    if (!lastRaf) lastRaf=ts;
    const dt=Math.min(60,ts-lastRaf); lastRaf=ts;
    sweepDeg+=sweepDir*dt*0.13;
    if (sweepDeg>=90){sweepDeg=90;sweepDir=-1;}
    if (sweepDeg<=-90){sweepDeg=-90;sweepDir=1;}
    const col = RADAR.alarm ? '#c15a2b' : (RADAR.armed ? '#4e7d8c' : '#8b8677');
    for (let i=0;i<TRAIL;i++){
        const a=Math.max(-90,Math.min(90,sweepDeg-sweepDir*i*1.4));
        const [x,y]=ptR(a,RADAR.R);
        const l=trailLines[i];
        l.setAttribute('x1',RADAR.CX); l.setAttribute('y1',RADAR.CY);
        l.setAttribute('x2',x); l.setAttribute('y2',y);
        l.setAttribute('stroke',col);
        l.setAttribute('opacity',(1-i/TRAIL)*(RADAR.alarm?.95:(RADAR.armed?.85:.5)));
    }
    const pul = RADAR.alarm ? .42 : (RADAR.gerakan ? .30+.18*Math.abs(Math.sin(ts*.006)) : .06);
    pirSector.setAttribute('opacity', RADAR.armed ? pul : .04);
    const now=performance.now();
    for (let i=blipsG.childNodes.length-1;i>=0;i--){
        const b=blipsG.childNodes[i]; const age=now-b._t;
        if (age>4000){blipsG.removeChild(b);continue;}
        b.setAttribute('opacity',String(1-age/4000));
    }
    requestAnimationFrame(radarTick);
}

let lastBlipLabel=null;
function addBlip(){
    const d=RADAR.jarak;
    if (d==null||d<2||d>RADAR.MAXCM) return;
    const ang=(Math.random()*2-1)*10;
    const [x,y]=ptR(ang,RADAR.R*d/RADAR.MAXCM);
    const c=svgEl('circle',{cx:x,cy:y,r:6,fill:RADAR.gerakan?'#c15a2b':'#4e7d8c'},blipsG);
    c._t=performance.now();
    if (lastBlipLabel) lastBlipLabel.remove();
    lastBlipLabel=svgEl('text',{x,y:y-12,fill:'#33302a','font-size':11,'text-anchor':'middle','font-weight':700,'font-family':MONO},blipsG);
    lastBlipLabel.textContent=Math.round(d)+' cm';
    lastBlipLabel._t=performance.now();
}
function setPill(id,text,cls){ const e=document.getElementById(id); e.textContent=text; e.className='pill '+cls; }

buildRadar();
requestAnimationFrame(radarTick);

async function fastRefresh(){
    try{
        const res=await fetch('{{ route('dashboard.kelembapan.latest') }}?_='+Date.now(),{cache:'no-store'});
        if (!res.ok) return;
        const d=await res.json();
        if (!d.latest) return;

        if (d.latest.jarak!==null && d.latest.jarak!==undefined){
            RADAR.jarak=d.latest.jarak;
            if (d.latest.jarak<RADAR.MAXCM) addBlip();
        }
        RADAR.gerakan=!!d.latest.gerakan;
        RADAR.alarm=!!d.latest.alarm;

        document.getElementById('radarCard').classList.toggle('alarm',RADAR.alarm);

        const pj=document.getElementById('protJarak');
        pj.innerHTML=(d.latest.jarak!==null&&d.latest.jarak<999)?Math.round(d.latest.jarak)+' cm':'—';
        setPill('protPir',d.latest.gerakan?'MENDETEKSI':'SIAGA',d.latest.gerakan?'pill-kering':'pill-idle');
        setPill('protAlarm',d.latest.alarm?'MENGHALAU':'AMAN',d.latest.alarm?'pill-alarm':'pill-optimal');
        setPill('protPompa',d.latest.pompa?'ON':'OFF',d.latest.pompa?'pill-optimal':'pill-idle');
    }catch(e){}
}
setInterval(fastRefresh,1000);
fastRefresh();
</script>
@endsection
