# Role Admin dan User

## Role
- `admin`: mengelola data ASN, import Excel, audit log, dan role pengguna.
- `user`: akses baca untuk ASN, pelatihan, kompetensi, dan laporan.

## Membuat Admin pertama
Setelah migration selesai dan akun sudah terdaftar:

```bash
php artisan user:make-admin email@example.com
```

Role Admin terakhir tidak dapat diturunkan agar sistem tidak kehilangan seluruh administrator.

## Route
Route perubahan data ASN, import, audit, dan manajemen pengguna dilindungi middleware `admin`.
