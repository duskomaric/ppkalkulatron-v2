<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'ppKalkulatron')</title>
    <style>
        :root { color-scheme: light dark; --line: rgba(128,128,128,.28); --dim: rgba(128,128,128,1); --accent: #2563eb; }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, system-ui, "Segoe UI", sans-serif; margin: 0; line-height: 1.5; }
        .wrap { max-width: 480px; margin: 0 auto; padding: 24px 20px 48px; }
        h1 { font-size: 22px; margin: 0 0 4px; letter-spacing: -.01em; }
        .sub { margin: 0 0 28px; color: var(--dim); font-size: 14px; }
        label { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .1em; color: var(--dim); margin-bottom: 6px; font-weight: 700; }
        input { width: 100%; padding: 14px; font-size: 17px; border: 1px solid var(--line); border-radius: 12px; background: transparent; color: inherit; margin-bottom: 18px; }
        input:focus { outline: none; border-color: var(--accent); }
        input.pin { text-align: center; letter-spacing: .6em; font-weight: 700; }
        button { width: 100%; padding: 15px; font-size: 16px; font-weight: 700; border: 0; border-radius: 12px; background: var(--accent); color: #fff; cursor: pointer; }
        button.ghost { background: transparent; color: inherit; border: 1px solid var(--line); }
        button.danger { background: transparent; color: #dc2626; border: 1px solid #dc262655; }
        .err { color: #dc2626; font-size: 13px; margin: -10px 0 16px; }
        .ok { padding: 12px 14px; border-radius: 12px; background: #16a34a1a; border-left: 3px solid #16a34a; font-size: 14px; margin-bottom: 24px; }
        .card { border: 1px solid var(--line); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .card h2 { font-size: 15px; margin: 0 0 4px; }
        .card p { margin: 0 0 18px; font-size: 13px; color: var(--dim); }
        a { color: var(--accent); }
        nav { display: flex; gap: 16px; font-size: 14px; margin-bottom: 28px; padding-bottom: 16px; border-bottom: 1px solid var(--line); }
        .center { min-height: 100vh; display: flex; align-items: center; }
        .center .wrap { width: 100%; }
    </style>
</head>
<body class="@yield('body-class')">
    <div class="wrap">
        @if (session('status'))
            <div class="ok">{{ session('status') }}</div>
        @endif

        @yield('content')
    </div>
</body>
</html>
