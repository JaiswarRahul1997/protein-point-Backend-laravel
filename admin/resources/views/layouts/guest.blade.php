<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — Protein Point</title>
    <style>
        :root {
            --white: #ffffff;
            --black: #000000;
            --border: #e5e5e5;
            --surface: #fafafa;
            --space-2: 0.75rem;
            --space-3: 1rem;
            --space-4: 1.5rem;
            --space-5: 2rem;
            --radius: 8px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: var(--space-4);
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: var(--white);
            color: var(--black);
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: var(--space-5);
            background: var(--white);
        }

        .brand {
            margin: 0 0 var(--space-2);
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--black);
        }

        .auth-title {
            margin: 0 0 var(--space-2);
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--black);
        }

        .auth-lead {
            margin: 0 0 var(--space-4);
            font-size: 0.95rem;
            color: var(--black);
        }

        .form-field {
            margin-bottom: var(--space-3);
        }

        .form-label {
            display: block;
            margin-bottom: 0.375rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--black);
        }

        .form-input {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font: inherit;
            color: var(--black);
            background: var(--white);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--black);
        }

        .form-error {
            margin: 0.375rem 0 0;
            font-size: 0.85rem;
            color: var(--black);
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--black);
            border-radius: var(--radius);
            background: var(--black);
            color: var(--white);
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }

        .btn:hover {
            background: var(--white);
            color: var(--black);
        }

        .auth-footer {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--border);
            font-size: 0.9rem;
            text-align: center;
            color: var(--black);
        }

        .auth-footer a {
            color: var(--black);
            font-weight: 600;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <p class="brand">Protein Point · Admin</p>
        @yield('content')
    </div>
</body>
</html>
