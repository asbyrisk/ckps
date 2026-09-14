@extends('layouts.app')

@section('title', 'Data Kompetensi - CKPS Mapping')

@section('content')
<div class="page">
    <div class="page-header">
        <p class="page-eyebrow">Master Data</p>
        <h1 class="page-title">Data Kompetensi</h1>
        <p class="page-description">Daftar kompetensi teknis beserta pelatihan dan ASN yang terhubung.</p>
    </div>

    <div class="panel search-panel">
        <form method="GET" action="{{ route('kompetensi.index') }}" class="search-form">
            <input type="text" name="search" value="{{ request('search') }}" class="search-input" placeholder="Cari kode, nama kompetensi, atau level...">
            <button type="submit" class="btn">Cari</button>
            @if(request('search')) <a href="{{ route('kompetensi.index') }}" class="btn btn-outline">Reset</a> @endif
        </form>
    </div>

    <div class="panel table-panel">
        <div class="table-toolbar">
            <div>
                <h2>Daftar Kompetensi</h2>
                <p><strong>{{ number_format($kompetensi->total(), 0, ',', '.') }}</strong> kompetensi terdaftar.</p>
            </div>
            <div class="table-count">{{ number_format($kompetensi->total(), 0, ',', '.') }} kompetensi</div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Kompetensi</th>
                        <th>Level</th>
                        <th>Pelatihan</th>
                        <th>ASN</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kompetensi as $item)
                        <tr>
                            <td><span class="number-badge">{{ $kompetensi->firstItem() + $loop->index }}</span></td>
                            <td><span class="code-pill">{{ $item->kode }}</span></td>
                            <td><span class="primary-text">{{ $item->nama }}</span></td>
                            <td>{{ $item->level ?: '-' }}</td>
                            <td><span class="count-badge {{ $item->pelatihans_count == 0 ? 'zero' : '' }}">{{ $item->pelatihans_count }}</span></td>
                            <td><span class="count-badge {{ $item->asns_count == 0 ? 'zero' : '' }}">{{ $item->asns_count }}</span></td>
                            <td>
                                <span class="status-pill {{ $item->aktif ? 'sudah' : 'belum' }}">
                                    <span class="status-dot"></span>{{ $item->aktif ? 'Aktif' : 'Tidak aktif' }}
                                </span>
                            </td>
                            <td><a href="{{ route('kompetensi.show', $item) }}" class="btn btn-outline btn-small">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">🧠</div><strong>Kompetensi tidak ditemukan.</strong>Coba ubah kata pencarian.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($kompetensi->hasPages())
            <div class="pagination-bar">
                <div class="pagination-info">Menampilkan <strong>{{ $kompetensi->firstItem() }}</strong>–<strong>{{ $kompetensi->lastItem() }}</strong> dari <strong>{{ $kompetensi->total() }}</strong> kompetensi</div>
                <div class="pagination-links">
                    @if($kompetensi->onFirstPage()) <span class="disabled">‹</span> @else <a href="{{ $kompetensi->previousPageUrl() }}">‹</a> @endif
                    @for($page = 1; $page <= $kompetensi->lastPage(); $page++)
                        @if($page === $kompetensi->currentPage()) <span class="active">{{ $page }}</span> @else <a href="{{ $kompetensi->url($page) }}">{{ $page }}</a> @endif
                    @endfor
                    @if($kompetensi->hasMorePages()) <a href="{{ $kompetensi->nextPageUrl() }}">›</a> @else <span class="disabled">›</span> @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
