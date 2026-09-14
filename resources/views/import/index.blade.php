@extends('layouts.app')
@section('title', 'Import Data Excel - CKPS Mapping')
@section('content')
<div class="page page-narrow">
    <div class="page-header"><div class="page-header-row"><div><p class="page-eyebrow">Pemutakhiran Data</p><h1 class="page-title">Import Data Excel</h1><p class="page-description">Perbarui data ASN, pelatihan, dan kompetensi dari file Excel tanpa menghapus data yang sudah ada.</p></div><a href="{{ route('dashboard') }}" class="btn btn-outline">← Dashboard</a></div></div>
    @if($errors->any())<div class="alert alert-error" style="margin-bottom:18px;"><strong>Import tidak berhasil.</strong><ul class="error-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="import-grid">
        <section class="panel import-upload-card"><div class="panel-header"><h2>Periksa file sebelum masuk database</h2><p>File tidak akan mengubah database sampai kamu menekan tombol konfirmasi.</p></div><div class="panel-body">
            <div class="import-warning"><strong>ℹ️ Mode aman untuk pemutakhiran</strong><span>Upload pertama hanya membuat preview. Data ASN diperbarui berdasarkan NIP. Master pelatihan dibaca dari header/nama pelatihan pada Excel. Penanda angka seperti 1, 10, 11, atau 12 tidak akan dianggap sebagai nama pelatihan. Jika Excel tidak memiliki kode pelatihan resmi, sistem membuat kode internal PLT-001, PLT-002, dan seterusnya agar setiap pelatihan tetap mudah dirujuk.</span></div>
            <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data" class="import-form">@csrf
                <label class="dropzone" for="excel_file"><span class="dropzone-icon">📊</span><strong>Pilih file Excel</strong><span id="fileName">XLSX, XLS, atau CSV · maksimal 50 MB</span><input id="excel_file" type="file" name="file" accept=".xlsx,.xls,.csv" required></label>
                <button type="submit" class="btn btn-primary import-submit">🔎 Periksa & Preview</button>
            </form>
            <div class="import-steps"><div><b>1</b><span>Upload file</span></div><div><b>2</b><span>Preview & Validasi</span></div><div><b>3</b><span>Konfirmasi</span></div><div><b>4</b><span>Catat riwayat</span></div></div><div class="import-warning" style="margin-top:14px;"><strong>⛔ Perlindungan data</strong><span>Jika sistem mendeteksi NIP tidak valid atau kemungkinan Jabatan/Unit Kerja/Pangkat tertukar, tombol konfirmasi akan otomatis diblokir. Tidak ada data yang masuk sebelum validasi lolos.</span></div>
        </div></section>
        <section class="panel"><div class="panel-header"><h2>Catatan penting</h2><p>Agar hasil import konsisten.</p></div><div class="panel-body import-notes">
            <div><span>✓</span><p><strong>NIP menjadi identitas ASN.</strong><br>NIP yang sama diperbarui, bukan dibuat ganda.</p></div>
            <div><span>✓</span><p><strong>Status pelatihan manual tetap dipertahankan.</strong><br>Import tidak menghapus status yang dipilih dari detail ASN.</p></div>
            <div><span>✓</span><p><strong>Data yang tidak ada di Excel tidak otomatis dihapus.</strong><br>Artefak nama pelatihan yang hanya berupa angka dibersihkan agar tidak muncul lagi sebagai nama pelatihan.</p></div>
            <div><span>✓</span><p><strong>Kode pelatihan tidak dibuat-buat.</strong><br>Jika file Excel menyediakan kode pelatihan, kode tersebut dipertahankan. Jika tidak tersedia, sistem membuat kode internal berurutan seperti PLT-001. Kode internal bukan kode resmi kementerian.</p></div><div><span>✓</span><p><strong>Gunakan file sumber resmi.</strong><br>Jangan unggah data ASN ke perangkat/layanan yang tidak berwenang.</p></div>
        </div></section>
    </div>
    <section class="panel" style="margin-top:20px;"><div class="panel-header"><h2>Riwayat Import</h2><p>Catatan file yang pernah diproses.</p></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Waktu</th><th>File</th><th>Sheet</th><th>ASN</th><th>Pelatihan</th><th>Kompetensi</th><th>Status</th></tr></thead><tbody>
        @forelse($imports as $import)<tr><td>{{ $import->created_at?->format('d M Y H:i') }}</td><td><strong>{{ $import->filename }}</strong></td><td>{{ number_format($import->total_sheets, 0, ',', '.') }}</td><td>{{ number_format($import->total_asns, 0, ',', '.') }}</td><td>{{ number_format($import->total_trainings, 0, ',', '.') }}</td><td>{{ number_format($import->total_competencies, 0, ',', '.') }}</td><td><span class="status-pill {{ $import->status === 'completed' ? 'sudah' : 'belum' }}">{{ $import->status === 'completed' ? 'Berhasil' : ucfirst($import->status) }}</span></td></tr>
        @empty<tr><td colspan="7"><div class="empty-state">Belum ada riwayat import.</div></td></tr>@endforelse
    </tbody></table></div><div class="pagination-bar">{{ $imports->links() }}</div></section>
</div>
@endsection
@push('scripts')<script>document.addEventListener('DOMContentLoaded',function(){const i=document.getElementById('excel_file'),n=document.getElementById('fileName');i?.addEventListener('change',function(){n.textContent=this.files?.[0]?.name||'XLSX, XLS, atau CSV · maksimal 50 MB';});});</script>@endpush
