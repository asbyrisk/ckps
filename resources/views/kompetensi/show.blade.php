@extends('layouts.app')

@section('title', 'Detail Kompetensi - ' . $kompetensi->nama)

@section('content')
<div class="page page-narrow">
    <a href="{{ route('kompetensi.index') }}" class="back-link">← Kembali ke Data Kompetensi</a>

    <div class="hero">
        <div class="hero-id">Detail Kompetensi</div>
        <div class="hero-row">
            <div>
                <span class="code-pill">{{ $kompetensi->kode }}</span>
                <h1 style="margin-top:10px;">{{ $kompetensi->nama }}</h1>
                <p>{{ $kompetensi->level ? 'Level '.$kompetensi->level : 'Level belum diisi' }}</p>
            </div>
            <span class="status-pill {{ $kompetensi->aktif ? 'sudah' : 'belum' }}"><span class="status-dot"></span>{{ $kompetensi->aktif ? 'Aktif' : 'Tidak aktif' }}</span>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item"><div class="info-label">Kode</div><div class="info-value">{{ $kompetensi->kode }}</div></div>
        <div class="info-item"><div class="info-label">Level</div><div class="info-value">{{ $kompetensi->level ?: '-' }}</div></div>
        <div class="info-item"><div class="info-label">Pelatihan</div><div class="info-value">{{ $kompetensi->pelatihans->count() }}</div></div>
        <div class="info-item"><div class="info-label">ASN</div><div class="info-value">{{ $kompetensi->asns->count() }}</div></div>
    </div>

    <div class="panel" style="margin-bottom:22px;">
        <div class="panel-header">
            <h2>Pelatihan Terkait</h2>
            <p>Pelatihan yang sudah dipetakan ke kompetensi ini.</p>
        </div>
        @forelse($kompetensi->pelatihans as $pelatihan)
            <div class="training-row">
                <div class="training-info">
                    <div class="training-icon">🎓</div>
                    <div>
                        <div class="training-name">{{ $pelatihan->name }}</div>
                        <span class="training-code">{{ $pelatihan->code ? 'Kode: '.$pelatihan->code : 'Tanpa kode' }}</span>
                    </div>
                </div>
                <a href="{{ route('pelatihan.show', $pelatihan) }}" class="btn btn-outline btn-small">Detail</a>
            </div>
        @empty
            <div class="empty-state"><div class="empty-icon">🎓</div><strong>Belum ada pelatihan terkait.</strong>Kompetensi ini belum dipetakan ke pelatihan.</div>
        @endforelse
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>ASN dengan Kompetensi Ini</h2>
            <p>ASN yang memiliki kompetensi ini dalam data sistem.</p>
        </div>
        @forelse($kompetensi->asns as $asn)
            <div class="training-row">
                <div class="training-info">
                    <div class="training-icon">👤</div>
                    <div>
                        <div class="training-name">{{ $asn->name }}</div>
                        <span class="training-code">NIP: {{ $asn->nip }}{{ $asn->position ? ' · '.$asn->position : '' }}</span>
                    </div>
                </div>
                <a href="{{ route('asn.show', $asn) }}" class="btn btn-outline btn-small">Detail ASN</a>
            </div>
        @empty
            <div class="empty-state"><div class="empty-icon">👤</div><strong>Belum ada ASN.</strong>Belum ada ASN yang terhubung dengan kompetensi ini.</div>
        @endforelse
    </div>
</div>
@endsection
