@extends('admin::layouts.app')

@section('title', 'Dashboard')

@section('content')
    <header class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-lead">Overview of users across the Protein Point platform.</p>
    </header>

    <section class="section" aria-label="Platform overview">
        <h2 class="section-title">Overview</h2>
        <div class="stat-grid">
            <article class="stat-card">
                <span class="stat-label">Admin users</span>
                <span class="stat-value">{{ number_format($adminUsers) }}</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">Seller users</span>
                <span class="stat-value">{{ number_format($sellerUsers) }}</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">Storefront users</span>
                <span class="stat-value">{{ number_format($storefrontUsers) }}</span>
            </article>
        </div>
    </section>
@endsection
