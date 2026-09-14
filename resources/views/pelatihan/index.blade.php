@extends('layouts.app')

@section('title', 'Data Pelatihan - CKPS Mapping')

@section('content')
<div class="page">
    <div class="page-header">
        <p class="page-eyebrow">Master Data</p>
        <h1 class="page-title">Data Pelatihan</h1>
        <p class="page-description">Daftar seluruh pelatihan yang tersedia beserta jumlah kompetensi dan ASN yang tercatat.</p>
    </div>

    <div class="panel search-panel">
        <form method="GET" action="{{ route('pelatihan.index') }}" class="search-form">
            <input type="text" name="search" value="{{ request('search') }}" class="search-input" placeholder="Cari nama atau kode pelatihan...">
            <button type="submit" class="btn">Cari</button>
            @if(request('search'))
                <a href="{{ route('pelatihan.index') }}" class="btn btn-outline">Reset</a>
            @endif
        </form>
    </div>

    <div class="panel table-panel">
        <div class="table-toolbar">
            <div>
                <h2>Daftar Pelatihan</h2>
                <p><strong>{{ number_format($pelatihan->total(), 0, ',', '.') }}</strong> pelatihan ditemukan.</p>
            </div>
            <div class="table-count">{{ number_format($pelatihan->total(), 0, ',', '.') }} pelatihan</div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Pelatihan</th>
                        <th>Kode</th>
                        <th>Kompetensi Terkait</th>
                        <th>ASN Tercatat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pelatihan as $item)
                        <tr>
                            <td><span class="number-badge">{{ $pelatihan->firstItem() + $loop->index }}</span></td>
                            <td>
                                <span class="primary-text">{{ $item->name }}</span>
                                <span class="secondary-text">Nama pelatihan</span>
                            </td>
                            <td>
                                @if($item->code)
                                    <span class="code-pill">{{ $item->code }}</span>
                                    <span class="secondary-text">Kode internal</span>
                                @else
                                    <span class="secondary-text">Kode belum tersedia</span>
                                @endif
                            </td>
                            <td>
                                <span class="count-badge {{ $item->kompetensi_count == 0 ? 'zero' : '' }}">{{ $item->kompetensi_count }}</span>
                                <span class="secondary-text">kompetensi</span>
                            </td>
                            <td>
                                <span class="count-badge {{ $item->asns_count == 0 ? 'zero' : '' }}">{{ $item->asns_count }}</span>
                                <span class="secondary-text">ASN memiliki catatan</span>
                            </td>
                            <td><a href="{{ route('pelatihan.show', $item) }}" class="btn btn-outline btn-small">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">🎓</div><strong>Data pelatihan tidak ditemukan.</strong>Coba ubah kata pencarian.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pelatihan->hasPages())
            <div class="pagination-bar">
                <div class="pagination-info">Menampilkan <strong>{{ $pelatihan->firstItem() }}</strong>–<strong>{{ $pelatihan->lastItem() }}</strong> dari <strong>{{ $pelatihan->total() }}</strong> pelatihan</div>
                <div class="pagination-links">
                    @if($pelatihan->onFirstPage()) <span class="disabled">‹</span> @else <a href="{{ $pelatihan->previousPageUrl() }}">‹</a> @endif
                    @for($page = 1; $page <= $pelatihan->lastPage(); $page++)
                        @if($page === $pelatihan->currentPage()) <span class="active">{{ $page }}</span> @else <a href="{{ $pelatihan->url($page) }}">{{ $page }}</a> @endif
                    @endfor
                    @if($pelatihan->hasMorePages()) <a href="{{ $pelatihan->nextPageUrl() }}">›</a> @else <span class="disabled">›</span> @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
