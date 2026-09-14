# CKPS Mapping - Schema Fix

Database schema canonical yang digunakan aplikasi ini:

- `trainings`
- `kompetensi`
- `kompetensi_pelatihan`
- `asns`
- `asn_trainings`
- `asn_kompetensi`

Aplikasi tidak lagi memakai `competency_trainings`.

Setelah menimpa project:

```bash
php artisan optimize:clear
php artisan migrate
php artisan serve
```

Jangan menjalankan `php artisan migrate:fresh` karena akan menghapus data.

Import Excel menggunakan `kompetensi_pelatihan` untuk relasi kompetensi dan pelatihan.
