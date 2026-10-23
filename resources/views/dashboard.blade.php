@extends('layouts.app')

@section('title', 'Dashboard Admin - CKPS Mapping')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $trainingCoverage = $totalPelatihan > 0 ? round(($pelatihanDenganKompetensi / $totalPelatihan) * 100) : 0;
    $competencyCoverage = $totalKompetensi > 0 ? round(($kompetensiTerpakai / $totalKompetensi) * 100) : 0;
    $asnCompetencyCoverage = $totalAsn > 0 ? round(($asnMemilikiKompetensi / $totalAsn) * 100) : 0;
    $asnTrainingCoverage = $totalAsn > 0 ? round(($asnSudahPelatihan / $totalAsn) * 100) : 0;
    $overallCoverage = round(($trainingCoverage + $competencyCoverage + $asnCompetencyCoverage) / 3);
    $healthClass = $overallCoverage >= 80 ? 'is-good' : ($overallCoverage >= 50 ? 'is-warning' : 'is-danger');
    $healthLabel = $overallCoverage >= 80 ? 'Pemetaan baik' : ($overallCoverage >= 50 ? 'Perlu perhatian' : 'Perlu pemetaan');
@endphp

<div class="page admin-dashboard">
    <section class="dashboard-hero">
        <div class="dashboard-hero-copy">
            <span class="dashboard-kicker">CKPS MAPPING • ADMINISTRATOR</span>
            <h1>Dashboard Pemetaan Kompetensi ASN</h1>
            <p>Pantau kesiapan data ASN, kompetensi, pelatihan, dan hubungan pemetaannya dalam satu tampilan.</p>
        </div>
        <div class="dashboard-hero-actions">
            <a href="{{ route('import.index') }}" class="dashboard-primary-action">Import Excel</a>
            <a href="{{ route('pelatihan.index') }}" class="dashboard-secondary-action">Kelola Pemetaan</a>
        </div>
    </section>

    <section class="dashboard-kpi-grid">
        <div class="dashboard-kpi">
            <div class="dashboard-kpi-top"><span>ASN</span><span class="dashboard-kpi-icon">👤</span></div>
            <strong>{{ number_format($totalAsn, 0, ',', '.') }}</strong>
            <small>Total ASN terdaftar</small>
        </div>
        <div class="dashboard-kpi">
            <div class="dashboard-kpi-top"><span>PELATIHAN</span><span class="dashboard-kpi-icon">🎓</span></div>
            <strong>{{ number_format($totalPelatihan, 0, ',', '.') }}</strong>
            <small>{{ number_format($pelatihanDenganKompetensi, 0, ',', '.') }} sudah dipetakan</small>
        </div>
        <div class="dashboard-kpi">
            <div class="dashboard-kpi-top"><span>KOMPETENSI</span><span class="dashboard-kpi-icon">🧠</span></div>
            <strong>{{ number_format($totalKompetensi, 0, ',', '.') }}</strong>
            <small>{{ number_format($kompetensiTerpakai, 0, ',', '.') }} sudah terhubung</small>
        </div>
        <div class="dashboard-kpi dashboard-kpi-highlight">
            <div class="dashboard-kpi-top"><span>PEMETAAN</span><span class="dashboard-kpi-icon">🔗</span></div>
            <strong>{{ number_format($totalPemetaan, 0, ',', '.') }}</strong>
            <small>{{ number_format($pemetaanWajib, 0, ',', '.') }} kompetensi wajib</small>
        </div>
    </section>

    <section class="dashboard-section-heading">
        <div><span class="dashboard-section-kicker">RINGKASAN KONDISI</span><h2>Seberapa siap pemetaan saat ini?</h2></div>
        <div class="dashboard-health {{ $healthClass }}"><span></span>{{ $healthLabel }} · {{ $overallCoverage }}%</div>
    </section>

    <section class="dashboard-main-grid">
        <div class="dashboard-card dashboard-coverage-card">
            <div class="dashboard-card-heading"><div><h3>Indikator Pemetaan</h3><p>Persentase keterhubungan data yang menjadi dasar rekomendasi pelatihan.</p></div></div>
            <div class="dashboard-coverage-list">
                @foreach([
                    ['label'=>'Pelatihan memiliki kompetensi','value'=>$trainingCoverage,'detail'=>$pelatihanDenganKompetensi.' dari '.$totalPelatihan.' pelatihan'],
                    ['label'=>'Kompetensi terhubung pelatihan','value'=>$competencyCoverage,'detail'=>$kompetensiTerpakai.' dari '.$totalKompetensi.' kompetensi'],
                    ['label'=>'ASN memiliki kompetensi','value'=>$asnCompetencyCoverage,'detail'=>$asnMemilikiKompetensi.' dari '.$totalAsn.' ASN'],
                    ['label'=>'ASN sudah mengikuti pelatihan','value'=>$asnTrainingCoverage,'detail'=>$asnSudahPelatihan.' dari '.$totalAsn.' ASN'],
                ] as $metric)
                    <div class="dashboard-meter">
                        <div class="dashboard-meter-head"><strong>{{ $metric['label'] }}</strong><b>{{ $metric['value'] }}%</b></div>
                        <div class="dashboard-meter-track"><span style="width: {{ min(100, max(0, $metric['value'])) }}%"></span></div>
                        <small>{{ $metric['detail'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="dashboard-card dashboard-attention-card">
            <div class="dashboard-card-heading"><div><h3>Prioritas Admin</h3><p>Data yang paling membutuhkan tindak lanjut.</p></div></div>
            <div class="dashboard-attention-list">
                <a href="{{ route('pelatihan.index') }}" class="dashboard-attention-item">
                    <span class="dashboard-attention-number danger">{{ number_format($pelatihanTanpaKompetensi, 0, ',', '.') }}</span>
                    <span><strong>Pelatihan belum dipetakan</strong><small>Tambahkan kompetensi sasaran.</small></span><b>→</b>
                </a>
                <a href="{{ route('kompetensi.index') }}" class="dashboard-attention-item">
                    <span class="dashboard-attention-number warning">{{ number_format($kompetensiBelumTerpakai, 0, ',', '.') }}</span>
                    <span><strong>Kompetensi belum terhubung</strong><small>Belum memiliki pelatihan terkait.</small></span><b>→</b>
                </a>
                <a href="{{ route('asn.index') }}" class="dashboard-attention-item">
                    <span class="dashboard-attention-number neutral">{{ number_format($asnBelumMemilikiKompetensi, 0, ',', '.') }}</span>
                    <span><strong>ASN belum punya kompetensi</strong><small>Perlu dilengkapi untuk rekomendasi.</small></span><b>→</b>
                </a>
                <a href="{{ route('asn.index') }}" class="dashboard-attention-item">
                    <span class="dashboard-attention-number neutral">{{ number_format($asnBelumPelatihan, 0, ',', '.') }}</span>
                    <span><strong>ASN belum punya riwayat pelatihan</strong><small>Periksa data pelatihan ASN.</small></span><b>→</b>
                </a>
            </div>
        </div>
    </section>


    <section class="dashboard-flow-card dashboard-card">
        <div class="dashboard-card-heading">
            <div>
                <span class="dashboard-section-kicker">MODEL DATA</span>
                <h3>Alur Pemetaan ASN → Kompetensi → Pelatihan</h3>
                <p>Gambaran hubungan utama data yang digunakan sistem untuk menghasilkan rekomendasi pelatihan.</p>
            </div>
        </div>
        <div class="dashboard-flow">
            <div class="dashboard-flow-node">
                <span class="dashboard-flow-icon">👤</span>
                <div><small>ASN</small><strong>{{ number_format($totalAsn, 0, ',', '.') }}</strong><em>data ASN</em></div>
            </div>
            <span class="dashboard-flow-arrow">→</span>
            <div class="dashboard-flow-node">
                <span class="dashboard-flow-icon">🧠</span>
                <div><small>KOMPETENSI</small><strong>{{ number_format($totalKompetensi, 0, ',', '.') }}</strong><em>{{ number_format($kompetensiTerpakai, 0, ',', '.') }} terhubung</em></div>
            </div>
            <span class="dashboard-flow-arrow">→</span>
            <div class="dashboard-flow-node">
                <span class="dashboard-flow-icon">🎓</span>
                <div><small>PELATIHAN</small><strong>{{ number_format($totalPelatihan, 0, ',', '.') }}</strong><em>{{ number_format($pelatihanDenganKompetensi, 0, ',', '.') }} terpetakan</em></div>
            </div>
        </div>
    </section>

    <section class="dashboard-chart-grid">
        <div class="dashboard-card dashboard-chart-card">
            <div class="dashboard-card-heading"><div><span class="dashboard-section-kicker">DISTRIBUSI ASN</span><h3>ASN per Unit Kerja</h3><p>8 unit dengan jumlah ASN terbanyak.</p></div></div>
            <div class="dashboard-chart-list">
                @php $maxUnit = max(1, $asnPerUnit->max('asns_count')); @endphp
                @forelse($asnPerUnit as $item)
                    <div class="dashboard-chart-row">
                        <div class="dashboard-chart-label"><span>{{ $loop->iteration }}</span><strong>{{ $item->name }}</strong><b>{{ number_format($item->asns_count, 0, ',', '.') }}</b></div>
                        <div class="dashboard-chart-track"><i style="width: {{ round(($item->asns_count / $maxUnit) * 100) }}%"></i></div>
                    </div>
                @empty
                    <div class="dashboard-empty">Belum ada data unit kerja.</div>
                @endforelse
            </div>
        </div>

        <div class="dashboard-card dashboard-chart-card">
            <div class="dashboard-card-heading"><div><span class="dashboard-section-kicker">STATUS PELATIHAN</span><h3>Status Riwayat Pelatihan</h3><p>Distribusi status pada data riwayat pelatihan.</p></div></div>
            <div class="dashboard-status-chart">
                @php $maxStatus = max(1, $statusPelatihan->max('total')); @endphp
                @forelse($statusPelatihan as $item)
                    @php $statusLabel = ucfirst(str_replace('_', ' ', $item->status ?: 'Belum diisi')); @endphp
                    <div class="dashboard-status-row">
                        <div><strong>{{ $statusLabel }}</strong><b>{{ number_format($item->total, 0, ',', '.') }}</b></div>
                        <div class="dashboard-chart-track"><i style="width: {{ round(($item->total / $maxStatus) * 100) }}%"></i></div>
                    </div>
                @empty
                    <div class="dashboard-empty">Belum ada riwayat pelatihan.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="dashboard-analytics-grid">
        <div class="dashboard-card">
            <div class="dashboard-card-heading"><div><h3>Pelatihan dengan Pemetaan Terbanyak</h3><p>Jumlah kompetensi yang dipetakan pada setiap pelatihan.</p></div><a href="{{ route('pelatihan.index') }}">Lihat semua →</a></div>
            <div class="dashboard-rank-list">
                @forelse($pelatihanPalingTerpetakan as $item)
                    @php $max = max(1, $pelatihanPalingTerpetakan->max('kompetensi_count')); $width = round(($item->kompetensi_count/$max)*100); @endphp
                    <div class="dashboard-rank-item"><div class="dashboard-rank-label"><span>{{ $loop->iteration }}</span><strong>{{ $item->name }}</strong><b>{{ $item->kompetensi_count }}</b></div><div class="dashboard-mini-track"><i style="width:{{ $width }}%"></i></div></div>
                @empty
                    <div class="dashboard-empty">Belum ada pemetaan kompetensi pada pelatihan.</div>
                @endforelse
            </div>
        </div>
        <div class="dashboard-card">
            <div class="dashboard-card-heading"><div><h3>Kompetensi Paling Banyak Dimiliki</h3><p>Kompetensi dengan jumlah ASN terbanyak.</p></div><a href="{{ route('kompetensi.index') }}">Lihat semua →</a></div>
            <div class="dashboard-rank-list">
                @forelse($kompetensiPalingDimiliki as $item)
                    @php $max = max(1, $kompetensiPalingDimiliki->max('asns_count')); $width = round(($item->asns_count/$max)*100); @endphp
                    <div class="dashboard-rank-item"><div class="dashboard-rank-label"><span>{{ $loop->iteration }}</span><strong>{{ $item->nama }}</strong><b>{{ number_format($item->asns_count, 0, ',', '.') }}</b></div><div class="dashboard-mini-track"><i style="width:{{ $width }}%"></i></div></div>
                @empty
                    <div class="dashboard-empty">Belum ada kompetensi yang dimiliki ASN.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="dashboard-card dashboard-recent-card">
        <div class="dashboard-card-heading"><div><span class="dashboard-section-kicker">AKTIVITAS DATA</span><h3>Pelatihan Terbaru</h3><p>Data pelatihan yang terakhir ditambahkan ke sistem.</p></div><a href="{{ route('pelatihan.index') }}">Buka daftar pelatihan →</a></div>
        <div class="dashboard-training-list">
            @forelse($pelatihanTerbaru as $item)
                <a href="{{ route('pelatihan.show', $item) }}" class="dashboard-training-item"><span class="dashboard-training-icon">🎓</span><span class="dashboard-training-copy"><strong>{{ $item->name }}</strong><small>{{ $item->code ? 'Kode '.$item->code.' · ' : '' }}{{ $item->kompetensi_count }} kompetensi · {{ $item->asns_count }} ASN</small></span><span class="dashboard-training-arrow">→</span></a>
            @empty
                <div class="dashboard-empty">Belum ada data pelatihan.</div>
            @endforelse
        </div>
    </section>

    <section class="dashboard-footer-grid">
        <a href="{{ route('asn.index') }}" class="dashboard-action-card"><span>👤</span><div><strong>Kelola Data ASN</strong><small>Periksa kompetensi dan pelatihan setiap ASN.</small></div><b>→</b></a>
        <a href="{{ route('pelatihan.index') }}" class="dashboard-action-card"><span>🔗</span><div><strong>Kelola Pemetaan</strong><small>Hubungkan kompetensi dengan pelatihan.</small></div><b>→</b></a>
        <a href="{{ route('import.index') }}" class="dashboard-action-card"><span>📊</span><div><strong>Import Excel</strong><small>Perbarui data dari file sumber terbaru.</small></div><b>→</b></a>
        <a href="{{ route('audit.index') }}" class="dashboard-action-card"><span>🛡️</span><div><strong>Audit Aktivitas</strong><small>Telusuri perubahan yang dilakukan Admin.</small></div><b>→</b></a>
    </section>
</div>
@endsection
