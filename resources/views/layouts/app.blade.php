<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CKPS Mapping')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="{{ route('dashboard') }}" class="brand">
            @if(file_exists(public_path('images/logo-pu.png')))
                <img src="{{ asset('images/logo-pu.png') }}" alt="Logo PU" class="brand-logo-img">
            @else
                <span class="brand-logo-fallback">PU</span>
            @endif
            <div>
                <div class="brand-title">CKPS Mapping</div>
                <div class="brand-subtitle">Pemetaan Kompetensi dan Pelatihan ASN</div>
            </div>
        </a>

        <button type="button" class="mobile-menu-toggle" aria-expanded="false" aria-controls="main-navigation">☰ <span>Menu</span></button>
        <nav id="main-navigation" class="nav-links" aria-label="Navigasi utama">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('pelatihan.index') }}" class="{{ request()->routeIs('pelatihan.*') ? 'active' : '' }}">Pelatihan</a>
            <a href="{{ route('asn.index') }}" class="{{ request()->routeIs('asn.*') ? 'active' : '' }}">ASN</a>
            <a href="{{ route('kompetensi.index') }}" class="{{ request()->routeIs('kompetensi.*') ? 'active' : '' }}">Kompetensi</a>
            <a href="{{ route('laporan.index') }}" class="{{ request()->routeIs('laporan.*') ? 'active' : '' }}">Laporan</a>
            <a href="{{ route('audit.index') }}" class="{{ request()->routeIs('audit.*') ? 'active' : '' }}">Audit</a>
            <a href="{{ route('import.index') }}" class="nav-import-mobile {{ request()->routeIs('import.*') ? 'active' : '' }}">Import Excel</a>
        </nav>

        <div class="header-account">
            <a href="{{ route('import.index') }}" class="header-import">Import Excel</a>
            <span class="header-user">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-header-logout">Keluar</button>
            </form>
            <details class="mobile-account-menu">
                <summary>👤 <span>{{ auth()->user()->name }}</span></summary>
                <div class="mobile-account-popover">
                    <div class="mobile-account-name">{{ auth()->user()->name }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mobile-account-logout">Keluar</button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>

<main>
    @if(session('success'))
        <div class="page" style="padding-bottom:0;">
            <div class="alert alert-success">✓ {{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="page" style="padding-bottom:0;">
            <div class="alert alert-error">⚠ {{ session('error') }}</div>
        </div>
    @endif

    @yield('content')
</main>

@stack('scripts')
</body>
</html>
