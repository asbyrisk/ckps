# CKPS Mapping — Import Excel Aman

Revisi ini mengubah alur import Excel menjadi **Preview → Validasi → Konfirmasi → Import**.

## Alur operator
1. Buka **Import Excel**.
2. Pilih XLSX/XLS/CSV.
3. Klik **Periksa & Preview**.
4. Periksa ringkasan sheet, jumlah NIP, duplikasi NIP, dan sampel jabatan/pangkat/unit kerja.
5. Jika benar, klik **Konfirmasi & Masukkan ke Database**.
6. Jika salah, klik **Batalkan**. Database tidak disentuh.

## Keamanan alur
- File preview disimpan sementara dan terkait dengan user yang mengunggah.
- Preview berlaku 2 jam.
- User lain tidak dapat membuka atau mengonfirmasi preview tersebut.
- File sementara dihapus setelah import berhasil atau preview dibatalkan.
- Import tetap berjalan dalam transaksi database pada `ExcelImporterService`.

## Migration
Jalankan:

```bash
php artisan migrate
php artisan optimize:clear
```

Jangan gunakan `migrate:fresh` pada database yang sudah berisi data.
