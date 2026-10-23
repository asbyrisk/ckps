@extends('layouts.app')

@section('title', 'Kelola Pemetaan - ' . $pelatihan->name)

@section('content')
<div class="page page-narrow">
    <a href="{{ route('pelatihan.show', $pelatihan) }}" class="back-link">← Kembali ke Detail Pelatihan</a>

    <div class="hero">
        <div class="hero-id">Administrasi Pemetaan</div>
        <div class="hero-row">
            <div>
                <h1>{{ $pelatihan->name }}</h1>
                <p>Pilih kompetensi yang relevan dengan pelatihan ini. Tandai kompetensi wajib jika kompetensi tersebut menjadi persyaratan utama.</p>
            </div>
            <span class="count-badge">{{ $selected->count() }} dipetakan</span>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.pelatihan.mapping.update', $pelatihan) }}">
        @csrf
        @method('PUT')

        <div class="panel">
            <div class="panel-header">
                <h2>Kompetensi Terkait</h2>
                <p>Centang kompetensi yang sesuai dengan materi atau tujuan pelatihan.</p>
            </div>

            <div class="mapping-list">
                @forelse($kompetensi as $item)
                    @php
                        $mapped = $selected->get($item->id);
                        $isSelected = (bool) $mapped;
                        $isRequired = $mapped && (bool) $mapped->pivot->wajib;
                    @endphp
                    <label class="mapping-item">
                        <div class="mapping-main">
                            <input type="checkbox" name="kompetensi[]" value="{{ $item->id }}" @checked($isSelected)>
                            <div>
                                <strong>{{ $item->nama }}</strong>
                                <div class="muted">{{ $item->kode }}{{ $item->level ? ' · Level '.$item->level : '' }}</div>
                            </div>
                        </div>
                        <span class="mapping-required">
                            <input type="checkbox" name="wajib[]" value="{{ $item->id }}" @checked($isRequired)>
                            Wajib
                        </span>
                    </label>
                @empty
                    <div class="empty-state">Belum ada kompetensi aktif.</div>
                @endforelse
            </div>

            @error('kompetensi.*')
                <div class="alert alert-error" style="margin:18px;">{{ $message }}</div>
            @enderror

            <div class="form-actions">
                <a href="{{ route('pelatihan.show', $pelatihan) }}" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn">Simpan Pemetaan</button>
            </div>
        </div>
    </form>

    <div class="panel mapping-help">
        <div class="panel-header">
            <h2>Cara membaca pemetaan</h2>
            <p><strong>Kompetensi</strong> adalah kemampuan yang berkaitan dengan pelatihan. <strong>Wajib</strong> berarti kompetensi tersebut menjadi kompetensi utama/persyaratan pelatihan.</p>
        </div>
    </div>
</div>
@endsection
