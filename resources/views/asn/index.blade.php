@extends('layouts.app')

@section('title', 'Data ASN - CKPS Mapping')

@section('content')
<div class="page">
    <div class="page-header">
        <div class="page-header-row">
            <div>
                <p class="page-eyebrow">Data Utama</p>
                <h1 class="page-title">Data ASN</h1>
                <p class="page-description">Cari ASN dan lihat ringkasan pelatihan serta kompetensinya.</p>
            </div>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('asn.create') }}" class="btn">+ Tambah ASN</a>
        @endif
        </div>
    </div>

    <div class="panel search-panel">
        <form method="GET" action="{{ route('asn.index') }}" class="search-form">
            <input type="text" name="search" value="{{ request('search') }}" class="search-input" placeholder="Cari NIP, nama, jabatan, pangkat, atau unit kerja...">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <button type="submit" class="btn">Cari</button>
            @if(request('search') || request('status'))
                <a href="{{ route('asn.index') }}" class="btn btn-outline">Reset</a>
            @endif
        </form>
    </div>

    <div class="panel" style="margin-bottom:20px;">
        <div class="table-toolbar">
            <div>
                <h2>Filter status pelatihan</h2>
                <p>Pilih filter sesuai kebutuhan. Status di sini berasal dari riwayat pelatihan ASN.</p>
            </div>
            <div class="table-count">{{ number_format($asn->total(), 0, ',', '.') }} ASN</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap; padding:16px 20px;">
            <a href="{{ route('asn.index', request()->except('status')) }}" class="btn btn-small {{ !request('status') ? '' : 'btn-outline' }}">Semua</a>
            <a href="{{ route('asn.index', array_merge(request()->except('status'), ['status'=>'sudah'])) }}" class="btn btn-small {{ request('status') === 'sudah' ? '' : 'btn-outline' }}">✓ Sudah mengikuti</a>
            <a href="{{ route('asn.index', array_merge(request()->except('status'), ['status'=>'belum'])) }}" class="btn btn-small {{ request('status') === 'belum' ? '' : 'btn-outline' }}">○ Belum / masih perlu dicek</a>
            <a href="{{ route('asn.index', array_merge(request()->except('status'), ['status'=>'diusulkan'])) }}" class="btn btn-small {{ request('status') === 'diusulkan' ? '' : 'btn-outline' }}">↗ Diusulkan</a>
        </div>
    </div>

    <div class="panel table-panel">
        <div class="table-toolbar">
            <div>
                <h2>Daftar ASN</h2>
                <p>Lihat kompetensi ASN dan nama pelatihan yang sesuai berdasarkan pemetaan kompetensi.</p>
            </div>
            <div class="table-count">{{ number_format($asn->total(), 0, ',', '.') }} hasil</div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ASN</th>
                        <th>Jabatan</th>
                        <th>Pangkat</th>
                        <th>Unit Kerja</th>
                        <th>Kompetensi</th>
                        <th>Pelatihan yang Sesuai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asn as $item)
                        <tr>
                            <td><span class="number-badge">{{ $asn->firstItem() + $loop->index }}</span></td>
                            <td>
                                <span class="primary-text">{{ $item->name }}</span>
                                <span class="secondary-text">NIP: {{ $item->nip ?: '-' }}</span>
                            </td>
                            <td>{{ $item->position ?: '-' }}</td>
                            <td>{{ $item->rank ?: '-' }}</td>
                            <td>{{ $item->unit?->name ?: '-' }}</td>
                            <td>
                                @if($item->competencies->isNotEmpty())
                                    <div class="tag-list">
                                        @foreach($item->competencies as $competency)
                                            <span class="mapping-tag">{{ $competency->nama }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="secondary-text">Belum ada kompetensi</span>
                                @endif
                            </td>
                            <td>
                                @if($item->mapped_trainings->isNotEmpty())
                                    <div class="training-match-list">
                                        @foreach($item->mapped_trainings as $training)
                                            <span class="training-match">🎓 {{ $training->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="secondary-text">Belum ada pelatihan yang terhubung dengan kompetensi</span>
                                @endif
                            </td>
                            <td><a href="{{ route('asn.show', $item) }}" class="btn btn-outline btn-small">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-icon">👤</div>
                                    <strong>Data ASN tidak ditemukan.</strong>
                                    Coba ubah kata pencarian atau reset filter.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($asn->hasPages())
            <div class="pagination-bar">
                <div class="pagination-info">
                    Menampilkan <strong>{{ $asn->firstItem() }}</strong>–<strong>{{ $asn->lastItem() }}</strong> dari <strong>{{ $asn->total() }}</strong> ASN
                </div>
                <div class="pagination-links">
                    @if($asn->onFirstPage())
                        <span class="disabled">‹</span>
                    @else
                        <a href="{{ $asn->previousPageUrl() }}">‹</a>
                    @endif
                    @php
                        $lastPage = $asn->lastPage();
                        $currentPage = $asn->currentPage();
                        $startPage = max(1, min($currentPage - 2, $lastPage - 4));
                        $endPage = min($lastPage, $startPage + 4);
                    @endphp

                    @for($page = $startPage; $page <= $endPage; $page++)
                        @if($page === $currentPage)
                            <span class="active">{{ $page }}</span>
                        @else
                            <a href="{{ $asn->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor
                    @if($asn->hasMorePages())
                        <a href="{{ $asn->nextPageUrl() }}">›</a>
                    @else
                        <span class="disabled">›</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
