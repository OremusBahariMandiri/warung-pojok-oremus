@extends('layouts.admin')

@section('title', 'Log Aktivitas Sistem — Warung Pojok Oremus')

@push('styles')
<!-- DataTables BSD & Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/activity_logs.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item text-muted">pengaturan sistem</li>
        <li class="breadcrumb-item active text-dark" aria-current="page">log aktivitas</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $logList = $logs ?? collect();
    $totalLogs = method_exists($logList, 'total') ? $logList->total() : $logList->count();
    $todayLogs = $logList->filter(fn($l) => $l->created_at && $l->created_at->isToday())->count();
    $mutationLogs = $logList->filter(fn($l) => in_array(strtoupper($l->action ?? ''), ['CREATE', 'UPDATE', 'DELETE']))->count();
    $restockLogs = $logList->filter(fn($l) => strtoupper($l->action ?? '') === 'RESTOCK')->count();

    // Module list for filter dropdown
    $defaultModules = ['PRODUCTS', 'HPP', 'RESTOCK', 'REPORT', 'USER', 'UNITS', 'AUTH'];
    $collectedModules = $logList->pluck('module')->unique()->filter()->map(fn($m) => strtoupper($m))->toArray();
    $allModules = array_values(array_unique(array_merge($defaultModules, $collectedModules)));

    // Unique users list
    $uniqueUsers = $logList->map(fn($l) => $l->user)->filter()->unique('id');

    function getLogActionClass($action) {
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

    function getLogActionIcon($action) {
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
@endphp

<!-- Header Title -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Log Aktivitas Sistem</h4>
    </div>
</div>

<!-- Session Alert Messages -->
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-0 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('success') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('error') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Quick Stat Cards -->
{{-- <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="log-stat-card d-flex align-items-center gap-3">
            <div class="log-stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="log-stat-value">{{ number_format($totalLogs, 0, ',', '.') }}</div>
                <div class="log-stat-label">Total Aktivitas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="log-stat-card d-flex align-items-center gap-3">
            <div class="log-stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <div class="log-stat-value">{{ number_format($todayLogs, 0, ',', '.') }}</div>
                <div class="log-stat-label">Aktivitas Hari Ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="log-stat-card d-flex align-items-center gap-3">
            <div class="log-stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <div>
                <div class="log-stat-value">{{ number_format($mutationLogs, 0, ',', '.') }}</div>
                <div class="log-stat-label">Mutasi Data (CRUD)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="log-stat-card d-flex align-items-center gap-3">
            <div class="log-stat-icon bg-purple bg-opacity-10 text-purple" style="background-color: rgba(147, 51, 234, 0.1); color: #9333ea;">
                <i class="bi bi-box-arrow-in-down"></i>
            </div>
            <div>
                <div class="log-stat-value">{{ number_format($restockLogs, 0, ',', '.') }}</div>
                <div class="log-stat-label">Aktivitas Restock</div>
            </div>
        </div>
    </div>
</div> --}}

{{-- ═══════════════════════════════════════════════════════════════
     DESKTOP LAYOUT (>= 768px) — DataTables Table
     ═══════════════════════════════════════════════════════════════ --}}
<div class="card-box border rounded-3 overflow-hidden shadow-sm bg-white mb-4 activity-logs-desktop-card">

    <!-- Action & Filter Header -->
    <div class="py-3 d-flex align-items-center justify-content-between gap-3">
        <!-- Left: Search Box -->
        <div class="position-relative activity-search-box">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" id="dtSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2" placeholder="Cari deskripsi, modul, pengguna, atau IP...">
        </div>

        <!-- Right: Action & Filter Buttons -->
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-2 px-3 position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterActivityLog">
                <i class="bi bi-funnel"></i> Filter
                <span id="activeFilterBadge" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                    <span class="visually-hidden">Filter Aktif</span>
                </span>
            </button>
        </div>
    </div>

    <!-- DataTables Table -->
    <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap" id="activityLogsDataTable">
        <thead>
            <tr>
                <th style="width: 50px;" class="text-center">No</th>
                <th style="width: 160px;" class="text-center">Waktu & Tanggal</th>
                <th style="width: 120px;" class="text-center">Modul</th>
                <th style="width: 110px;" class="text-center">Aksi</th>
                <th class="text-center">Deskripsi Aktivitas</th>
                <th style="width: 180px;" class="text-center">Pengguna</th>
                <th style="width: 100px;" class="text-center no-sort">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logList as $log)
                @php
                    $actor = $log->user;
                    $actorName = $actor->employee_name ?? $actor->email ?? 'Sistem / Cron';
                    $initials = strtoupper(substr(trim($actorName), 0, 2));
                    $formattedDate = $log->created_at ? $log->created_at->format('d M Y, H:i:s') : '-';
                    $rawDate = $log->created_at ? $log->created_at->format('Y-m-d') : '';
                    $relativeTime = $log->created_at ? $log->created_at->diffForHumans() : '';
                    $actionClass = getLogActionClass($log->action);
                    $actionIcon = getLogActionIcon($log->action);

                    // Payload for quick preview modal
                    $quickPayload = [
                        'id' => $log->id,
                        'action' => strtoupper($log->action ?? '-'),
                        'module' => strtoupper($log->module ?? '-'),
                        'description' => $log->description,
                        'user_name' => $actorName,
                        'time' => $formattedDate . ' (' . $relativeTime . ')',
                        'ip' => $log->ip_address ?? '127.0.0.1',
                        'old_values' => $log->old_values,
                        'new_values' => $log->new_values,
                    ];
                @endphp
                <tr data-date="{{ $rawDate }}" data-module="{{ strtoupper($log->module ?? '') }}" data-action="{{ strtoupper($log->action ?? '') }}">
                    {{-- #1 No --}}
                    <td class="text-center text-muted fw-medium small">{{ $loop->iteration }}</td>

                    {{-- #2 Waktu --}}
                    <td class="text-center" data-order="{{ $log->created_at ? $log->created_at->timestamp : 0 }}">
                        <div class="text-dark fw-semibold small">{{ $log->created_at ? $log->created_at->format('d M Y, H:i') : '-' }}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">{{ $relativeTime }}</div>
                    </td>

                    {{-- #3 Modul --}}
                    <td class="text-center">
                        <span class="badge-module">
                            <i class="bi bi-folder2 me-1 text-secondary"></i>{{ strtoupper($log->module ?? 'SYSTEM') }}
                        </span>
                    </td>

                    {{-- #4 Aksi --}}
                    <td class="text-center">
                        <span class="badge-action {{ $actionClass }}">
                            <i class="bi {{ $actionIcon }}"></i> {{ strtoupper($log->action ?? 'LOG') }}
                        </span>
                    </td>

                    {{-- #5 Deskripsi --}}
                    <td>
                        <div class="text-dark fw-medium fs-sm text-truncate" style="max-width: 420px;" title="{{ $log->description }}">
                            {{ $log->description ?: "Aktivitas pada modul {$log->module}" }}
                        </div>
                        @if ($log->entity_type)
                            <div class="text-muted small" style="font-size: 0.72rem;">
                                <span class="font-monospace">{{ class_basename($log->entity_type) }}</span> (ID: {{ $log->entity_id }})
                            </div>
                        @endif
                    </td>

                    {{-- #6 User --}}
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="actor-avatar-sm">{{ $initials }}</div>
                            <div class="d-flex flex-column">
                                <span class="text-dark fw-semibold small text-truncate" style="max-width: 120px;">{{ $actorName }}</span>
                                <span class="ip-tag mt-1">{{ $log->ip_address ?? '127.0.0.1' }}</span>
                            </div>
                        </div>
                    </td>

                    {{-- #7 Aksi Detail --}}
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1"
                                    title="Quick Preview Data"
                                    data-log="{{ json_encode($quickPayload) }}"
                                    onclick="openQuickLogModal(this)">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="{{ route('activity_logs.show', $log->id) }}" class="btn btn-sm btn-outline-success rounded-2 px-2 py-1" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     MOBILE LAYOUT (< 768px) — Responsive Card List
     ═══════════════════════════════════════════════════════════════ --}}
<div class="activity-logs-mobile-wrapper mb-4">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        
        <!-- Mobile Header Toolbar -->
        <div class="card-header bg-white p-3 border-0 d-flex flex-column gap-2">
            <!-- Search Bar -->
            <div class="position-relative w-100">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="mobileLogSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2 w-100" placeholder="Cari aktivitas / user...">
            </div>

            <!-- Filter Button -->
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center justify-content-center gap-2 px-3 position-relative w-100" data-bs-toggle="modal" data-bs-target="#modalFilterActivityLog">
                <i class="bi bi-funnel"></i> Filter
                <span id="activeFilterBadgeMobile" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                    <span class="visually-hidden">Filter Aktif</span>
                </span>
            </button>
        </div>

        <!-- Mobile Card Items Container -->
        <div class="card-body p-3 pt-0" id="mobileLogCards">
            @forelse ($logList as $log)
                @php
                    $actor = $log->user;
                    $actorName = $actor->employee_name ?? $actor->email ?? 'Sistem / Cron';
                    $initials = strtoupper(substr(trim($actorName), 0, 2));
                    $formattedDate = $log->created_at ? $log->created_at->format('d M Y, H:i') : '-';
                    $rawDate = $log->created_at ? $log->created_at->format('Y-m-d') : '';
                    $relativeTime = $log->created_at ? $log->created_at->diffForHumans() : '';
                    $actionClass = getLogActionClass($log->action);
                    $actionIcon = getLogActionIcon($log->action);

                    $searchKeywords = strtolower("{$log->description} {$actorName} {$log->module} {$log->action} {$log->ip_address}");
                @endphp

                <div class="mobile-log-card"
                     data-date="{{ $rawDate }}"
                     data-module="{{ strtoupper($log->module ?? '') }}"
                     data-action="{{ strtoupper($log->action ?? '') }}"
                     data-user="{{ strtolower($actorName) }}"
                     data-search="{{ $searchKeywords }}">

                    <!-- Card Header -->
                    <div class="mlc-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge-action {{ $actionClass }}">
                                <i class="bi {{ $actionIcon }}"></i> {{ strtoupper($log->action ?? 'LOG') }}
                            </span>
                            <span class="badge-module">{{ strtoupper($log->module ?? 'SYS') }}</span>
                        </div>
                        <div class="mlc-time">
                            <i class="bi bi-clock"></i> {{ $formattedDate }}
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="mlc-body">
                        <div class="mlc-desc">{{ $log->description ?: "Aktivitas pada modul {$log->module}" }}</div>
                        <div class="mlc-meta">
                            <div class="d-flex align-items-center gap-2">
                                <div class="actor-avatar-sm" style="width:24px; height:24px; font-size:0.65rem;">{{ $initials }}</div>
                                <span class="text-dark fw-semibold small">{{ $actorName }}</span>
                            </div>
                            <span class="ip-tag">{{ $log->ip_address ?? '127.0.0.1' }}</span>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="mlc-footer">
                        <span class="text-muted small" style="font-size: 0.72rem;">#Log ID {{ $log->id }}</span>
                        <a href="{{ route('activity_logs.show', $log->id) }}" class="btn btn-sm btn-outline-success rounded-2 px-3 py-1 small">
                            <i class="bi bi-eye me-1"></i> Detail
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted small">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    Belum ada log aktivitas yang tercatat.
                </div>
            @endforelse

            <div id="mobileLogNoResult" class="text-center py-4 text-muted small d-none">
                Tidak ada log aktivitas yang cocok dengan pencarian atau filter.
            </div>
        </div>

    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     MODAL FILTER LOG AKTIVITAS
     ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalFilterActivityLog" tabindex="-1" aria-labelledby="modalFilterActivityLogLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark" id="modalFilterActivityLogLabel">
                    <i class="bi bi-funnel me-1"></i> Filter Log Aktivitas
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 d-flex flex-column gap-3">
                <div>
                    <label for="modalFilterModule" class="form-label small fw-semibold text-dark mb-1">Modul Sistem</label>
                    <select id="modalFilterModule" class="form-select form-select-sm rounded-2">
                        <option value="">Semua Modul</option>
                        @foreach ($allModules as $mod)
                            <option value="{{ $mod }}">{{ $mod }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="modalFilterAction" class="form-label small fw-semibold text-dark mb-1">Tipe Aksi</label>
                    <select id="modalFilterAction" class="form-select form-select-sm rounded-2">
                        <option value="">Semua Aksi</option>
                        <option value="CREATE">CREATE (Tambah Data)</option>
                        <option value="UPDATE">UPDATE (Ubah Data)</option>
                        <option value="DELETE">DELETE (Hapus Data)</option>
                        <option value="RESTOCK">RESTOCK (Restock Barang)</option>
                        <option value="REPORT">REPORT (Laporan Penjualan)</option>
                        <option value="LOGIN">LOGIN (Masuk Sistem)</option>
                        <option value="LOGOUT">LOGOUT (Keluar Sistem)</option>
                    </select>
                </div>

                <div>
                    <label for="modalFilterUser" class="form-label small fw-semibold text-dark mb-1">Pengguna</label>
                    <select id="modalFilterUser" class="form-select form-select-sm rounded-2">
                        <option value="">Semua Pengguna</option>
                        @foreach ($uniqueUsers as $u)
                            <option value="{{ $u->employee_name ?? $u->email }}">{{ $u->employee_name ?? $u->email }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label for="modalFilterStartDate" class="form-label small fw-semibold text-dark mb-1">Dari Tanggal</label>
                        <input type="date" id="modalFilterStartDate" class="form-control form-control-sm rounded-2">
                    </div>
                    <div class="col-6">
                        <label for="modalFilterEndDate" class="form-label small fw-semibold text-dark mb-1">Sampai Tanggal</label>
                        <input type="date" id="modalFilterEndDate" class="form-control form-control-sm rounded-2">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-light border rounded-2 px-3" id="btnResetFilter" data-bs-dismiss="modal">
                    Reset
                </button>
                <button type="button" class="btn btn-sm btn-success text-white fw-semibold rounded-2 px-4" id="btnApplyFilter" data-bs-dismiss="modal">
                    Terapkan Filter
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     MODAL QUICK PREVIEW DATA CHANGES
     ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalQuickViewLog" tabindex="-1" aria-labelledby="modalQuickViewLogLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="modalQuickViewLogLabel">
                        <i class="bi bi-binoculars me-1 text-success"></i> Quick Preview Mutasi Data <span id="quickLogId" class="text-muted font-monospace"></span>
                    </h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Meta strip -->
                <div class="bg-light border rounded-3 p-3 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2 small">
                    <div>
                        <span class="text-muted">Aksi:</span> <strong id="quickLogAction" class="text-dark"></strong>
                        <span class="mx-2 text-muted">•</span>
                        <span class="text-muted">Modul:</span> <strong id="quickLogModule" class="text-dark"></strong>
                    </div>
                    <div>
                        <span class="text-muted">Petugas:</span> <strong id="quickLogUser" class="text-dark"></strong>
                        <span class="mx-2 text-muted">•</span>
                        <span class="text-muted">IP:</span> <span id="quickLogIp" class="font-monospace text-dark"></span>
                    </div>
                    <div class="w-100 text-muted border-top pt-2 mt-1">
                        <span>Waktu:</span> <span id="quickLogTime" class="text-dark fw-medium font-monospace"></span>
                    </div>
                    <div class="w-100 text-muted border-top pt-2 mt-1">
                        <span>Deskripsi:</span> <span id="quickLogDesc" class="text-dark fw-medium"></span>
                    </div>
                </div>

                <!-- Old vs New JSON side-by-side -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="fw-semibold text-danger small mb-1">
                            <i class="bi bi-dash-circle me-1"></i> Old Values (Sebelum)
                        </div>
                        <div class="code-box-wrapper">
                            <pre class="code-box-content" id="quickLogOldJson" style="max-height: 240px;"></pre>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-semibold text-success small mb-1">
                            <i class="bi bi-plus-circle me-1"></i> New Values (Sesudah)
                        </div>
                        <div class="code-box-wrapper">
                            <pre class="code-box-content" id="quickLogNewJson" style="max-height: 240px;"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-light border rounded-2 px-3" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="quickLogDetailLink" class="btn btn-sm btn-success text-white fw-semibold rounded-2 px-4">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Halaman Detail Penuh
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script> 
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="{{ asset('js/activity_logs.js') }}"></script>
@endpush
