<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'FixFlow') }} — {{ $title ?? '' }}</title>
    <style>
        :root { --brand:#4f46e5; --ink:#1f2937; --muted:#6b7280; --bg:#f9fafb; --line:#e5e7eb; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:'Segoe UI',system-ui,-apple-system,sans-serif; background:var(--bg); color:var(--ink); }
        .topbar { background:#111827; color:#fff; padding:14px 24px; display:flex; align-items:center; justify-content:space-between; }
        .topbar .brand { font-weight:700; font-size:18px; letter-spacing:.4px; }
        .topbar a { color:#c7d2fe; text-decoration:none; margin-left:18px; font-size:14px; }
        .container { max-width:960px; margin:0 auto; padding:32px 24px 64px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:12px; padding:28px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
        h1 { font-size:26px; margin:0 0 8px; }
        .lead { color:var(--muted); margin:0 0 24px; font-size:16px; }
        label { display:block; font-weight:600; font-size:13px; margin:14px 0 6px; }
        input, select, textarea { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:8px; font-size:15px; font-family:inherit; }
        textarea { resize:vertical; min-height:120px; }
        .btn { display:inline-block; background:var(--brand); color:#fff; border:none; padding:12px 22px; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; }
        .btn:hover { background:#4338ca; }
        .alert { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; border-radius:8px; padding:14px 16px; margin-bottom:20px; }
        .err { color:#b91c1c; font-size:13px; margin-top:4px; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:0 20px; }
        .ref { font-family:ui-monospace,monospace; font-size:15px; letter-spacing:1px; background:#eef2ff; border:1px dashed #6366f1; border-radius:8px; padding:14px 16px; display:inline-block; }
        .pill { display:inline-block; padding:4px 12px; border-radius:999px; font-size:13px; font-weight:600; }
        .steps { display:flex; gap:12px; flex-wrap:wrap; margin:20px 0; }
        .step { flex:1; min-width:150px; background:#f3f4f6; border-radius:10px; padding:14px; font-size:14px; }
        .step .dot { font-weight:800; color:var(--brand); }
        .center { text-align:center; }
        .mt { margin-top:20px; }
        .muted { color:var(--muted); }
    </style>
</head>
<body>
    <div class="topbar">
        <span class="brand">🔧 FixFlow</span>
        <nav>
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('portal.create') }}">Report an issue</a>
        </nav>
    </div>
    <main class="container">
        {{ $slot ?? '' }}
    </main>
</body>
</html>
