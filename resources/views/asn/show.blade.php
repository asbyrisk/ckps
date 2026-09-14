@extends('layouts.app')

@section('title', 'Detail ASN - ' . $asn->name)

@section('content')
<div class="page page-narrow">
    <a href="{{ route('asn.index') }}" class="back-link">← Kembali ke Data ASN</a>

    <div class="hero">
        <div class="hero-row">
            <div>
                <div class="hero-id">Detail ASN #{{ $asn->id }}</div>
                <h1>{{ $asn->name }}</h1>
                <p>Kelola informasi ASN dan status keikutsertaan pada setiap pelatihan.</p>
            </div>
            <a href="{{ route('asn.edit', $asn) }}" class="btn">✎ Edit Data ASN</a>
        </div>
    </div>

    <div class="panel" style="margin-bottom:22px;">
        <div class="panel-header">
            <h2>Informasi ASN</h2>
            <p>Informasi dasar yang digunakan dalam pemetaan.</p>
        </div>
        <div class="panel-body">
            <div class="info-grid" style="margin:0;">
                <div class="info-item">
                    <div class="info-label">NIP</div>
                    <div class="info-value">{{ $asn->nip ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Jabatan</div>
                    <div class="info-value">{{ $asn->position ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pangkat</div>
                    <div class="info-value">{{ $asn->rank ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Unit Kerja</div>
                    <div class="info-value">{{ $asn->unit?->name ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-bottom:22px;">
        <div class="panel-header">
            <h2>Penugasan / Perpindahan Unit Kerja</h2>
            <p>Pindahkan ASN ke unit kerja baru tanpa menghapus riwayat unit sebelumnya.</p>
        </div>
        <div class="panel-body">
            <div class="info-grid" style="margin:0 0 18px;">
                <div class="info-item">
                    <div class="info-label">Unit kerja saat ini</div>
                    <div class="info-value">{{ $asn->unit?->name ?: 'Belum ada unit' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tujuan perpindahan</div>
                    <div class="info-value">Pilih BPBPK/unit kerja baru di bawah.</div>
                </div>
            </div>
            <form method="POST" action="{{ route('asn.unit.transfer', $asn) }}" class="form-grid">
                @csrf
                <div class="form-group">
                    <label for="unit_id">Unit kerja tujuan</label>
                    <select id="unit_id" name="unit_id" class="form-control" required>
                        <option value="">Pilih unit kerja...</option>
                        @foreach(\App\Models\Unit::query()->orderBy('name')->get() as $unit)
                            <option value="{{ $unit->id }}" {{ $asn->unit_id == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="effective_date">Tanggal berlaku</label>
                    <input id="effective_date" type="date" name="effective_date" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                <div class="form-group">
                    <label for="reason">Jenis perubahan</label>
                    <select id="reason" name="reason" class="form-control">
                        <option>Penugasan</option>
                        <option>Mutasi</option>
                        <option>Penempatan kembali</option>
                        <option>Diusulkan</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label for="note">Catatan</label>
                    <textarea id="note" name="note" class="form-control" rows=3 placeholder="Contoh: dipindahkan dari BPBPK Jawa Barat ke BPBPK Yogyakarta."></textarea>
                </div>
                <div style="grid-column:1/-1;">
                    <button type="submit" class="btn">Pindahkan ASN ke unit baru</button>
                </div>
            </form>
        </div>
        @if($asn->unitHistories->isNotEmpty())
            <div class="training-list">
                @foreach($asn->unitHistories as $history)
                    @php
                        $historyActive = ($history->status ?: 'aktif') === 'aktif';
                    @endphp
                    <div class="training-row history-row {{ $historyActive ? '' : 'history-canceled' }}">
                        <div class="training-info">
                            <div class="training-icon">↔</div>
                            <div>
                                <div class="training-name">
                                    {{ $history->fromUnit?->name ?: 'Belum ada unit' }} → {{ $history->toUnit?->name ?: 'Belum ada unit' }}
                                </div>
                                <span class="training-code">
                                    {{ optional($history->effective_date)->format('d/m/Y') }} · {{ $history->reason ?: 'Perubahan unit' }}
                                </span>
                                <span class="status-pill {{ $historyActive ? 'history-active' : 'history-canceled-pill' }}">
                                    <span class="status-dot"></span>
                                    {{ $historyActive ? 'Aktif' : 'Dibatalkan' }}
                                </span>
                                @if(!$historyActive && $history->cancellation_reason)
                                    <span class="secondary-text">Koreksi: {{ $history->cancellation_reason }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="history-actions">
                            @if($history->note)
                                <div class="secondary-text history-note">{{ $history->note }}</div>
                            @endif
                            @if($historyActive)
                                <button
                                    type="button"
                                    class="btn btn-danger btn-small cancel-history-btn"
                                    data-url="{{ route('asn.unit.history.cancel', [$asn, $history]) }}"
                                    data-from="{{ $history->fromUnit?->name ?: 'Belum ada unit' }}"
                                    data-to="{{ $history->toUnit?->name ?: 'Belum ada unit' }}"
                                >
                                    ↶ Koreksi
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="panel" style="margin-bottom:22px;">
        <div class="panel-header">
            <div class="page-header-row" style="align-items:center;">
                <div>
                    <h2>Pelatihan ASN</h2>
                    <p>Setiap pelatihan memiliki status <strong>Sudah mengikuti</strong>, <strong>Belum mengikuti</strong>, atau <strong>Diusulkan</strong>.</p>
                </div>
                <span class="table-count">{{ $trainings->count() }} pelatihan</span>
            </div>
        </div>

        <div class="filter-row">
            <input type="text" id="trainingSearch" class="search-input" placeholder="Cari nama pelatihan..." aria-label="Cari pelatihan">
        </div>

        <div class="training-list" id="trainingList">
            @forelse($trainings as $training)
                @php
                    $status = $trainingStatus->get($training->id, 'belum');
                    $isSudah = $status === 'sudah';
                    $isDiusulkan = $status === 'diusulkan';
                @endphp
                <div class="training-row" data-training-name="{{ strtolower($training->name) }}">
                    <div class="training-info">
                        <div class="training-icon">🎓</div>
                        <div>
                            <div class="training-name">{{ $training->name }}</div>
                            <span class="training-code">{{ $training->code ? 'Kode: '.$training->code : 'Master pelatihan' }}</span>
                        </div>
                    </div>
                    <div class="training-actions">
                        <span class="status-pill {{ $isSudah ? 'sudah' : ($isDiusulkan ? 'diusulkan' : 'belum') }}">
                            <span class="status-dot"></span>
                            {{ $isSudah ? 'Sudah mengikuti' : ($isDiusulkan ? 'Diusulkan' : 'Belum mengikuti') }}
                        </span>
                        <button
                            type="button"
                            class="btn btn-outline btn-small edit-training-btn"
                            data-id="{{ $training->id }}"
                            data-name="{{ $training->name }}"
                        >
                            ✎ Ubah
                        </button>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-icon">🎓</div>
                    <strong>Belum ada master pelatihan.</strong>
                    Tambahkan data pelatihan terlebih dahulu.
                </div>
            @endforelse
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Kompetensi ASN</h2>
            <p>Kompetensi yang sudah terhubung dengan ASN ini.</p>
        </div>
        @forelse($asn->competencies as $competency)
            <div class="training-row">
                <div class="training-info">
                    <div class="training-icon">🧠</div>
                    <div>
                        <div class="training-name">{{ $competency->nama }}</div>
                        <span class="training-code">
                            {{ $competency->kode }}{{ $competency->level ? ' · Level '.$competency->level : '' }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-icon">🧠</div>
                <strong>Belum ada kompetensi.</strong>
                ASN ini belum memiliki kompetensi yang terhubung.
            </div>
        @endforelse
    </div>
</div>

<div id="cancelHistoryModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="cancelHistoryTitle">
        <div class="modal-header">
            <div>
                <h3 id="cancelHistoryTitle">Koreksi perpindahan ASN</h3>
                <p id="cancelHistoryDescription">Perpindahan akan dibatalkan dan ASN dikembalikan ke unit sebelumnya.</p>
            </div>
            <button type="button" id="closeCancelHistoryModal" class="modal-close">✕</button>
        </div>
        <div class="modal-body">
            <div class="correction-warning">
                <strong>Perhatian</strong>
                <span>Riwayat tidak dihapus. Sistem hanya menandainya sebagai <strong>Dibatalkan</strong> agar jejak koreksi tetap tersimpan.</span>
            </div>
            <form id="cancelHistoryForm" method="POST">
                @csrf
                <div class="form-group" style="margin-top:16px;">
                    <label for="cancellation_reason">Alasan koreksi</label>
                    <textarea id="cancellation_reason" name="cancellation_reason" class="form-control" rows=3 required placeholder="Contoh: Salah memilih unit tujuan."></textarea>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
                    <button type="button" id="closeCancelHistoryModal2" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-danger">Batalkan & kembalikan unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="trainingModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTrainingName">
        <div class="modal-header">
            <div>
                <h3 id="modalTrainingName">Ubah status</h3>
                <p>Pilih status keikutsertaan ASN pada pelatihan ini.</p>
            </div>
            <button type="button" id="closeTrainingModal" class="modal-close">✕</button>
        </div>
        <div class="modal-body">
            <form id="trainingStatusForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-choice-grid">
                    <button type="submit" name="status" value="sudah" class="choice-button sudah">✓<br>Sudah mengikuti</button>
                    <button type="submit" name="status" value="belum" class="choice-button belum">○<br>Belum mengikuti</button>
                    <button type="submit" name="status" value="diusulkan" class="choice-button diusulkan">↗<br>Diusulkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('trainingSearch');
    const rows = document.querySelectorAll('.training-row[data-training-name]');
    const modal = document.getElementById('trainingModal');
    const modalName = document.getElementById('modalTrainingName');
    const form = document.getElementById('trainingStatusForm');
    const close = document.getElementById('closeTrainingModal');

    search?.addEventListener('input', function () {
        const keyword = this.value.trim().toLowerCase();
        rows.forEach(row => {
            row.style.display = !keyword || row.dataset.trainingName.includes(keyword) ? '' : 'none';
        });
    });

    document.querySelectorAll('.edit-training-btn').forEach(button => {
        button.addEventListener('click', function () {
            modalName.textContent = this.dataset.name;
            form.action = @json(url('/asn/'.$asn->id.'/pelatihan')) + '/' + this.dataset.id + '/status';
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    close?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
            closeCancelHistoryModal();
        }
    });

    const cancelModal = document.getElementById('cancelHistoryModal');
    const cancelForm = document.getElementById('cancelHistoryForm');
    const cancelReason = document.getElementById('cancellation_reason');
    const closeCancel = document.getElementById('closeCancelHistoryModal');
    const closeCancel2 = document.getElementById('closeCancelHistoryModal2');

    function closeCancelHistoryModal() {
        cancelModal?.classList.remove('open');
        cancelModal?.setAttribute('aria-hidden', 'true');
        if (cancelReason) cancelReason.value = '';
    }

    document.querySelectorAll('.cancel-history-btn').forEach(button => {
        button.addEventListener('click', function () {
            const from = this.dataset.from || 'unit sebelumnya';
            const to = this.dataset.to || 'unit tujuan';
            cancelForm.action = this.dataset.url;
            document.getElementById('cancelHistoryDescription').textContent =
                `Perpindahan ${from} → ${to} akan dibatalkan dan ASN dikembalikan ke unit sebelumnya.`;
            cancelModal.classList.add('open');
            cancelModal.setAttribute('aria-hidden', 'false');
            setTimeout(() => cancelReason?.focus(), 50);
        });
    });

    closeCancel?.addEventListener('click', closeCancelHistoryModal);
    closeCancel2?.addEventListener('click', closeCancelHistoryModal);
    cancelModal?.addEventListener('click', function (event) {
        if (event.target === cancelModal) closeCancelHistoryModal();
    });
});
</script>
@endpush
