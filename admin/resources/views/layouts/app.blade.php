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
            --sidebar-width: 260px;
            --space-1: 0.5rem;
            --space-2: 0.75rem;
            --space-3: 1rem;
            --space-4: 1.5rem;
            --space-5: 2rem;
            --radius: 8px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: var(--white);
            color: var(--black);
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }

        .shell {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            min-height: 100vh;
        }

        aside {
            background: var(--white);
            padding: var(--space-5) var(--space-3);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }

        .brand {
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: -0.01em;
            color: var(--black);
            margin-bottom: var(--space-5);
            padding: 0 var(--space-2);
        }

        .nav-label {
            margin: var(--space-4) 0 var(--space-2);
            padding: 0 var(--space-2);
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--black);
        }

        .nav-label:first-of-type {
            margin-top: 0;
        }

        .nav-link {
            display: block;
            color: var(--black);
            text-decoration: none;
            padding: 0.625rem var(--space-2);
            border-radius: var(--radius);
            margin-bottom: 0.2rem;
            font-size: 0.95rem;
            transition: background-color 0.15s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--surface);
        }

        .nav-group {
            margin-bottom: var(--space-2);
        }

        .nav-group-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 0.625rem var(--space-2);
            border: 0;
            border-radius: var(--radius);
            background: transparent;
            font: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--black);
            text-align: left;
            cursor: pointer;
        }

        .nav-group-title:hover {
            background: var(--surface);
        }

        .nav-group-title .nav-chevron {
            display: inline-block;
            width: 0.45rem;
            height: 0.45rem;
            border-right: 2px solid var(--black);
            border-bottom: 2px solid var(--black);
            transform: rotate(-45deg);
            transition: transform 0.15s ease;
        }

        .nav-group.is-open .nav-group-title .nav-chevron {
            transform: rotate(45deg);
        }

        .nav-sub {
            display: none;
            margin: 0;
            padding: 0 0 0 var(--space-3);
            list-style: none;
        }

        .nav-group.is-open .nav-sub {
            display: block;
        }

        .nav-sub .nav-link {
            font-size: 0.875rem;
            font-weight: 400;
        }

        main {
            padding: var(--space-5);
            background: var(--white);
            overflow-x: auto;
        }

        .content {
            max-width: 1400px;
        }

        .page-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: var(--space-3);
            margin-bottom: var(--space-5);
            padding-bottom: var(--space-4);
            border-bottom: 1px solid var(--border);
        }

        .page-title {
            margin: 0 0 var(--space-1);
            font-size: 1.875rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--black);
        }

        .page-lead {
            margin: 0;
            font-size: 0.95rem;
            color: var(--black);
        }

        .section {
            margin-bottom: var(--space-5);
        }

        .section-title {
            margin: 0 0 var(--space-3);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--black);
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: var(--space-3);
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: var(--space-4);
        }

        .stat-label {
            display: block;
            margin-bottom: var(--space-2);
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--black);
        }

        .stat-value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.03em;
            color: var(--black);
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: var(--space-4);
            border-top: 1px solid var(--border);
        }

        .user-name {
            margin: 0 0 var(--space-3);
            padding: 0 var(--space-2);
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--black);
        }

        .logout-form {
            margin: 0;
            padding: 0 var(--space-2);
        }

        .logout-btn {
            width: 100%;
            padding: 0.625rem var(--space-2);
            border: 1px solid var(--black);
            border-radius: var(--radius);
            background: var(--white);
            color: var(--black);
            font: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: var(--black);
            color: var(--white);
        }

        .btn {
            display: inline-block;
            padding: 0.625rem 1rem;
            border: 1px solid var(--black);
            border-radius: var(--radius);
            background: var(--black);
            color: var(--white);
            font: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .btn:hover {
            background: var(--white);
            color: var(--black);
        }

        .btn-outline {
            background: var(--white);
            color: var(--black);
        }

        .btn-outline:hover {
            background: var(--black);
            color: var(--white);
        }

        .btn-sm {
            padding: 0.35rem 0.65rem;
            font-size: 0.8rem;
        }

        .alert {
            margin-bottom: var(--space-4);
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            font-size: 0.9rem;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }

        /* Magento-style grid filters */
        .grid-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            position: relative;
        }

        .grid-tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--white);
            color: var(--black);
            font: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .grid-tool-btn:hover,
        .grid-tool-btn.is-active {
            border-color: var(--black);
            background: var(--surface);
        }

        .grid-tool-btn .grid-tool-icon {
            font-size: 0.95rem;
            line-height: 1;
        }

        .grid-filter-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.15rem;
            height: 1.15rem;
            padding: 0 0.3rem;
            border-radius: 999px;
            background: var(--black);
            color: var(--white);
            font-size: 0.7rem;
            font-weight: 700;
        }

        .grid-filter-panel {
            display: none;
            margin-bottom: 1rem;
            padding: 1rem 1.1rem 0.85rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
        }

        .grid-filter-panel.is-open {
            display: block;
        }

        .grid-filter-fields {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.85rem 1.25rem;
        }

        @media (max-width: 960px) {
            .grid-filter-fields {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .grid-filter-fields {
                grid-template-columns: 1fr;
            }
        }

        .grid-filter-field label {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .grid-filter-range {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .grid-filter-range .form-input {
            width: 100%;
        }

        .grid-filter-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
            padding-top: 0.85rem;
            border-top: 1px solid var(--border);
        }

        .grid-filter-cancel {
            color: var(--black);
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            background: none;
            border: 0;
            cursor: pointer;
            font: inherit;
        }

        .grid-filter-cancel:hover {
            text-decoration: underline;
        }

        .grid-columns-menu {
            display: none;
            position: absolute;
            top: calc(100% + 0.35rem);
            right: 0;
            z-index: 40;
            width: min(280px, 100%);
            max-height: 360px;
            overflow: auto;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--white);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .grid-columns-menu.is-open {
            display: block;
        }

        .grid-columns-title {
            margin: 0 0 0.65rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--muted);
        }

        .grid-columns-list {
            display: grid;
            gap: 0.35rem;
            margin-bottom: 0.75rem;
        }

        .grid-columns-list label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .grid-columns-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--border);
        }

        .grid-columns-actions button {
            border: 0;
            background: none;
            color: var(--black);
            font: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }

        .grid-columns-actions button:hover {
            text-decoration: underline;
        }


        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        table.data-table th,
        table.data-table td {
            padding: 0.75rem 0.85rem;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: middle;
        }

        table.data-table th {
            background: var(--surface);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        table.data-table tr:last-child td {
            border-bottom: 0;
        }

        .thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border: 1px solid var(--border);
            border-radius: 4px;
            background: var(--surface);
            display: block;
        }

        .thumb-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid var(--border);
            border-radius: 4px;
            background: var(--surface);
            font-size: 0.65rem;
            color: var(--black);
        }

        .media-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: var(--space-3);
        }

        .media-item {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--white);
        }

        .media-item img,
        .media-item video {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 4px;
            background: var(--surface);
            display: block;
        }

        .media-item-banner img {
            aspect-ratio: 16 / 9;
        }

        .media-item-video video {
            aspect-ratio: 16 / 9;
        }

        .media-remove-btn {
            position: absolute;
            top: 0.35rem;
            right: 0.35rem;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--white);
            color: var(--black);
            font-size: 1.15rem;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
        }

        .media-remove-btn:hover {
            background: var(--black);
            color: var(--white);
            border-color: var(--black);
        }

        .actions {
            display: flex;
            gap: 0.4rem;
            align-items: center;
        }

        .status-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            cursor: pointer;
            user-select: none;
        }

        .status-switch input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .status-switch-track {
            position: relative;
            width: 2.6rem;
            height: 1.4rem;
            border-radius: 999px;
            background: #c4c4c4;
            transition: background 0.2s ease;
            flex-shrink: 0;
        }

        .status-switch-track::after {
            content: '';
            position: absolute;
            top: 0.15rem;
            left: 0.15rem;
            width: 1.1rem;
            height: 1.1rem;
            border-radius: 50%;
            background: var(--white);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease;
        }

        .status-switch input:checked + .status-switch-track {
            background: var(--black);
        }

        .status-switch input:checked + .status-switch-track::after {
            transform: translateX(1.2rem);
        }

        .status-switch input:focus-visible + .status-switch-track {
            outline: 2px solid var(--black);
            outline-offset: 2px;
        }

        .status-switch-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
            min-width: 1.6rem;
        }

        .status-switch input:checked ~ .status-switch-label {
            color: var(--black);
        }

        .status-switch-form {
            display: inline-flex;
            margin: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3) var(--space-4);
        }

        .form-grid .full {
            grid-column: 1 / -1;
        }

        .form-field {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 0.375rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--black);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font: inherit;
            color: var(--black);
            background: var(--white);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--black);
        }

        .form-error {
            margin: 0.375rem 0 0;
            font-size: 0.85rem;
        }

        .form-hint {
            margin: 0.375rem 0 0;
            font-size: 0.8rem;
            color: var(--black);
            opacity: 0.7;
        }

        .form-panel {
            margin-bottom: var(--space-5);
            padding: var(--space-4);
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }

        .checkbox-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 0.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .form-actions {
            display: flex;
            gap: var(--space-2);
            margin-top: var(--space-4);
        }

        .empty-state {
            padding: var(--space-5);
            border: 1px dashed var(--border);
            border-radius: var(--radius);
            text-align: center;
        }

        .pagination {
            margin-top: var(--space-4);
        }

        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .shell {
                grid-template-columns: 1fr;
            }

            aside {
                border-right: 0;
                border-bottom: 1px solid var(--border);
                padding: var(--space-4) var(--space-3);
            }

            main {
                padding: var(--space-4) var(--space-3);
            }
        }
    </style>
