@extends('layouts.app')

@section('title', 'Audit Trail - CKPS Mapping')

@section('content')
<div class="page">
    <div class="page-header-row">
        <div>
            <div class="kicker">PENGAWASAN SISTEM</div>
            <h1>Audit Trail</h1>
            <p class="page-description">Catatan perubahan data untuk mengetahui siapa, kapan, dan apa yang diubah.</p>
        </div>
        <span class="table-count">{{ number_format($logs->total(), 0, ',', '.') }} aktivitas</span>
    </div>

    <div class="audit-stat-grid">
        <div class="audit-stat-card">
            <span>Total aktivitas</span>
            <strong>{{ number_format($totalActivities, 0, ',', '.') }}</strong>
        </div>
        <div class="audit-stat-card">
            <span>Aktivitas hari ini</span>
            <strong>{{ number_format($todayActivities, 0, ',', '.') }}</strong>
        </div>
        <div class="audit-stat-card">
            <span>Koreksi/perubahan</span>
            <strong>{{ number_format($correctionActivities, 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="panel" style="margin-bottom:20px;">
        <div class="panel-header">
            <h2>Filter audit</h2>
            <p>Gunakan pencarian dan rentang tanggal untuk menemukan perubahan tertentu.</p>
        </div>
        <div class="panel-body">
            <form method="GET" class="audit-filter-grid">
                <input type="text" name="search" value="{{ request('search') }}" class="search-input" placeholder="Cari pengguna, aktivitas, IP, atau objek...">
                <select name="action" class="form-control">
                    <option value="">Semua aktivitas</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                            {{ \App\Models\AuditLog::actionLabelFor($action) }}
                        </option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" aria-label="Tanggal mulai">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" aria-label="Tanggal akhir">
                <button class="btn" type="submit">Filter</button>
                @if(request()->hasAny(['search', 'action', 'date_from', 'date_to']))
                    <a href="{{ route('audit.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="panel audit-panel">
        <div class="panel-header audit-list-header">
            <div>
                <h2>Riwayat aktivitas</h2>
                <p>Setiap perubahan dicatat dan dapat ditelusuri kembali tanpa menghapus riwayat sebelumnya.</p>
            </div>
            <span class="audit-result-count">{{ $logs->total() }} catatan</span>
        </div>

        <div class="audit-list">
            @forelse($logs as $log)
                @php
                    $old = is_array($log->old_values) ? $log->old_values : [];
                    $new = is_array($log->new_values) ? $log->new_values : [];
                    $keys = collect(array_unique(array_merge(array_keys($old), array_keys($new))))
                        ->filter(fn ($key) => $key !== null && $key !== '')
                        ->values();

                    $formatAuditValue = function ($value) {
                        if ($value === null || $value === '') return '—';
                        if (is_bool($value)) return $value ? 'Ya' : 'Tidak';
                        if (is_array($value) || is_object($value)) {
                            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                        }
                        return (string) $value;
                    };
                @endphp

                <article class="audit-item">
                    <div class="audit-item-top">
                        <div class="audit-item-heading">
                            <div class="audit-item-icon" aria-hidden="true">✓</div>
                            <div class="audit-item-heading-text">
                                <h3>{{ $log->description }}</h3>
                                <div class="audit-meta">
                                    <span class="audit-user">{{ $log->user?->name ?: 'Sistem' }}</span>
                                    <span>•</span>
                                    <span>{{ $log->created_at?->format('d/m/Y H:i:s') }}</span>
                                    <span>•</span>
                                    <span>IP {{ $log->ip_address ?: '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="audit-item-identifiers">
                            @if($log->auditable_id)
                                <span class="audit-id">ID #{{ $log->auditable_id }}</span>
                            @endif
                            <span class="status-pill audit-action">{{ $log->actionLabel() }}</span>
                            <span class="status-pill audit-target">{{ $log->auditableLabel() }}</span>
                        </div>
                    </div>

                    @if($old || $new)
                        <details class="audit-details">
                            <summary>
                                <span class="audit-summary-icon">⌄</span>
                                Lihat detail perubahan
                            </summary>

                            <div class="audit-change-card">
                                <div class="audit-change-card-title">
                                    <div>
                                        <strong>Detail perubahan data</strong>
                                        <span>{{ $keys->count() }} field diperiksa</span>
                                    </div>
                                </div>

                                @if($keys->isEmpty())
                                    <div class="audit-empty-change">Tidak ada rincian field yang dapat ditampilkan.</div>
                                @else
                                    <div class="audit-change-list">
                                        @foreach($keys as $key)
                                            @php
                                                $before = array_key_exists($key, $old) ? $old[$key] : null;
                                                $after = array_key_exists($key, $new) ? $new[$key] : null;
                                                $beforeText = $formatAuditValue($before);
                                                $afterText = $formatAuditValue($after);
                                                $changed = $beforeText !== $afterText;
                                            @endphp

                                            <div class="audit-change-item {{ $changed ? 'is-changed' : '' }}">
                                                <div class="audit-field-name">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
                                                <div class="audit-value-block">
                                                    <span class="audit-value-label">Sebelum</span>
                                                    <div class="audit-value {{ $beforeText === '—' ? 'is-empty' : '' }}">{{ $beforeText }}</div>
                                                </div>
                                                <div class="audit-change-arrow" aria-hidden="true">→</div>
                                                <div class="audit-value-block">
                                                    <span class="audit-value-label">Sesudah</span>
                                                    <div class="audit-value audit-value-after {{ $afterText === '—' ? 'is-empty' : '' }}">{{ $afterText }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </details>
                    @endif
                </article>
            @empty
                <div class="empty-state">
                    <div class="empty-icon">🧾</div>
                    <strong>Belum ada aktivitas yang cocok.</strong>
                    Coba ubah filter atau lakukan perubahan data setelah audit trail aktif.
                </div>
            @endforelse
        </div>

        @if($logs->hasPages())
            <div class="pagination-wrap">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
