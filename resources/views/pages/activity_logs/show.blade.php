@extends('layouts.admin')

@section('title', 'Detail Log Aktivitas #' . $activityLog->id . ' — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/activity_logs.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item text-muted">pengaturan sistem</li>
        <li class="breadcrumb-item"><a href="{{ route('activity_logs.index') }}" class="text-decoration-none text-muted">log aktivitas</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">detail log #{{ $activityLog->id }}</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $log = $activityLog;
    $actor = $log->user;
    $actorName = $actor->employee_name ?? $actor->email ?? 'Sistem Otomatis';
    $actorEmail = $actor->email ?? '-';
    $actorRole = $actor ? ($actor->is_admin ? 'Super Admin' : 'Petugas Kasir') : 'Sistem / Background Job';
    $initials = strtoupper(substr(trim($actorName), 0, 2));
    
    $formattedDate = $log->created_at ? $log->created_at->format('d M Y, H:i:s') : '-';
    $relativeTime = $log->created_at ? $log->created_at->diffForHumans() : '';

    $oldVals = is_array($log->old_values) ? $log->old_values : (json_decode($log->old_values, true) ?? []);
    $newVals = is_array($log->new_values) ? $log->new_values : (json_decode($log->new_values, true) ?? []);

    $allKeys = array_values(array_unique(array_merge(array_keys($oldVals), array_keys($newVals))));

    function getActionClassShow($action) {
        return match(strtoupper($action ?? '')) {
            'CREATE' => 'badge-action-create',
            'UPDATE' => 'badge-action-update',
            'DELETE' => 'badge-action-delete',
            'RESTOCK' => 'badge-action-restock',
            'LOGIN', 'LOGOUT' => 'badge-action-login',
            'REPORT' => 'badge-action-report',
            default => 'badge-action-default',
        };
    }

    function getActionIconShow($action) {
        return match(strtoupper($action ?? '')) {
            'CREATE' => 'bi-plus-circle',
            'UPDATE' => 'bi-pencil-square',
            'DELETE' => 'bi-trash',
            'RESTOCK' => 'bi-box-arrow-in-down',
            'LOGIN' => 'bi-box-arrow-in-right',
            'LOGOUT' => 'bi-box-arrow-left',
            'REPORT' => 'bi-file-earmark-bar-graph',
            default => 'bi-activity',
        };
    }

    function formatVal($val) {
        if (is_null($val)) return '<span class="text-muted fst-italic">null</span>';
        if (is_bool($val)) return $val ? '<span class="badge bg-success-subtle text-success">true</span>' : '<span class="badge bg-danger-subtle text-danger">false</span>';
        if (is_array($val)) return htmlspecialchars(json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return htmlspecialchars((string)$val);
    }
@endphp

<!-- Header Title & Back Button -->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">Rincian Log Aktivitas</h4>
            <span class="badge bg-light text-dark border font-monospace px-2 py-1">#{{ $log->id }}</span>
        </div>
        <p class="text-muted small mb-0">Detail metadata audit trail dan perbandingan perubahan nilai entitas data.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('activity_logs.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Summary & Metadata (4 cols) -->
    <div class="col-lg-4">
        <div class="d-flex flex-column gap-3">
            
            <!-- Card 1: Detail Aktivitas -->
            <div class="card-box bg-white border rounded-3 p-4 shadow-sm">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center justify-content-between">
                    <span>Informasi Aktivitas</span>
                    <i class="bi bi-info-circle text-muted"></i>
                </h6>
                <div class="d-flex flex-column gap-3">
                    <div>
                        <div class="text-muted small">Tipe Aksi</div>
                        <div class="mt-1">
                            <span class="badge-action {{ getActionClassShow($log->action) }}">
                                <i class="bi {{ getActionIconShow($log->action) }}"></i> {{ strtoupper($log->action ?? 'LOG') }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted small">Modul Terkait</div>
                        <div class="mt-1">
                            <span class="badge-module">
                                <i class="bi bi-folder2 text-secondary me-1"></i> {{ strtoupper($log->module ?? 'SYSTEM') }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted small">Waktu Transaksi</div>
                        <div class="fw-semibold text-dark fs-sm">{{ $formattedDate }}</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">{{ $relativeTime }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Deskripsi Transaksi</div>
                        <div class="text-dark fw-medium small bg-light p-2 rounded-2 border mt-1">
                            {{ $log->description ?: 'Tidak ada catatan deskripsi.' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Informasi Petugas / Aktor -->
            <div class="card-box bg-white border rounded-3 p-4 shadow-sm">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center justify-content-between">
                    <span>Pengguna</span>
                    <i class="bi bi-person-badge text-muted"></i>
                </h6>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="actor-avatar-lg">{{ $initials }}</div>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-dark fs-sm">{{ $actorName }}</span>
                        <span class="text-muted small">{{ $actorEmail }}</span>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary align-self-start mt-1" style="font-size:0.7rem;">{{ $actorRole }}</span>
                    </div>
                </div>
                <div class="border-top pt-3 d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">User ID:</span>
                        <span class="font-monospace fw-semibold text-dark">{{ $log->user_id ?? 'System (Null)' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">IP Address:</span>
                        <span class="ip-tag">{{ $log->ip_address ?? '127.0.0.1' }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Informasi Entitas -->
            @if ($log->entity_type || $log->entity_id)
            <div class="card-box bg-white border rounded-3 p-4 shadow-sm">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center justify-content-between">
                    <span>Entitas Data</span>
                    <i class="bi bi-database text-muted"></i>
                </h6>
                <div class="d-flex flex-column gap-2 small">
                    <div>
                        <div class="text-muted">Model / Class:</div>
                        <div class="font-monospace fw-semibold text-dark mt-1 text-truncate" title="{{ $log->entity_type }}">{{ $log->entity_type ?: '-' }}</div>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span class="text-muted">Record Entity ID:</span>
                        <span class="font-monospace fw-bold text-dark">#{{ $log->entity_id ?: '-' }}</span>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    <!-- Right Column: Audit Trail & Data Diff (8 cols) -->
    <div class="col-lg-8">
        <div class="card-box p-0 bg-white border rounded-3 overflow-hidden shadow-sm mb-4">
            
            <!-- Card Header with Tabs -->
            <div class="p-3 border-bottom bg-light bg-opacity-50 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-0">Inspeksi Mutasi Data</h6>
                    <span class="text-muted small">Perbandingan data sebelum dan sesudah aktivitas dilakukan.</span>
                </div>
                <ul class="nav nav-pills log-nav-tabs" id="logDetailTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-1 px-3" id="diff-tab" data-bs-toggle="tab" data-bs-target="#diff-tab-pane" type="button" role="tab" aria-selected="true">
                            <i class="bi bi-arrow-left-right me-1"></i> Visual Diff
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1 px-3" id="json-tab" data-bs-toggle="tab" data-bs-target="#json-tab-pane" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-code-slash me-1"></i> Raw JSON
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content p-4" id="logDetailTabContent">
                
                {{-- ── TAB 1: VISUAL DIFF ── --}}
                <div class="tab-pane fade show active" id="diff-tab-pane" role="tabpanel" aria-labelledby="diff-tab" tabindex="0">
                    
                    @if (empty($oldVals) && empty($newVals))
                        <div class="text-center py-5 text-muted">
                            <div class="avatar-circle mx-auto mb-2 bg-light text-muted" style="width:48px;height:48px;font-size:1.3rem;">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Tidak Ada Data Payload</h6>
                            <p class="small text-muted mb-0">Aktivitas ini tidak menyimpan perubahan data nilai lama ataupun nilai baru.</p>
                        </div>
                    @else
                        <!-- Diff Legend -->
                        <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded-2 border small">
                            <span class="text-muted fw-semibold">Keterangan:</span>
                            <span class="d-inline-flex align-items-center gap-1"><span class="badge bg-danger bg-opacity-25 text-danger border border-danger-subtle px-2 py-1">Old Values</span> Nilai Sebelum</span>
                            <span class="d-inline-flex align-items-center gap-1"><span class="badge bg-success bg-opacity-25 text-success border border-success-subtle px-2 py-1">New Values</span> Nilai Sesudah</span>
                        </div>

                        <!-- Diff Table -->
                        <div class="table-responsive border rounded-3">
                            <table class="table diff-table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Field / Properti</th>
                                        <th style="width: 35%;">Nilai Lama (Sebelum)</th>
                                        <th style="width: 35%;">Nilai Baru (Sesudah)</th>
                                        <th style="width: 5%;" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($allKeys as $key)
                                        @php
                                            $hasOld = array_key_exists($key, $oldVals);
                                            $hasNew = array_key_exists($key, $newVals);
                                            $oldVal = $hasOld ? $oldVals[$key] : null;
                                            $newVal = $hasNew ? $newVals[$key] : null;
                                            $isChanged = json_encode($oldVal) !== json_encode($newVal);
                                        @endphp
                                        <tr>
                                            <!-- Field Name -->
                                            <td class="diff-field-name">
                                                <i class="bi bi-hash text-muted me-1"></i>{{ $key }}
                                            </td>

                                            <!-- Old Value -->
                                            <td>
                                                @if ($hasOld)
                                                    <div class="{{ $isChanged ? 'diff-old-val' : 'diff-unchanged-val' }}">
                                                        {!! formatVal($oldVal) !!}
                                                    </div>
                                                @else
                                                    <span class="text-muted small fst-italic">— Tidak ada —</span>
                                                @endif
                                            </td>

                                            <!-- New Value -->
                                            <td>
                                                @if ($hasNew)
                                                    <div class="{{ $isChanged ? 'diff-new-val' : 'diff-unchanged-val' }}">
                                                        {!! formatVal($newVal) !!}
                                                    </div>
                                                @else
                                                    <span class="text-muted small fst-italic">— Dihapus —</span>
                                                @endif
                                            </td>

                                            <!-- Status Icon -->
                                            <td class="text-center align-middle">
                                                @if (!$hasOld && $hasNew)
                                                    <span class="badge bg-success text-white" title="Ditambahkan"><i class="bi bi-plus"></i></span>
                                                @elseif ($hasOld && !$hasNew)
                                                    <span class="badge bg-danger text-white" title="Dihapus"><i class="bi bi-dash"></i></span>
                                                @elseif ($isChanged)
                                                    <span class="badge bg-warning text-dark" title="Diubah"><i class="bi bi-pencil"></i></span>
                                                @else
                                                    <span class="badge bg-light text-muted border" title="Sama / Tidak Berubah"><i class="bi bi-check"></i></span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>

                {{-- ── TAB 2: RAW JSON ── --}}
                <div class="tab-pane fade" id="json-tab-pane" role="tabpanel" aria-labelledby="json-tab" tabindex="0">
                    <div class="row g-3">
                        <!-- Old Values JSON -->
                        <div class="col-md-6">
                            <div class="code-box-wrapper">
                                <div class="code-box-header">
                                    <span class="fw-semibold text-danger"><i class="bi bi-dash-circle me-1"></i> Old Values (Sebelum)</span>
                                    <button type="button" class="btn btn-sm btn-outline-light border-0 py-0 px-2 small" onclick="copyLogJson('codeOldValues', this)">
                                        <i class="bi bi-clipboard me-1"></i> Salin
                                    </button>
                                </div>
                                <pre class="code-box-content" id="codeOldValues">{{ !empty($oldVals) ? json_encode($oldVals, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '// Tidak ada data nilai lama' }}</pre>
                            </div>
                        </div>

                        <!-- New Values JSON -->
                        <div class="col-md-6">
                            <div class="code-box-wrapper">
                                <div class="code-box-header">
                                    <span class="fw-semibold text-success"><i class="bi bi-plus-circle me-1"></i> New Values (Sesudah)</span>
                                    <button type="button" class="btn btn-sm btn-outline-light border-0 py-0 px-2 small" onclick="copyLogJson('codeNewValues', this)">
                                        <i class="bi bi-clipboard me-1"></i> Salin
                                    </button>
                                </div>
                                <pre class="code-box-content" id="codeNewValues">{{ !empty($newVals) ? json_encode($newVals, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '// Tidak ada data nilai baru' }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/activity_logs.js') }}"></script>
@endpush
