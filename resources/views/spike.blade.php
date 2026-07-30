<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Spike — lokalni ESIR</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, system-ui, sans-serif; margin: 0; padding: 24px; line-height: 1.5; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        p.sub { margin: 0 0 24px; opacity: .6; font-size: 14px; }
        label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; opacity: .6; margin-bottom: 6px; }
        input { width: 100%; padding: 14px; font-size: 16px; border: 1px solid rgba(128,128,128,.4); border-radius: 12px; background: transparent; color: inherit; margin-bottom: 16px; }
        button { width: 100%; padding: 16px; font-size: 16px; font-weight: 700; border: 0; border-radius: 12px; background: #2563eb; color: #fff; }
        button:disabled { opacity: .5; }
        pre { margin-top: 20px; padding: 16px; border-radius: 12px; background: rgba(128,128,128,.12); overflow-x: auto; font-size: 13px; white-space: pre-wrap; word-break: break-word; }
        .ok { border-left: 4px solid #16a34a; }
        .bad { border-left: 4px solid #dc2626; }
    </style>
</head>
<body>
    <h1>Lokalni ESIR — spike</h1>
    <p class="sub">Dokazuje da PHP na uređaju može pozvati HTTP kasu na LAN-u.</p>

    <form method="POST" action="{{ route('spike.attention') }}">
        @csrf
        <label for="base_url">Base URL kase</label>
        <input id="base_url" name="base_url" value="{{ old('base_url', $baseUrl) }}" placeholder="http://192.168.31.102:3566" autocapitalize="off" autocorrect="off" spellcheck="false">

        <label for="api_key">API ključ (opciono)</label>
        <input id="api_key" name="api_key" value="{{ old('api_key') }}" autocapitalize="off" autocorrect="off" spellcheck="false">

        <button type="submit">Pozovi /api/attention</button>
    </form>

    @isset($result)
        <pre class="{{ $result['ok'] ? 'ok' : 'bad' }}">{{ $result['text'] }}</pre>
    @endisset
</body>
</html>
