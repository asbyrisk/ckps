@extends('layouts.app')

@section('title', 'Dashboard - CKPS Mapping')

@section('content')
<div class="page">
    <div class="page-header">
        <div class="page-header-row">
            <div>
                <p class="page-eyebrow">CKPS Mapping</p>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-description">Ringkasan data ASN, pelatihan, dan kompetensi dalam satu tampilan.</p>
            </div>
            <div class="dashboard-actions">
                <a href="{{ route('asn.create') }}" class="btn">+ Tambah ASN</a>
                <a href="{{ route('pelatihan.index') }}" class="btn btn-secondary">Lihat Pelatihan</a>
                <a href="{{ route('laporan.index') }}" class="btn btn-outline">📈 Laporan</a>
                <a href="{{ route('import.index') }}" class="btn btn-outline">📊 Import Excel</a>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👤</div>
            <div class="stat-label">Total ASN</div>
            <div class="stat-value">{{ number_format($totalAsn, 0, ',', '.') }}</div>
            <div class="stat-help">Jumlah ASN yang terdaftar.</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <div class="stat-label">Total Pelatihan</div>
            <div class="stat-value">{{ number_format($totalPelatihan, 0, ',', '.') }}</div>
            <div class="stat-help">Master pelatihan yang tersedia.</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🧠</div>
            <div class="stat-label">Total Kompetensi</div>
            <div class="stat-value">{{ number_format($totalKompetensi, 0, ',', '.') }}</div>
            <div class="stat-help">Master kompetensi teknis.</div>
        </div>
    </div>

    <div class="panel" style="margin-bottom:20px;">
        <div class="panel-header">
            <h2>Ringkasan Status</h2>
            <p>Angka berikut membantu membaca kondisi data tanpa perlu menebak arti setiap angka.</p>
        </div>
        <div class="stats-grid" style="margin:0; padding:18px;">
            <div class="stat-card">
                <div class="stat-label">Pelatihan sudah dipetakan ke kompetensi</div>
                <div class="stat-value">{{ number_format($pelatihanDenganKompetensi, 0, ',', '.') }}</div>
                <div class="stat-help">Dari {{ number_format($totalPelatihan, 0, ',', '.') }} pelatihan.</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Kompetensi sudah memiliki pelatihan</div>
                <div class="stat-value">{{ number_format($kompetensiTerpakai, 0, ',', '.') }}</div>
                <div class="stat-help">Kompetensi yang sudah terhubung.</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">ASN sudah memiliki riwayat pelatihan</div>
                <div class="stat-value">{{ number_format($asnSudahPelatihan, 0, ',', '.') }}</div>
                <div class="stat-help">Berdasarkan status pelatihan yang tersimpan.</div>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-bottom:20px;">
        <div class="table-toolbar">
            <div>
                <h2>Pelatihan Terbaru</h2>
                <p>Pelatihan yang terakhir masuk ke sistem.</p>
            </div>
            <a href="{{ route('pelatihan.index') }}" class="btn btn-outline btn-small">Lihat semua</a>
        </div>

        @forelse($pelatihanTerbaru as $pelatihan)
            <div class="training-row">
                <div class="training-info">
                    <div class="training-icon">🎓</div>
                    <div>
                        <div class="training-name">{{ $pelatihan->name }}</div>
                        <span class="training-code">{{ $pelatihan->code ? 'Kode: '.$pelatihan->code : 'Kode belum tersedia' }}</span>
                        <div style="display:flex; gap:7px; flex-wrap:wrap; margin-top:7px;">
                            <span class="count-badge">{{ $pelatihan->kompetensi_count }} kompetensi</span>
                            <span class="count-badge">{{ $pelatihan->asns_count }} ASN tercatat</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('pelatihan.show', $pelatihan) }}" class="btn btn-outline btn-small">Detail →</a>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-icon">🎓</div>
                <strong>Belum ada data pelatihan.</strong>
                Tambahkan atau import data pelatihan terlebih dahulu.
            </div>
        @endforelse
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Akses Cepat</h2>
            <p>Menu yang paling sering digunakan.</p>
        </div>
        <div class="quick-grid">
            <a href="{{ route('asn.index') }}" class="quick-card">
                <div><strong>Data ASN</strong><span>Lihat ASN dan kelola status pelatihan.</span></div>
                <strong>→</strong>
            </a>
            <a href="{{ route('laporan.index') }}" class="quick-card"><div><strong>Laporan & Analisis</strong><span>Lihat persentase ASN, pelatihan, dan ringkasan untuk laporan.</span></div><strong>→</strong></a>
            <a href="{{ route('import.index') }}" class="quick-card"><div><strong>Import Excel</strong><span>Perbarui database dari file Excel terbaru.</span></div><strong>→</strong></a>
            <a href="{{ route('kompetensi.index') }}" class="quick-card">
                <div><strong>Data Kompetensi</strong><span>Lihat kompetensi dan pelatihan terkait.</span></div>
                <strong>→</strong>
            </a>
        </div>
    </div>
</div>
@endsection
