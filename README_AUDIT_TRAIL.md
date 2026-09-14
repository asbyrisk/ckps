# Audit Trail - CKPS Mapping

Patch ini menambahkan audit trail untuk meningkatkan kualitas dan keterlacakan perubahan data.

## Yang ditambahkan

- Tabel `audit_logs` yang aman dijalankan ulang jika tabel sudah ada.
- Model `AuditLog`.
- Service `AuditLogger`.
- Halaman `/audit-log` dengan pencarian, filter aktivitas, rentang tanggal, pagination, ringkasan statistik, dan detail perubahan.
- Menu **Audit** di navigasi utama.
- Pencatatan aktivitas:
  - login
  - pembuatan akun
  - tambah ASN
  - ubah ASN
  - hapus ASN
  - perubahan status pelatihan ASN
  - perpindahan unit kerja ASN
  - koreksi/pembatalan riwayat perpindahan
  - import Excel berhasil
  - import Excel gagal

## Cara menerapkan

Salin file patch ini ke project Laravel yang sedang digunakan, lalu jalankan:

```bash
php artisan migrate
php artisan optimize:clear
php artisan ckps:check
```

Migration audit dibuat idempotent: jika `audit_logs` sudah ada, migration tidak mencoba membuat tabel kedua. Command `ckps:check` juga memeriksa tabel dan kolom inti setelah migration.

Setelah itu buka:

```text
http://127.0.0.1:8000/audit-log
```

## Catatan

Audit trail tidak menghapus riwayat ketika koreksi dilakukan. Contohnya, jika perpindahan Jawa Barat -> Yogyakarta dibatalkan, sistem tetap menyimpan aktivitas pembatalan beserta alasan koreksinya.

Semua file PHP pada patch sudah dicek dengan `php -l`.
