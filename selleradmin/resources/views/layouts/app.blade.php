<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Seller Admin') — Protein Point</title>
    <style>
        :root { --bg: #121418; --panel: #1c222b; --accent: #6eb5ff; --text: #e8edf5; --muted: #9aa7b8; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", system-ui, sans-serif; background: var(--bg); color: var(--text); }
        .shell { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
        aside { background: var(--panel); padding: 1.5rem 1rem; border-right: 1px solid #2a3340; }
        aside .brand { font-weight: 700; color: var(--accent); margin-bottom: 1.5rem; }
        aside a { display: block; color: var(--muted); text-decoration: none; padding: 0.5rem 0.75rem; border-radius: 6px; margin-bottom: 0.25rem; }
        aside a:hover, aside a.active { background: #2a3340; color: var(--text); }
        main { padding: 2rem; }
        h1 { margin: 0 0 0.5rem; font-size: 1.75rem; }
        .muted { color: var(--muted); }
    </style>
</head>
<body>
<div class="shell">
    <aside>
        <div class="brand">Protein Point · Seller</div>
        <a href="{{ route('selleradmin.dashboard') }}" class="active">Dashboard</a>
    </aside>
    <main>
        @yield('content')
    </main>
</div>
</body>
</html>
