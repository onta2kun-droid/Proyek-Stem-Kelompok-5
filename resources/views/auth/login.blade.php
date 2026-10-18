<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — Prot-Hydro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#f1ecdf; --card:#fbf9f2; --line:#e5dec9; --ink:#33302a;
            --muted:#8b8677; --green-dark:#5c6b44; --orange:#c15a2b;
            --mono:'JetBrains Mono',monospace; --serif:'Fraunces',serif;
        }
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
             background:var(--bg);font-family:var(--serif);color:var(--ink);padding:20px}
        .box{width:100%;max-width:400px;background:var(--card);border:1px solid var(--line);
             border-radius:18px;padding:34px 30px;box-shadow:0 10px 40px rgba(60,50,30,.12)}
        .logo{width:56px;height:56px;border-radius:14px;background:var(--green-dark);
              display:grid;place-items:center;color:#f6f3e8;margin:0 auto 14px}
        h1{font-size:22px;text-align:center;margin:0 0 4px}
        p.sub{font-family:var(--mono);font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;
              color:var(--muted);text-align:center;margin:0 0 26px}
        label{display:block;font-family:var(--mono);font-size:10px;letter-spacing:.14em;
              text-transform:uppercase;color:var(--muted);margin:14px 0 6px}
        input{width:100%;font-family:var(--mono);font-size:14px;padding:12px 14px;
              border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--ink)}
        input:focus{outline:2px solid var(--green-dark);border-color:var(--green-dark)}
        button{width:100%;margin-top:22px;font-family:var(--mono);font-size:13px;font-weight:700;
               letter-spacing:.06em;color:#fff;background:var(--green-dark);border:none;
               border-radius:10px;padding:13px;cursor:pointer}
        button:hover{background:#4d5a3a}
        .err{background:#f6e6da;color:var(--orange);font-family:var(--mono);font-size:12px;
             border:1px solid #eccdb5;border-radius:10px;padding:11px 14px;margin-bottom:6px}
        .back{display:block;text-align:center;margin-top:18px;font-family:var(--mono);
              font-size:11.5px;color:var(--muted)}
        .back:hover{color:var(--green-dark)}
    </style>
</head>
<body>
<div class="box">
    <span class="logo">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2.7c3.6 4.2 6.5 7.8 6.5 11.2a6.5 6.5 0 1 1-13 0C5.5 10.5 8.4 6.9 12 2.7z"/>
        </svg>
    </span>
    <h1>Login Admin</h1>
    <p class="sub">PROT-HIDRO · GACOR</p>

    @if ($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Masuk →</button>
    </form>

    <a class="back" href="{{ route('dashboard.kelembapan') }}">← Kembali ke Dashboard publik</a>
</div>
</body>
</html>
