@extends('layouts.app')

@section('title', 'Detail Pelatihan - ' . $pelatihan->name)

@section('content')
<div class="page page-narrow">
    <a href="{{ route('pelatihan.index') }}" class="back-link">← Kembali ke Data Pelatihan</a>

    <div class="hero">
        <div class="hero-id">Detail Pelatihan</div>
        <div class="hero-row">
            <div>
                <h1>{{ $pelatihan->name }}</h1>
                <p>{{ $pelatihan->code ? 'Kode pelatihan: '.$pelatihan->code : 'Kode pelatihan belum tersedia pada sumber Excel.' }}</p>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <span class="count-badge">{{ $pelatihan->kompetensi->count() }} kompetensi</span>
                <span class="count-badge">{{ $pelatihanAsns->count() }} ASN tercatat</span>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-bottom:22px;">
        <div class="panel-header">
            <h2>Kompetensi Terkait</h2>
            <p>Kompetensi yang sudah dipetakan ke pelatihan ini.</p>
        </div>
        @forelse($pelatihan->kompetensi as $kompetensi)
            <div class="training-row">
                <div class="training-info">
                    <div class="training-icon">🧠</div>
                    <div>
                        <div class="training-name">{{ $kompetensi->nama }}</div>
                        <span class="training-code">{{ $kompetensi->kode }}{{ $kompetensi->level ? ' · Level '.$kompetensi->level : '' }}</span>
                    </div>
                </div>
                <a href="{{ route('kompetensi.show', $kompetensi) }}" class="btn btn-outline btn-small">Detail</a>
            </div>
        @empty
            <div class="empty-state"><div class="empty-icon">🧠</div><strong>Belum ada kompetensi terkait.</strong>Pelatihan ini belum dipetakan ke kompetensi.</div>
        @endforelse
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>ASN yang Tercatat</h2>
            <p>Daftar ASN yang memiliki catatan pada pelatihan ini. Status ditampilkan agar mudah dibaca.</p>
        </div>
        @forelse($pelatihanAsns as $asn)
            @php $status = \App\Models\AsnTraining::normalizeStatus($asn->pivot->status); $statusClass = $status === 'sudah' ? 'sudah' : ($status === 'diusulkan' ? 'diusulkan' : 'belum'); @endphp
            <div class="training-row">
                <div class="training-info">
                    <div class="training-icon">👤</div>
                    <div>
                        <div class="training-name"><a href="{{ route('asn.show', $asn) }}" style="text-decoration:none;">{{ $asn->name }}</a></div>
                        <span class="training-code">NIP: {{ $asn->nip }}</span>
                    </div>
                </div>
                <span class="status-pill {{ $statusClass }}"><span class="status-dot"></span>{{ \App\Models\AsnTraining::statusLabel($status) }}</span>
            </div>
        @empty
            <div class="empty-state"><div class="empty-icon">👤</div><strong>Belum ada ASN tercatat.</strong>Belum ada data ASN yang terhubung ke pelatihan ini.</div>
        @endforelse
    </div>
</div>
@endsection
