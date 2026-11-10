<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0b1e3a;
            --blue: #1f6feb;
            --blue-dark: #1558c7;
            --ink: #14213d;
            --muted: #6b7a90;
            --line: #dfe5ee;
            --danger: #d64545;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: "Plus Jakarta Sans", "Segoe UI", system-ui, -apple-system, sans-serif;
            color: var(--ink);
            /*
             * Lapisan: gelap transparan -> foto (public/images/login-bg.jpg) -> ilustrasi cadangan (svg).
             * Bila login-bg.jpg belum ada, ilustrasi SVG yang tampil.
             */
            background:
                linear-gradient(120deg, rgba(8, 22, 46, .88) 0%, rgba(11, 30, 58, .62) 45%, rgba(11, 30, 58, .35) 100%),
                url("{{ asset('images/login-bg.jpg') }}") center / cover no-repeat,
                url("{{ asset('images/login-bg.svg') }}") center / cover no-repeat,
                var(--navy);
            background-attachment: fixed;
        }
        .page {
            min-height: 100%;
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            align-items: center;
            gap: 48px;
            max-width: 1180px;
            margin: 0 auto;
            padding: 40px 32px;
        }

        /* ---------- Sisi kiri: brand ---------- */
        .brand { color: #fff; animation: rise .7s ease-out both; }
        .brand-mark {
            display: inline-flex; align-items: center; gap: 12px;
            font-weight: 800; font-size: 15px; letter-spacing: .18em;
            padding: 8px 14px 8px 8px; border-radius: 999px;
            background: rgba(255, 255, 255, .1); border: 1px solid rgba(255, 255, 255, .18);
            backdrop-filter: blur(6px);
        }
        .brand-mark span.logo {
            width: 32px; height: 32px; border-radius: 50%;
            display: grid; place-items: center;
            background: linear-gradient(135deg, #4c9bff, var(--blue));
        }
        .brand h1 {
            font-size: clamp(32px, 4.4vw, 54px);
            line-height: 1.08; margin: 28px 0 16px; font-weight: 800; letter-spacing: -.02em;
        }
        .brand h1 em { font-style: normal; color: #8fc1ff; }
        .brand p { font-size: 17px; line-height: 1.6; max-width: 460px; color: rgba(255, 255, 255, .78); margin: 0; }
        .stats { display: flex; gap: 28px; margin-top: 36px; }
        .stats div { border-left: 2px solid rgba(143, 193, 255, .6); padding-left: 14px; }
        .stats b { display: block; font-size: 15px; color: #fff; }
        .stats small { font-size: 13px; color: rgba(255, 255, 255, .65); }

        /* ---------- Sisi kanan: kartu login ---------- */
        .card {
            background: rgba(255, 255, 255, .97);
            border-radius: 20px;
            padding: 40px 36px 32px;
            box-shadow: 0 30px 80px rgba(3, 12, 28, .45);
            width: 100%; max-width: 420px; justify-self: end;
            animation: rise .7s .12s ease-out both;
        }
        .card h2 { margin: 0 0 6px; font-size: 24px; font-weight: 700; }
        .card .lead { margin: 0 0 28px; color: var(--muted); font-size: 14px; }

        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .field { position: relative; margin-bottom: 18px; }
        .field svg.icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: #9aa6b8; pointer-events: none;
        }
        .field input {
            width: 100%; height: 48px; padding: 0 44px;
            font: inherit; font-size: 15px; color: var(--ink);
            border: 1.5px solid var(--line); border-radius: 12px; background: #f8fafc;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .field input:focus {
            outline: none; border-color: var(--blue); background: #fff;
            box-shadow: 0 0 0 4px rgba(31, 111, 235, .14);
        }
        .field.has-error input { border-color: var(--danger); }
        .toggle-pass {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            width: 34px; height: 34px; border: 0; border-radius: 8px;
            background: transparent; color: #9aa6b8; cursor: pointer; display: grid; place-items: center;
        }
        .toggle-pass:hover { color: var(--ink); background: #eef2f7; }
        .toggle-pass svg { width: 18px; height: 18px; }

        .alert {
            display: flex; gap: 10px; align-items: flex-start;
            background: #fdecec; color: #9b2c2c; border: 1px solid #f6caca;
            padding: 12px 14px; border-radius: 12px; font-size: 14px; margin-bottom: 20px;
        }
        .alert svg { flex: none; width: 18px; height: 18px; margin-top: 1px; }

        .btn {
            width: 100%; height: 50px; margin-top: 8px;
            border: 0; border-radius: 12px; cursor: pointer;
            font: inherit; font-size: 15px; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, #3b8bff, var(--blue) 55%, var(--blue-dark));
            box-shadow: 0 10px 24px rgba(31, 111, 235, .35);
            transition: transform .12s, box-shadow .12s, filter .12s;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn:hover { filter: brightness(1.05); box-shadow: 0 14px 30px rgba(31, 111, 235, .42); }
        .btn:active { transform: translateY(1px); }
        .btn[disabled] { opacity: .75; cursor: progress; }
        .spinner {
            width: 16px; height: 16px; border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, .45); border-top-color: #fff;
            animation: spin .7s linear infinite; display: none;
        }
        .btn.loading .spinner { display: inline-block; }

        .foot { margin-top: 26px; text-align: center; font-size: 12px; color: var(--muted); }

        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ---------- Tablet & HP ---------- */
        @media (max-width: 900px) {
            .page { grid-template-columns: 1fr; gap: 28px; padding: 32px 16px; align-content: center; }
            .brand { text-align: center; }
            .brand p { margin: 0 auto; font-size: 15px; }
            .brand h1 { margin: 20px 0 10px; }
            .stats { display: none; }
            .card { justify-self: center; padding: 32px 22px 26px; }
        }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; } }
    </style>
</head>
<body>
<main class="page">
    <section class="brand">
        <div class="brand-mark">
            <span class="logo">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="m3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
            </span>
            RISE
        </div>
        <h1>Inventory<br><em>Asset Database</em></h1>
        <p>Catat, lacak, dan rawat seluruh aset RISE dalam satu tempat, dari pembelian, peminjaman, hingga jadwal perawatan.</p>
        <div class="stats">
            <div><b>Aset</b><small>Data &amp; lokasi</small></div>
            <div><b>Transaksi</b><small>Peminjaman staff</small></div>
            <div><b>Maintenance</b><small>Jadwal servis</small></div>
        </div>
    </section>

    <section class="card" aria-labelledby="login-title">
        <h2 id="login-title">Selamat datang</h2>
        <p class="lead">Masuk dengan akun Anda untuk melanjutkan.</p>

        @if ($errors->any())
            <div class="alert" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" id="login-form" novalidate>
            @csrf

            <label for="email">Email</label>
            <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                <input type="text" id="email" name="email" value="{{ old('email') }}"
                       placeholder="nama@email.com" autocomplete="username" autofocus required>
            </div>

            <label for="password">Password</label>
            <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" id="password" name="password" placeholder="••••••••"
                       autocomplete="current-password" required>
                <button type="button" class="toggle-pass" aria-label="Tampilkan password" data-toggle-pass>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>

            <button type="submit" class="btn" id="login-btn">
                <span class="spinner" aria-hidden="true"></span>
                <span>Masuk</span>
            </button>
        </form>

        <p class="foot">&copy; {{ date('Y') }} RISE &middot; Inventory Asset Database</p>
    </section>
</main>

<script>
    document.querySelector('[data-toggle-pass]').addEventListener('click', function () {
        var input = document.getElementById('password');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    });
    document.getElementById('login-form').addEventListener('submit', function () {
        var btn = document.getElementById('login-btn');
        btn.classList.add('loading');
        btn.disabled = true;
    });
</script>
</body>
</html>
