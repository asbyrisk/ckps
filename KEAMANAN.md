# Keamanan CKPS Mapping

Fitur keamanan yang ditambahkan:
- Semua halaman aplikasi utama memerlukan login.
- Login menggunakan Laravel session authentication.
- Session diregenerasi setelah login dan di-invalidate saat logout.
- CSRF protection menggunakan middleware Laravel.
- Rate limit login: maksimal 5 percobaan per menit per email + IP.
- Password dibuat dengan hash Laravel dan akun baru dianjurkan minimal 12 karakter, huruf besar/kecil, angka, dan simbol.
- Tidak ada akun default yang dibuat oleh DatabaseSeeder.
- Security response headers ditambahkan.
- Tidak ada halaman registrasi publik.

## Membuat akun pertama

Jalankan:

php artisan migrate
php artisan app:create-user

Atau:

php artisan app:create-user --name="Admin CKPS" --email="admin@example.go.id"

Untuk keamanan, masukkan password secara interaktif agar tidak tersimpan di shell history.

## Produksi

Sebelum deployment:
- APP_ENV=production
- APP_DEBUG=false
- APP_URL menggunakan HTTPS
- SESSION_SECURE_COOKIE=true
- Gunakan database server/host yang memiliki backup dan kontrol akses.
- Jangan commit `.env`.
- Batasi akses database hanya dari server aplikasi.
- Aktifkan backup terenkripsi dan uji pemulihan.
- Gunakan HTTPS/TLS end-to-end.
- Batasi akun aplikasi berdasarkan kebutuhan (least privilege).
- Rotasi kredensial dan secret secara berkala.

Catatan: aplikasi ini melindungi akses aplikasi, tetapi keamanan data secara menyeluruh juga bergantung pada konfigurasi server, database, jaringan, backup, dan kebijakan organisasi.
