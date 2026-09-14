@extends('layouts.app')

@section('title', 'Tambah ASN - CKPS Mapping')

@section('content')
<div class="page page-narrow">
    <a href="{{ route('asn.index') }}" class="back-link">← Kembali ke Data ASN</a>

    <div class="hero">
        <div class="hero-id">Data ASN</div>
        <h1>Tambah ASN</h1>
        <p>Masukkan data dasar ASN. Setelah disimpan, status pelatihan dapat dikelola dari halaman detail ASN.</p>
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
            <p>Kolom bertanda * wajib diisi.</p>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('asn.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="nip">NIP *</label>
                        <input id="nip" name="nip" class="form-control" value="{{ old('nip') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="name">Nama ASN *</label>
                        <input id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="position">Jabatan</label>
                        <input id="position" name="position" class="form-control" value="{{ old('position') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="rank">Pangkat</label>
                        <input id="rank" name="rank" class="form-control" value="{{ old('rank') }}">
                    </div>
                    <div class="form-group full">
                        <label class="form-label" for="unit_id">Unit Kerja</label>
                        <select id="unit_id" name="unit_id" class="form-control">
                            <option value="">-- Pilih unit kerja --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Simpan ASN</button>
                    <a href="{{ route('asn.index') }}" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
