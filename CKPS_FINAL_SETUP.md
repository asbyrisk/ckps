# CKPS Mapping — Final setup

Paket ini mempertahankan struktur aplikasi yang sudah berjalan dan menambahkan hardening untuk
import Excel, master ASN, riwayat perpindahan, audit, dan pemeriksaan database.

## Setelah menimpa project

Jalankan dari folder project:

```bash
composer install
php artisan optimize:clear
php artisan migrate
php artisan ckps:check
php artisan serve
```

Jika `php artisan migrate` menampilkan `Nothing to migrate`, itu berarti seluruh migration yang
tercatat pada tabel `migrations` sudah dijalankan. Tetap jalankan `php artisan ckps:check` untuk
memastikan tabel inti benar-benar ada.

## Import Excel

Alur yang aman:

1. Buka menu Import.
2. Upload Excel.
3. Periksa Preview.
4. Pastikan NIP 18 digit.
5. Pastikan Jabatan, Unit Kerja, dan Pangkat/Golongan tidak tertukar.
6. Pastikan pangkat menggunakan bentuk seperti `IV/a`, `III/c`, dst.
7. Konfirmasi import.

Sistem menggunakan API PhpSpreadsheet modern (`getCell()` + koordinat A1), sehingga tidak
menggunakan `getCellByColumnAndRow()` yang sudah tidak tersedia pada versi PhpSpreadsheet baru.

Nama pelatihan numerik seperti `1`, `2`, `3` tidak dianggap sebagai nama pelatihan. Kode pelatihan
akan dipertahankan jika tersedia; jika sumber tidak menyediakan kode, aplikasi memberi kode internal
`PLT-xxx`.

## Perpindahan ASN

Detail ASN menyediakan:
- unit kerja saat ini;
- form perpindahan ke unit lain;
- tanggal efektif;
- alasan;
- catatan;
- riwayat perpindahan;
- koreksi perpindahan terbaru tanpa menghapus jejak audit.

Koreksi hanya membatalkan riwayat dan mengembalikan unit ASN ke unit sebelumnya. Riwayat tidak
dihapus.

## Keamanan

Login/logout tetap melalui middleware `auth`. Audit log menyimpan perubahan penting. Password
diproses oleh Laravel menggunakan hashing, bukan disimpan sebagai teks biasa.

Untuk deployment kementerian/produksi, jangan memakai `.env` development, jangan commit password,
atur HTTPS, backup database, batasi akun admin, dan nonaktifkan `APP_DEBUG=true`.

## Catatan penting

Jangan menjalankan `migrate:fresh` pada database yang berisi data ASN kecuali memang ingin
menghapus seluruh database. Gunakan `php artisan migrate`.
