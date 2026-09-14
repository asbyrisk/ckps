# CKPS Mapping - Revisi Final

Patch ini dibuat dari project ZIP yang terakhir dikirim.

## Perbaikan utama

1. Error `AsnTraining::statusLabel()` diperbaiki.
2. Status pelatihan mendukung:
   - Sudah mengikuti
   - Belum mengikuti
   - Diusulkan
3. Import Excel tidak lagi menulis relasi kompetensi ke tabel legacy `kompetensi_pelatihan`.
   Relasi aplikasi sekarang memakai `training_kompetensi`, sesuai model `Pelatihan` dan `Kompetensi`.
   Ini memperbaiki error FK 1452 pada `kompetensi_pelatihan`.
4. Pembacaan identitas ASN diperbaiki untuk beberapa layout Excel:
   - D=Jabatan, E=Unit Kerja, F=Golongan
   - D=Pangkat/Golongan, E=Jabatan, F=Unit Kerja
   - D=Pangkat/Golongan, E=Unit Kerja, F=Golongan
   - D=Jabatan, E=Unit Kerja, F=Pangkat/Golongan
5. Golongan dinormalisasi menjadi format seperti `IV/a`, `III/c`.
6. Jabatan panjang tidak lagi dimasukkan ke kolom `rank`.
7. Unit kerja seperti `Balai ...`, `Direktorat ...`, `Sekretariat ...` dipisahkan dari jabatan berdasarkan isi nilai, bukan hanya nama header.
8. Import tidak menghapus data jabatan/pangkat/unit yang sudah benar ketika sheet tertentu tidak membawa identitas lengkap.
9. Nama pelatihan numerik seperti `1`, `10`, `11` dibersihkan; kode internal `PLT-001`, dst. dibuat jika sumber Excel tidak menyediakan kode.
10. Riwayat perpindahan unit ASN tetap tersedia melalui halaman Detail ASN.
11. Pagination Data ASN sudah menampilkan maksimal 5 nomor halaman di sekitar halaman aktif.

## Cara memasang

Timpa folder berikut pada project yang sedang dipakai:

- `app/`
- `resources/`
- `routes/`
- `database/migrations/`

Jangan menimpa `.env` dan jangan hapus `vendor/`.

Kemudian:

```bash
php artisan optimize:clear
php artisan migrate
```

Jangan gunakan `php artisan migrate:fresh`.

Setelah aplikasi bisa dibuka, import kembali file Excel sumber yang sama agar Jabatan, Pangkat, dan Unit Kerja yang sudah telanjur tersimpan salah diperbarui.

## Catatan

Migration `asn_unit_histories` dan `training_kompetensi` dibuat aman jika tabelnya sudah ada.

Password registrasi tetap menggunakan aturan Laravel:
minimal 8 karakter + huruf besar/kecil + angka + simbol.