</head>
<body>
@php
    $categoriesActive = request()->routeIs('admin.categories.*');
    $attributesActive = request()->routeIs('admin.attributes.*');
    $bannersActive = request()->routeIs('admin.banners.*');
    $menusActive = request()->routeIs('admin.menus.*');
    $ordersActive = request()->routeIs('admin.orders.*');
    $customersActive = request()->routeIs('admin.customers.*');
    $newsletterActive = request()->routeIs('admin.newsletter.*');
@endphp
<div class="shell">
    <aside>
        <div class="brand">Protein Point · Admin</div>

        <p class="nav-label">Menu</p>
        <nav aria-label="Admin navigation">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>

            <div class="nav-group" data-nav-group>
                <button
                    type="button"
                    class="nav-group-title"
                    aria-expanded="false"
                    aria-controls="products-submenu"
                    data-nav-toggle
                >
                    <span>Products</span>
                    <span class="nav-chevron" aria-hidden="true"></span>
                </button>
                <ul class="nav-sub" id="products-submenu">
                    <li>
                        <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.index') ? 'active' : '' }}">All Products</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.attributes.index') }}" class="nav-link {{ $attributesActive ? 'active' : '' }}">Attributes</a>
                    </li>
                </ul>
            </div>

            <div class="nav-group" data-nav-group>
                <button
                    type="button"
                    class="nav-group-title"
                    aria-expanded="false"
                    aria-controls="categories-submenu"
                    data-nav-toggle
                >
                    <span>Categories</span>
                    <span class="nav-chevron" aria-hidden="true"></span>
                </button>
                <ul class="nav-sub" id="categories-submenu">
                    <li>
                        <a href="{{ route('admin.categories.index') }}" class="nav-link {{ $categoriesActive ? 'active' : '' }}">All Categories</a>
                    </li>
                </ul>
            </div>

            <div class="nav-group" data-nav-group>
                <button
                    type="button"
                    class="nav-group-title"
                    aria-expanded="false"
                    aria-controls="banners-submenu"
                    data-nav-toggle
                >
                    <span>Banners</span>
                    <span class="nav-chevron" aria-hidden="true"></span>
                </button>
                <ul class="nav-sub" id="banners-submenu">
                    <li>
                        <a href="{{ route('admin.banners.index') }}" class="nav-link {{ $bannersActive ? 'active' : '' }}">Homepage Banners</a>
                    </li>
                </ul>
            </div>

            <div class="nav-group" data-nav-group>
                <button
                    type="button"
                    class="nav-group-title"
                    aria-expanded="false"
                    aria-controls="menus-submenu"
                    data-nav-toggle
                >
                    <span>Menus</span>
                    <span class="nav-chevron" aria-hidden="true"></span>
                </button>
                <ul class="nav-sub" id="menus-submenu">
                    <li>
                        <a href="{{ route('admin.menus.index') }}" class="nav-link {{ $menusActive ? 'active' : '' }}">All Menu Items</a>
                    </li>
                </ul>
            </div>

            <div class="nav-group {{ $customersActive ? 'is-open' : '' }}" data-nav-group>
                <button
                    type="button"
                    class="nav-group-title"
                    aria-expanded="{{ $customersActive ? 'true' : 'false' }}"
                    aria-controls="customers-submenu"
                    data-nav-toggle
                >
                    <span>Customers</span>
                    <span class="nav-chevron" aria-hidden="true"></span>
                </button>
                <ul class="nav-sub" id="customers-submenu">
                    <li>
                        <a href="{{ route('admin.customers.index') }}" class="nav-link {{ $customersActive ? 'active' : '' }}">All Customers</a>
                    </li>
                </ul>
            </div>

            <div class="nav-group {{ $newsletterActive ? 'is-open' : '' }}" data-nav-group>
                <button
                    type="button"
                    class="nav-group-title"
                    aria-expanded="{{ $newsletterActive ? 'true' : 'false' }}"
                    aria-controls="newsletter-submenu"
                    data-nav-toggle
                >
                    <span>Newsletter</span>
                    <span class="nav-chevron" aria-hidden="true"></span>
                </button>
                <ul class="nav-sub" id="newsletter-submenu">
                    <li>
                        <a href="{{ route('admin.newsletter.campaigns.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.campaigns.*') ? 'active' : '' }}">Campaigns</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.newsletter.subscribers.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.subscribers.*') ? 'active' : '' }}">Subscribers</a>
                    </li>
                </ul>
            </div>

            <div class="nav-group" data-nav-group>
                <button
                    type="button"
                    class="nav-group-title"
                    aria-expanded="false"
                    aria-controls="orders-submenu"
                    data-nav-toggle
                >
                    <span>Orders</span>
                    <span class="nav-chevron" aria-hidden="true"></span>
                </button>
                <ul class="nav-sub" id="orders-submenu">
                    <li>
                        <a href="{{ route('admin.orders.index') }}" class="nav-link {{ $ordersActive ? 'active' : '' }}">All Orders</a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="sidebar-footer">
            <p class="user-name">{{ session('admin_name') }}</p>
            <form class="logout-form" method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="logout-btn" type="submit">Logout</button>
            </form>
        </div>
    </aside>
    <main>
        <div class="content">
            @if (session('success'))
                <div class="alert">{{ session('success') }}</div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
<script>
    (function () {
        document.querySelectorAll('[data-nav-group]').forEach(function (group) {
            var toggle = group.querySelector('[data-nav-toggle]');
            if (!toggle) return;

            toggle.addEventListener('click', function () {
                var isOpen = group.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });
    })();
</script>
</body>
</html>
