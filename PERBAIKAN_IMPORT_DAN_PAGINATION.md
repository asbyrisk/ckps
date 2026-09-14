# Perbaikan Import Excel dan Pagination Data ASN

Perbaikan pada paket ini:

1. Import Excel tidak lagi memakai `Worksheet::getCellByColumnAndRow()`, yang sudah tidak tersedia pada PhpSpreadsheet 2.x.
2. Import membaca sel dengan `Coordinate::stringFromColumnIndex()` + `Worksheet::getCell()`.
3. Kolom `rank` tidak lagi diisi dengan jabatan panjang ketika kolom D ternyata berisi jabatan.
4. Migration baru memperbesar kolom `asns.rank` menjadi `TEXT` agar data lama/baru tidak gagal karena panjang nilai.
5. Pagination Data ASN menampilkan maksimal 5 nomor halaman sekaligus, misalnya `‹ 1 2 3 4 5 ›`.
6. Relasi kompetensi-pelatihan tetap menggunakan pivot aplikasi `kompetensi_pelatihan`, bukan tabel `competency_trainings`.

## Setelah menimpa file project

Jalankan dari folder project Laravel:

```bash
composer install
php artisan migrate
php artisan optimize:clear
php artisan serve
```

**Jangan menjalankan `php artisan migrate:fresh` atau `php artisan migrate:refresh`**, karena dapat menghapus/mengulang data yang sudah ada.

Jika `vendor/` sudah ada dan `composer.lock` tidak berubah, `composer install` tetap aman dan hanya memastikan dependensi sesuai lock file.
