@extends('layouts.app')

@section('title', 'Edit ASN - CKPS Mapping')

@section('content')
<div class="page page-narrow">
    <a href="{{ route('asn.show', $asn) }}" class="back-link">← Kembali ke Detail ASN</a>

    <div class="hero">
        <div class="hero-id">Edit ASN #{{ $asn->id }}</div>
        <h1>Edit Data ASN</h1>
        <p>Perbarui informasi ASN. Riwayat pelatihan dan kompetensi tidak akan terhapus.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-error">
            <strong>Periksa kembali data berikut:</strong>
            <ul class="error-list">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="panel">
        <div class="panel-header">
            <h2>Informasi ASN</h2>
            <p>Ubah data yang diperlukan lalu simpan.</p>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('asn.update', $asn) }}">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="nip">NIP *</label>
                        <input id="nip" name="nip" class="form-control" value="{{ old('nip', $asn->nip) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="name">Nama ASN *</label>
                        <input id="name" name="name" class="form-control" value="{{ old('name', $asn->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="position">Jabatan</label>
                        <input id="position" name="position" class="form-control" value="{{ old('position', $asn->position) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="rank">Pangkat</label>
                        <input id="rank" name="rank" class="form-control" value="{{ old('rank', $asn->rank) }}">
                    </div>
                    <div class="form-group full">
                        <label class="form-label" for="unit_id">Unit Kerja</label>
                        <select id="unit_id" name="unit_id" class="form-control">
                            <option value="">-- Pilih unit kerja --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('unit_id', $asn->unit_id) == $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Simpan Perubahan</button>
                    <a href="{{ route('asn.show', $asn) }}" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
