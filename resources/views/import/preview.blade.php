@extends('layouts.app')
@section('title', 'Preview Import Excel - CKPS Mapping')
@section('content')
@php($s = $preview->summary ?? [])
<div class="page page-narrow">
    <div class="page-header">
        <div class="page-header-row">
            <div>
                <p class="page-eyebrow">Validasi sebelum commit</p>
                <h1 class="page-title">Preview Import Excel</h1>
                <p class="page-description">Database belum diubah. Periksa hasil pembacaan file berikut sebelum melanjutkan.</p>
            </div>
            <a href="{{ route('import.index') }}" class="btn btn-outline">← Kembali</a>
        </div>
    </div>

    @if($errors->any())
        <section class="panel" style="margin-bottom:18px;border-color:#e7a7a7;background:#fff5f5;">
            <div class="panel-header"><h2>⛔ Import belum boleh dikonfirmasi</h2><p>Perbaiki masalah validasi berikut. Database belum diubah.</p></div>
            <div class="panel-body"><ul class="error-list" style="margin:0;padding-left:20px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul></div>
        </section>
    @endif

    @if(!empty($s['blocking_errors']))
        <section class="panel" style="margin-bottom:18px;border-color:#e7a7a7;background:#fff5f5;">
            <div class="panel-header"><h2>⛔ Masalah yang wajib diperbaiki</h2><p>Sistem sengaja memblokir import untuk mencegah data ASN yang salah masuk ke database.</p></div>
            <div class="panel-body"><ul class="error-list" style="margin:0;padding-left:20px;">
                @foreach($s['blocking_errors'] as $error)<li>{{ $error }}</li>@endforeach
            </ul></div>
        </section>
    @endif

    @if(!empty($s['warnings']))
        <section class="panel" style="margin-bottom:18px;border-color:#f3d08a;background:#fffaf0;">
            <div class="panel-header"><h2>⚠️ Peringatan yang perlu diperiksa</h2><p>Sistem menemukan kondisi yang perlu kamu pahami sebelum import.</p></div>
            <div class="panel-body">
                <ul class="error-list" style="margin:0;padding-left:20px;">
                    @foreach($s['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach
                </ul>
            </div>
        </section>
    @else
        <div class="alert alert-success" style="margin-bottom:18px;">✓ Tidak ada peringatan utama pada struktur file.</div>
    @endif

    <section class="panel" style="margin-bottom:18px;">
        <div class="panel-header"><h2>Ringkasan file</h2><p><strong>{{ $preview->filename }}</strong></p></div>
        <div class="panel-body">
            <div class="stats-grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;">
                <div class="stat-card"><span>Sheet</span><strong>{{ number_format($s['total_sheets'] ?? 0) }}</strong></div>
                <div class="stat-card"><span>Baris berisi data</span><strong>{{ number_format($s['total_rows'] ?? 0) }}</strong></div>
                <div class="stat-card"><span>NIP unik</span><strong>{{ number_format($s['unique_nips'] ?? 0) }}</strong></div>
                <div class="stat-card"><span>NIP duplikat</span><strong>{{ number_format(count($s['duplicate_nips'] ?? [])) }}</strong></div>
            </div>
        </div>
    </section>

    <section class="panel" style="margin-bottom:18px;">
        <div class="panel-header"><h2>Struktur sheet</h2><p>Periksa apakah file sumber terbaca sesuai format yang diharapkan.</p></div>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Sheet</th><th>Jenis</th><th>Baris</th><th>NIP</th><th>Pelatihan</th><th>Catatan</th></tr></thead><tbody>
        @foreach($s['sheets'] ?? [] as $sheet)
            <tr>
                <td><strong>{{ $sheet['name'] }}</strong></td>
                <td>{{ $sheet['type'] }}</td>
                <td>{{ number_format($sheet['rows']) }}</td>
                <td>{{ number_format($sheet['asn_count']) }}</td>
                <td>{{ count($sheet['training_headers']) ? implode(', ', $sheet['training_headers']) : '—' }}</td>
                <td>{{ count($sheet['warnings']) ? implode(' ', $sheet['warnings']) : '✓ OK' }}</td>
            </tr>
        @endforeach
        </tbody></table></div>
    </section>

    <section class="panel" style="margin-bottom:18px;">
        <div class="panel-header"><h2>Laporan validasi per baris</h2><p>Baris di bawah harus diperbaiki pada file Excel sebelum import dapat dikonfirmasi.</p></div>
        @if(($s['row_issue_count'] ?? 0) > 0)
            <div class="panel-body" style="padding-bottom:0;"><strong>{{ number_format($s['row_issue_count']) }} baris bermasalah terdeteksi.</strong> Maksimal 200 baris pertama ditampilkan pada preview.</div>
            <div class="table-wrap"><table class="data-table"><thead><tr><th>Sheet</th><th>Baris</th><th>Nama</th><th>NIP</th><th>Kolom</th><th>Nilai</th><th>Masalah</th></tr></thead><tbody>
            @foreach($s['row_issues'] ?? [] as $issue)
                @foreach($issue['issues'] ?? [] as $problem)
                    <tr>
                        <td>{{ $issue['sheet'] ?? '—' }}</td>
                        <td><strong>{{ $issue['row'] ?? '—' }}</strong></td>
                        <td>{{ $issue['name'] ?: '—' }}</td>
                        <td>{{ $issue['nip'] ?: '—' }}</td>
                        <td><strong>{{ $problem['column'] ?? '—' }}</strong></td>
                        <td>{{ $problem['value'] !== '' ? $problem['value'] : '—' }}</td>
                        <td style="color:#a33;">{{ $problem['message'] ?? 'Data tidak valid.' }}</td>
                    </tr>
                @endforeach
            @endforeach
            </tbody></table></div>
        @else
            <div class="panel-body"><div class="alert alert-success" style="margin:0;">✓ Tidak ditemukan error pada baris ASN yang memiliki header Nama dan NIP.</div></div>
        @endif
    </section>

    <section class="panel" style="margin-bottom:18px;">
        <div class="panel-header"><h2>Preview data ASN</h2><p>Sampel ini membantu memastikan jabatan, pangkat/golongan, dan unit kerja tidak tertukar.</p></div>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Nama</th><th>NIP</th><th>Jabatan</th><th>Pangkat/Gol.</th><th>Unit kerja</th></tr></thead><tbody>
        @forelse($s['sample_asns'] ?? [] as $asn)
            <tr><td><strong>{{ $asn['name'] ?: '—' }}</strong></td><td>{{ $asn['nip'] }}</td><td>{{ $asn['position'] ?: '—' }}</td><td>{{ $asn['rank'] ?: '—' }}</td><td>{{ $asn['unit'] ?: '—' }}</td></tr>
        @empty
            <tr><td colspan="5"><div class="empty-state">Belum ada sampel ASN yang dapat ditampilkan.</div></td></tr>
        @endforelse
        </tbody></table></div>
    </section>

    <section class="panel" style="border-color:#b7d7c2;">
        <div class="panel-header"><h2>Konfirmasi import</h2><p>Tekan konfirmasi hanya jika preview sudah sesuai dengan file sumber resmi.</p></div>
        <div class="panel-body" style="display:flex;gap:10px;flex-wrap:wrap;">
            @if($s['can_confirm'] ?? false)
                <form method="POST" action="{{ route('import.preview.confirm', $preview) }}" onsubmit="return confirm('Yakin import file ini ke database? Pastikan preview sudah benar.');">
                    @csrf
                    <button class="btn btn-primary" type="submit">✓ Konfirmasi & Masukkan ke Database</button>
                </form>
            @else
                <button class="btn btn-outline" type="button" disabled title="Perbaiki masalah validasi terlebih dahulu">⛔ Konfirmasi diblokir</button>
            @endif
            <form method="POST" action="{{ route('import.preview.cancel', $preview) }}">
                @csrf
                <button class="btn btn-outline" type="submit">Batalkan</button>
            </form>
        </div>
    </section>
</div>
@endsection
