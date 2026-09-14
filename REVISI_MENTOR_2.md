# Revisi Mentor 2 — CKPS Mapping

Perubahan utama:

1. Import Excel:
   - Format BPBPK yang memiliki D=`Pangkat / Golongan` tetapi isinya jabatan, E=`Unit Kerja`, F=`Golongan` sekarang dibaca sebagai Jabatan=D, Unit Kerja=E, Pangkat=F.
   - Format lain yang benar-benar memakai D=`IV/a`/`III/c` tetap didukung.
   - Nilai jabatan panjang tidak lagi dimasukkan ke kolom rank.
   - Relasi kompetensi-pelatihan memakai tabel `training_kompetensi` yang FK-nya konsisten ke `trainings`, sehingga tidak lagi bergantung pada pivot lama `kompetensi_pelatihan` yang menunjuk tabel `pelatihan`.

2. Detail ASN:
   - Status pelatihan: Sudah mengikuti, Belum mengikuti, Diusulkan.
   - Status Diusulkan benar-benar tersimpan dan ditampilkan kembali.

3. Perpindahan unit kerja:
   - Form Unit Kerja Tujuan, tanggal berlaku, alasan, dan catatan.
   - Saat disimpan, unit kerja aktif ASN berubah ke unit tujuan.
   - Riwayat dari unit lama ke unit baru disimpan di `asn_unit_histories` beserta user yang melakukan perubahan.

4. Filter ASN:
   - Ditambahkan filter `Diusulkan`.

## Cara pasang

Ekstrak isi ZIP ini ke folder utama project Laravel yang berisi `artisan`, `app`, `database`, `resources`, dan `routes`, lalu pilih **Replace**.

Jangan menimpa `.env`, `vendor`, atau database.

Jalankan:

```bash
php artisan migrate
php artisan optimize:clear
php artisan serve
```

Jangan gunakan `php artisan migrate:fresh` karena akan menghapus data database.

Setelah itu **import ulang Excel** agar data Jabatan, Pangkat, dan Unit Kerja yang sudah salah diperbaiki berdasarkan Excel sumber.

Catatan: bila `php artisan view:cache` gagal dengan `Class "DOMDocument" not found`, aktifkan ekstensi PHP `dom`/`xml`; itu masalah environment PHP, bukan mapping Excel.
