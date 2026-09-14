# Perbaikan CKPS Mapping

## Perubahan terbaru

### 1. Nama pelatihan tidak lagi mengambil angka ID
Workbook CKPS memiliki angka pada kolom `No` di blok STANDKOM/matriks. Angka tersebut bukan nama pelatihan.

Importer sekarang:
- membaca master nama pelatihan dari blok `STANDKOM` kolom `M:Z`;
- membaca header pelatihan dari format matriks yang berbeda-beda;
- mengabaikan nilai seperti `1`, `10`, `11`, `12`, `Belum`, `PNS`, dan penanda matriks lain sebagai nama pelatihan;
- menghapus artefak master pelatihan lama yang hanya berupa angka ketika import ulang.

### 2. Dukungan beberapa format sheet matriks
Importer sekarang mengenali:
- sheet dengan header `Pelatihan` lalu nama pelatihan pada baris berikutnya;
- sheet seperti DSSPI dengan `Pelatihan` pada baris 1 dan nama pada baris 2;
- sheet seperti `Dir Air Minum` yang langsung menaruh nama-nama pelatihan pada baris 2 mulai kolom G/H.

### 3. Kode pelatihan
Jika workbook memiliki kode pelatihan resmi, kode tersebut dipertahankan.
Jika workbook tidak menyediakan kode pelatihan, aplikasi memberi kode internal stabil seperti:

- `PLT-001`
- `PLT-002`
- `PLT-003`

Kode tersebut adalah **kode internal aplikasi**, bukan kode resmi kementerian.

### 4. Import Excel
Upload sekarang menerima:
- `.xlsx`
- `.xls`
- `.csv`
- maksimum 50 MB

File juga diperiksa menggunakan PhpSpreadsheet sebelum diproses. Jika file rusak atau tidak dapat dibaca, halaman import menampilkan pesan yang lebih jelas.

## Setelah menimpa project

Jalankan:

```bash
php artisan optimize:clear
php artisan migrate
npm install
npm run build
php artisan serve
```

Kemudian login dan buka **Import Data Excel**.

### Penting
Lakukan import ulang workbook resmi setelah memasang versi ini. Import ulang diperlukan agar master pelatihan lama yang salah seperti `1`, `10`, `11`, `12`, dan seterusnya dibersihkan dan diganti dengan nama pelatihan yang benar.

Jangan membagikan workbook yang berisi data ASN kepada pihak yang tidak berwenang.

## Perbaikan import terbaru

- Relasi kompetensi ASN sekarang memakai `asn_kompetensi` + `kompetensi`, sehingga tidak lagi bentrok dengan tabel duplikat `asn_competencies` + `competencies`.
- Relasi kompetensi-pelatihan memakai tabel `competency_trainings` yang konsisten dengan master `trainings`.
- Import Excel tetap membaca nama pelatihan dari header matrix, bukan angka penanda.
- Setelah import selesai, kode internal `PLT-001`, `PLT-002`, dan seterusnya dibuat hanya jika Excel tidak memiliki kode resmi.
- Pada mode local, pesan error import menampilkan detail exception agar masalah PHP/PhpSpreadsheet dapat diketahui dengan cepat.
