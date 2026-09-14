<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Buat Akun — CKPS Mapping</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-card" aria-labelledby="register-title">
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

            <h1 id="register-title">Buat akun baru</h1>
            <p class="login-description">Buat akun untuk mengakses data ASN, pelatihan, kompetensi, import Excel, dan laporan.</p>

            @if($errors->any())
                <div class="alert alert-error">
                    <strong>Akun belum dibuat.</strong>
                    <ul class="error-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="login-form">
                @csrf

                <div class="form-group">
                    <label for="name">Nama pengguna</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    <small class="form-help">Minimal 8 karakter, wajib mengandung huruf besar, huruf kecil, angka, dan simbol.</small>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi kata sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                </div>

                <button type="submit" class="btn btn-primary login-submit">Buat akun</button>
            </form>

            <div class="login-register-link">
                Sudah punya akun? <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
            </div>

            <p class="login-footer">CKPS Mapping · Sistem Pemetaan Kompetensi dan Pelatihan ASN</p>
        </section>
    </main>
</body>
</html>
