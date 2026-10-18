<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prot-Hydro — Monitoring Kelembapan Tanah</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        :root{
            --bg:#f1ecdf; --card:#fbf9f2; --line:#e5dec9; --ink:#33302a;
            --muted:#8b8677; --green:#6d7e52; --green-dark:#5c6b44;
            --orange:#c15a2b; --blue:#4e7d8c;
            --mono:'JetBrains Mono',monospace; --serif:'Fraunces',serif;
        }
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--ink);font-family:var(--serif)}
        a{text-decoration:none;color:inherit}
        .container{max-width:1400px;margin:0 auto;padding:0 28px}
        .topbar{display:flex;align-items:center;justify-content:space-between;gap:20px;
                padding:18px 0;border-bottom:1px solid var(--line)}
        .brand{display:flex;align-items:center;gap:14px}
        .logo{width:52px;height:52px;border-radius:14px;background:var(--green-dark);
              display:grid;place-items:center;color:#f6f3e8;flex:none}
        .brand h1{font-size:26px;margin:0;font-weight:700}
        .brand p{margin:3px 0 0;font-family:var(--mono);font-size:10.5px;letter-spacing:.15em;
                 color:var(--muted);text-transform:uppercase}
        .nav{display:flex;gap:26px;font-family:var(--mono);font-size:13.5px;align-items:center}
        .nav a{color:var(--muted);padding:8px 2px;border-bottom:2px solid transparent}
        .nav a.active{color:var(--ink);font-weight:700;border-color:var(--orange)}
        .logout-btn{font-family:var(--mono);font-size:11.5px;color:var(--orange);background:none;
                    border:1.5px solid var(--orange);border-radius:9px;padding:7px 12px;cursor:pointer}
        .logout-btn:hover{background:#f6e6da}
        .top-right{display:flex;align-items:center;gap:14px}
        .device-badge{display:flex;align-items:center;gap:8px;font-family:var(--mono);
                      font-size:11.5px;font-weight:700;letter-spacing:.08em;background:#e2e5d1;
                      color:var(--green-dark);padding:10px 16px;border-radius:999px;white-space:nowrap}
        .device-badge .dot{width:8px;height:8px;border-radius:50%;background:var(--green)}
        .device-badge.offline{background:#f0e2d6;color:var(--orange)}
        .device-badge.offline .dot{background:var(--orange)}
        .avatar{width:40px;height:40px;border-radius:50%;background:var(--green-dark);
                color:#f6f3e8;display:grid;place-items:center;font-family:var(--mono);
                font-size:13px;font-weight:700;flex:none}
        main{padding:26px 0 8px}
        .card{background:var(--card);border:1px solid var(--line);border-radius:18px;
              padding:22px 24px;box-shadow:0 1px 2px rgba(60,50,30,.05)}
        .card h2{font-size:20px;font-weight:600;margin:0}
        .grid-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:22px}
        .grid-main{display:grid;grid-template-columns:1.9fr 1fr;gap:22px;margin-top:22px}
        .grid-bottom{display:grid;grid-template-columns:1fr 1.15fr;gap:22px;margin-top:22px}
        .stat .label{font-family:var(--mono);font-size:10.5px;letter-spacing:.16em;
                     text-transform:uppercase;color:var(--muted)}
        .stat .value{font-size:46px;font-weight:600;line-height:1.15;margin:10px 0 6px}
        .stat .note{font-family:var(--mono);font-size:12px;margin:0;color:var(--muted)}
        .note.down{color:var(--orange)} .note.up{color:var(--green-dark)}
        .pill{display:inline-block;font-family:var(--mono);font-size:11px;font-weight:700;
              letter-spacing:.1em;padding:7px 16px;border-radius:999px;color:#fff}
        .pill-sm{font-size:10px;padding:5px 12px}
        .pill-optimal{background:var(--green)} .pill-kering{background:var(--orange)}
        .pill-basah{background:var(--blue)} .pill-idle{background:#b3ac97}
        .pill-alarm{background:var(--orange);animation:blinkA .9s infinite}
        @keyframes blinkA{50%{opacity:.45}}
        .card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        .legend{display:flex;gap:20px;font-family:var(--mono);font-size:12px;color:#5c584c;flex-wrap:wrap}
        .legend i{display:inline-block;vertical-align:middle;margin-right:8px}
        .lg-line{width:22px;height:0;border-top:3px solid var(--green);border-radius:2px}
        .lg-band{width:14px;height:14px;background:rgba(109,126,82,.28);border-radius:3px}
        .lg-dash{width:22px;height:0;border-top:2px dashed var(--orange)}
        .chart-wrap{position:relative;height:400px;margin-top:16px}
        .chart-wrap-sm{position:relative;height:320px;margin-top:24px}
        .gauge-card{text-align:center}
        .gauge-card h2{text-align:left}
        #gauge svg{width:100%;max-width:370px}
        .gauge-tick{font-family:var(--mono);font-size:12px;fill:var(--muted)}
        .gauge-value{font-family:var(--serif);font-size:40px;font-weight:700;fill:var(--ink)}
        .gauge-badge{margin-top:4px}
        .chips{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin-top:16px;
               border-top:1px solid var(--line);padding-top:16px}
        .chip{font-family:var(--mono);font-size:11.5px;color:#5c584c;border:1px solid var(--line);
              border-radius:999px;padding:7px 14px;background:rgba(255,255,255,.6)}
        table{width:100%;border-collapse:collapse;font-family:var(--mono);font-size:12.5px;margin-top:14px}
        thead th{text-align:left;font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;
                 color:var(--muted);font-weight:600;padding:0 8px 12px;border-bottom:1px solid var(--line)}
        tbody td{padding:13px 8px;border-bottom:1px solid #ece6d4}
        tbody tr:nth-child(odd){background:#f4efe1}
        .value-strong{font-weight:700;font-size:13.5px}
        footer{border-top:1px solid var(--line);margin-top:26px;padding:16px 0 22px;
               display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;
               font-family:var(--mono);font-size:12px;color:var(--muted)}
        .filter-bar{display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap}
        .filter-field{display:flex;flex-direction:column;gap:6px}
        .filter-field label{font-family:var(--mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
        select,input[type=number],input[type=text],input[type=email],input[type=password]{
            font-family:var(--mono);font-size:13px;color:var(--ink);
            padding:10px 14px;border:1px solid var(--line);border-radius:10px;background:#fff;min-width:150px}
        select:focus,input:focus{outline:2px solid var(--green);outline-offset:1px;border-color:var(--green)}
        .btn{font-family:var(--mono);font-size:12.5px;font-weight:700;letter-spacing:.06em;color:#fff;
            background:var(--green-dark);border:none;border-radius:10px;padding:11px 20px;cursor:pointer}
        .btn:hover{background:#4d5a3a}
        .btn-outline{display:inline-block;font-family:var(--mono);font-size:12.5px;font-weight:600;
            color:var(--green-dark);background:transparent;border:1.5px solid var(--green-dark);
            border-radius:10px;padding:10px 18px;cursor:pointer}
        .btn-outline:hover{background:#e7e9d8}
        .btn-sm{padding:7px 12px;font-size:11.5px}
        .btn-danger{color:var(--orange);border-color:var(--orange)}
        .btn-danger:hover{background:#f6e6da}
        .filter-note{margin-left:auto;font-family:var(--mono);font-size:12px;color:var(--muted)}
        .flash{margin-top:22px;padding:14px 18px;border-radius:12px;background:#e2e5d1;color:var(--green-dark);
            font-family:var(--mono);font-size:13px;border:1px solid #cdd3b4}
        .pager{display:flex;gap:6px;justify-content:center;margin-top:20px;flex-wrap:wrap}
        .pager .pager-btn{font-family:var(--mono);font-size:12.5px;min-width:36px;padding:8px 12px;text-align:center;
            border:1px solid var(--line);border-radius:9px;color:var(--ink);background:#fff}
        .pager .is-current{background:var(--green-dark);color:#fff;border-color:var(--green-dark);font-weight:700}
        .pager .is-disabled{opacity:.4;pointer-events:none}
        .device-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px}
        .muted{color:var(--muted)}
        .mono{font-family:var(--mono);font-size:11px;letter-spacing:.08em}
        .device-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-top:18px;
            padding-top:16px;border-top:1px solid var(--line)}
        .device-stats .label{display:block;font-family:var(--mono);font-size:10px;letter-spacing:.14em;
            text-transform:uppercase;color:var(--muted);margin-bottom:4px}
        .device-stats strong{font-family:var(--mono);font-size:13.5px;font-weight:600}
        .key-row{display:flex;align-items:center;gap:10px;margin-top:18px;flex-wrap:wrap}
        .key-row .label{font-family:var(--mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
        .key-box{font-family:var(--mono);font-size:12px;background:#efe9d8;border:1px dashed var(--line);
            border-radius:8px;padding:8px 12px;word-break:break-all}
        .api-sample{font-family:var(--mono);font-size:12px;background:#33302a;color:#e8e4d5;border-radius:12px;
            padding:18px;overflow-x:auto;white-space:pre;line-height:1.7}
        .form-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:6px}
        .zone-preview{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:20px}
        .zone{border-radius:12px;padding:14px;text-align:center;color:#fff}
        .zone h4{margin:0 0 6px;font-family:var(--mono);font-size:10.5px;letter-spacing:.12em}
        .zone p{margin:0;font-family:var(--mono);font-size:13px;font-weight:700}
        .zone-dry{background:var(--orange)} .zone-opt{background:var(--green)} .zone-wet{background:var(--blue)}
        .note-list{margin:14px 0 0;padding-left:18px;font-family:var(--mono);font-size:12.5px;line-height:2;color:#5c584c}

        /* ===== NAV BURGER + DRAWER ===== */
        .nav-toggle{display:none;flex-direction:column;justify-content:center;gap:5px;
                    width:44px;height:44px;background:var(--card);border:1px solid var(--line);
                    border-radius:12px;cursor:pointer;padding:0 10px;flex:none}
        .nav-toggle span{display:block;width:100%;height:2.5px;background:var(--ink);border-radius:2px;transition:.25s}
        .nav-toggle.active span:nth-child(1){transform:translateY(7.5px) rotate(45deg)}
        .nav-toggle.active span:nth-child(2){opacity:0}
        .nav-toggle.active span:nth-child(3){transform:translateY(-7.5px) rotate(-45deg)}
        .drawer{position:fixed;top:0;left:0;bottom:0;width:280px;max-width:85vw;
                background:var(--card);border-right:1px solid var(--line);
                transform:translateX(-105%);transition:transform .3s ease;z-index:120;
                padding:24px 18px;display:flex;flex-direction:column;gap:6px}
        .drawer.open{transform:translateX(0)}
        .drawer-head{display:flex;align-items:center;gap:12px;padding:4px 8px 18px;
                     border-bottom:1px solid var(--line);margin-bottom:14px}
        .drawer-head .logo{width:42px;height:42px;border-radius:12px}
        .drawer-head b{font-size:18px}
        .drawer-head small{display:block;font-family:var(--mono);font-size:9px;letter-spacing:.12em;
                           color:var(--muted);text-transform:uppercase;margin-top:2px}
        .drawer a{font-family:var(--mono);font-size:14px;color:var(--ink);padding:13px 14px;border-radius:12px}
        .drawer a:hover{background:#f0ead9}
        .drawer a.active{background:#e7e9d8;color:var(--green-dark);font-weight:700}
        .drawer-logout{font-family:var(--mono);font-size:13px;color:var(--orange);background:none;
                       border:1.5px solid var(--orange);border-radius:12px;padding:12px 14px;
                       cursor:pointer;text-align:left;margin-top:6px}
        .drawer-logout:hover{background:#f6e6da}
        .drawer .drawer-foot{margin-top:auto;font-family:var(--mono);font-size:10px;color:var(--muted);padding:8px}
        .overlay{position:fixed;inset:0;background:rgba(20,18,12,.45);opacity:0;
                 pointer-events:none;transition:opacity .3s;z-index:110}
        .overlay.show{opacity:1;pointer-events:auto}
        body.lock{overflow:hidden}

        /* ===== RESPONSIVE ===== */
        @media (max-width:1100px){
            .nav{display:none}
            .nav-toggle{display:flex}
            .grid-stats{grid-template-columns:repeat(2,1fr)}
            .grid-main,.grid-bottom{grid-template-columns:1fr}
            .grid-main[style]{grid-template-columns:1fr !important}
            .device-grid{grid-template-columns:1fr}
            .form-grid{grid-template-columns:1fr}
            .zone-preview{grid-template-columns:1fr}
            .brand h1{font-size:20px}
            .brand p{font-size:9px}
            .logo{width:44px;height:44px;border-radius:12px}
            .device-badge{padding:8px 12px;font-size:10px}
            .avatar{width:36px;height:36px;font-size:12px}
            .stat .value{font-size:38px}
            .container{padding:0 18px}
        }
        @media (max-width:560px){
            .grid-stats{grid-template-columns:1fr}
            .chart-wrap{height:300px}
            .chart-wrap-sm{height:240px}
            table{display:block;overflow-x:auto;white-space:nowrap}
            .stat .value{font-size:34px}
            .filter-note{margin-left:0}
        }
    </style>
</head>
<body>
<div class="container">
    <header class="topbar">
        <div class="brand">
            <span class="logo">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2.7c3.6 4.2 6.5 7.8 6.5 11.2a6.5 6.5 0 1 1-13 0C5.5 10.5 8.4 6.9 12 2.7z"/>
                </svg>
            </span>
            <div>
                <h1>Prot-Hydro</h1>
                <p>Sistem Monitoring · IoT</p>
            </div>
        </div>

        <nav class="nav">
            <a href="{{ route('dashboard.kelembapan') }}" class="{{ request()->routeIs('dashboard.*') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('pompa') }}" class="{{ request()->routeIs('pompa') ? 'active' : '' }}">Pompa</a>
            <a href="{{ route('riwayat') }}" class="{{ request()->routeIs('riwayat') ? 'active' : '' }}">Riwayat</a>
            <a href="{{ route('perangkat') }}" class="{{ request()->routeIs('perangkat') ? 'active' : '' }}">Perangkat</a>
            @auth
            <a href="{{ route('radar') }}" class="{{ request()->routeIs('radar') ? 'active' : '' }}">Radar</a>
            @endauth
            <a href="{{ route('pengaturan') }}" class="{{ request()->routeIs('pengaturan') ? 'active' : '' }}">Pengaturan</a>
            @if (auth()->check())
                <form action="{{ route('logout') }}" method="POST" style="margin:0">
                    @csrf
                    <button type="submit" class="logout-btn">Keluar ({{ auth()->user()->name }})</button>
                </form>
            @else
                <a href="{{ route('login') }}">Login Admin</a>
            @endif
        </nav>

        <div class="top-right">
            <button class="nav-toggle" id="navToggle" aria-label="Buka menu">
                <span></span><span></span><span></span>
            </button>
            @php $headerDevice = $headerDevice ?? ($device ?? null); @endphp
            <span class="device-badge {{ $headerDevice?->isOnline() ? '' : 'offline' }}" id="deviceBadge">
                <span class="dot"></span>
                <span class="badge-text">ESP32 {{ $headerDevice?->isOnline() ? 'ONLINE' : 'OFFLINE' }}</span>
            </span>
            <span class="avatar">IOT</span>
        </div>
    </header>

    <div class="overlay" id="drawerOverlay"></div>
    <nav class="drawer" id="drawer">
        <div class="drawer-head">
            <span class="logo">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2.7c3.6 4.2 6.5 7.8 6.5 11.2a6.5 6.5 0 1 1-13 0C5.5 10.5 8.4 6.9 12 2.7z"/>
                </svg>
            </span>
            <div>
                <b>Prot-Hydro</b>
                <small>Monitoring · IoT</small>
            </div>
        </div>
        <a href="{{ route('dashboard.kelembapan') }}" class="{{ request()->routeIs('dashboard.*') ? 'active' : '' }}">📊 Dashboard</a>
        <a href="{{ route('pompa') }}" class="{{ request()->routeIs('pompa') ? 'active' : '' }}">💧 Kontrol Pompa</a>
        <a href="{{ route('riwayat') }}" class="{{ request()->routeIs('riwayat') ? 'active' : '' }}">🗂 Riwayat</a>
        <a href="{{ route('perangkat') }}" class="{{ request()->routeIs('perangkat') ? 'active' : '' }}">📡 Perangkat</a>
        @auth
        <a href="{{ route('radar') }}" class="{{ request()->routeIs('radar') ? 'active' : '' }}">🛡️ Radar Proteksi</a>
        @endauth
        <a href="{{ route('pengaturan') }}" class="{{ request()->routeIs('pengaturan') ? 'active' : '' }}">⚙️ Pengaturan</a>
        @if (auth()->check())
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="drawer-logout">🚪 Keluar ({{ auth()->user()->name }})</button>
            </form>
        @else
            <a href="{{ route('login') }}">🔑 Login Admin</a>
        @endif
        <div class="drawer-foot">PROT-HIDRO · Prot-Hydro<br>knowos.my.id</div>
    </nav>

    <main>
        @if (session('success'))
            <div class="flash">✓ {{ session('success') }}</div>
        @endif
        @yield('content')
    </main>

    <footer>
        <span>Dashboard Web IoT — Prot-Hydro · ESP32 → Cloud Server → Website</span>
        <span id="syncTime">Sinkronisasi: {{ now()->format('H:i:s') }} WIB</span>
    </footer>
</div>

<script>
const navToggle  = document.getElementById('navToggle');
const drawer     = document.getElementById('drawer');
const drawerOverlay = document.getElementById('drawerOverlay');

function setDrawer(open){
    drawer.classList.toggle('open', open);
    drawerOverlay.classList.toggle('show', open);
    navToggle.classList.toggle('active', open);
    document.body.classList.toggle('lock', open);
}
navToggle.addEventListener('click', () => setDrawer(!drawer.classList.contains('open')));
drawerOverlay.addEventListener('click', () => setDrawer(false));
drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', () => setDrawer(false)));
window.addEventListener('resize', () => { if (window.innerWidth > 1100) setDrawer(false); });
</script>
    @stack('scripts')
</body>
</html>
