<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — Protein Point</title>
    <style>
        :root { --bg: #0f1412; --panel: #1a2420; --accent: #7dcea0; --text: #e8f0eb; --muted: #9bb5a8; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", system-ui, sans-serif; background: var(--bg); color: var(--text); }
        .shell { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
        aside { background: var(--panel); padding: 1.5rem 1rem; border-right: 1px solid #24332c; }
        aside .brand { font-weight: 700; color: var(--accent); margin-bottom: 1.5rem; }
        aside a { display: block; color: var(--muted); text-decoration: none; padding: 0.5rem 0.75rem; border-radius: 6px; margin-bottom: 0.25rem; }
        aside a:hover, aside a.active { background: #24332c; color: var(--text); }
        main { padding: 2rem; }
        h1 { margin: 0 0 0.5rem; font-size: 1.75rem; }
        .muted { color: var(--muted); }
    </style>
</head>
<body>
<div class="shell">
    <aside>
        <div class="brand">Protein Point · Admin</div>
        <a href="{{ route('admin.dashboard') }}" class="active">Dashboard</a>
    </aside>
    <main>
        @yield('content')
    </main>
</div>
</body>
</html>
