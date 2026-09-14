<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Masuk — CKPS Mapping</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-card" aria-labelledby="login-title">
            <div class="login-brand">
                @if(file_exists(public_path('images/logo-pu.png')))
                    <img src="{{ asset('images/logo-pu.png') }}" alt="Logo instansi" class="login-logo">
                @else
                    <div class="login-logo-fallback">PU</div>
                @endif
                <div>
                    <div class="login-kicker">Sistem Internal</div>
                    <div class="login-title-small">CKPS Mapping</div>
                </div>
            </div>

            <h1 id="login-title">Masuk ke sistem</h1>
            <p class="login-description">
                Gunakan akun yang telah diberikan untuk mengakses data ASN,
                pelatihan, dan kompetensi.
            </p>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    <strong>Gagal masuk.</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="login-form">
                @csrf

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email"
                           value="{{ old('email') }}" autocomplete="username"
                           required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password"
                           autocomplete="current-password" required>
                </div>

                <label class="login-remember">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ingat saya di perangkat ini</span>
                </label>

                <button type="submit" class="btn btn-primary login-submit">
                    Masuk
                </button>
            </form>

            <div class="login-security">
                <span>🔒</span>
                <div>
                    <strong>Akses terbatas</strong>
                    <p>Data hanya dapat diakses oleh pengguna yang berhasil masuk.</p>
                </div>
            </div>

            <div class="login-register-link">
                Belum punya akun? <a href="{{ route('register') }}">Buat akun baru</a>
            </div>

            <p class="login-footer">CKPS Mapping · Sistem Pemetaan Kompetensi dan Pelatihan ASN</p>
        </section>
    </main>
</body>
</html>
