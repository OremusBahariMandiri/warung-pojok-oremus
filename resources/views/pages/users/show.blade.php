@extends('layouts.admin')

@section('title', 'Profil Pengguna — ' . ($user->employee_name ?? $user->email) . ' — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item text-muted">pengaturan sistem</li>
        @if($user->id !== auth()->id())
            <li class="breadcrumb-item"><a href="{{ route('users.index') }}" class="text-decoration-none text-muted">kelola pengguna</a></li>
        @endif
        <li class="breadcrumb-item active text-dark" aria-current="page">profil pengguna</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $name = $user->employee_name ?? $user->email;
    $initials = strtoupper(substr(trim($name), 0, 2));
    $isAdmin = (bool)$user->is_admin;
    $accessList = $user->accesses ?? collect();

    // Module definitions for matrix preview
    $menuLabels = [
        'dashboard' => 'Dashboard Utama',
        'products' => 'Master – Manajemen Produk',
        'units' => 'Master – Manajemen Satuan',
        'hpp' => 'Master – Manajemen Komponen HPP',
        'restock' => 'Inventori – Restock Barang',
        'reports' => 'Laporan – Laporan Penjualan & Margin',
        'users' => 'Manajemen – Kelola Pengguna',
        'activity_logs' => 'Manajemen – Log Aktivitas Sistem',
    ];
@endphp

<!-- Header Title & Action Buttons -->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Profil Pengguna</h4>
        <p class="text-muted small mb-0">Informasi akun, ringkasan izin hak akses modul, dan statistik aktivitas.</p>
    </div>
    <div class="d-flex align-items-center gap-2 justify-around">
        @if($user->id !== auth()->id())
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary ...">← Kembali</a>
        @else
            <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary ...">← Kembali</a>
        @endif
        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-warning rounded-2 px-3 text-dark d-inline-flex align-items-center gap-2">
            <i class="bi bi-pencil"></i> Edit
        </a>
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

<div class="row g-4">
    <!-- Left Column: User Profile Card (4 cols) -->
    <div class="col-lg-4">
        <div class="d-flex flex-column gap-3">
            
            <!-- Profile Card -->
            <div class="card-box bg-white border rounded-3 p-4 shadow-sm text-center">
                <div class="user-avatar-lg mx-auto mb-3 {{ $isAdmin ? 'user-avatar-admin' : 'user-avatar-cashier' }}">
                    {{ $initials }}
                </div>
                <h5 class="fw-bold text-dark mb-1">{{ $name }}</h5>
                <p class="text-muted small mb-2">{{ $user->email }}</p>
                <div>
                    @if ($isAdmin)
                        <span class="badge-role-admin">Super Administrator</span>
                    @else
                        <span class="badge-role-cashier">Staff</span>
                    @endif
                </div>

                <div class="border-top pt-3 mt-4 text-start d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">NRK Karyawan:</span>
                        <span class="nrk-tag">{{ $user->nrk }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">User ID:</span>
                        <span class="font-monospace fw-semibold text-dark">#{{ $user->id }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Terdaftar Pada:</span>
                        <span class="text-dark">{{ $user->created_at ? $user->created_at->format('d M Y, H:i') : '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Terakhir Diperbarui:</span>
                        <span class="text-dark">{{ $user->updated_at ? $user->updated_at->diffForHumans() : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Activity Quick Counts -->
            <div class="card-box bg-white border rounded-3 p-4 shadow-sm">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Statistik Transaksi User</h6>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-box-arrow-in-down text-purple"></i>
                            <span class="small text-muted">Transaksi Restock</span>
                        </div>
                        <span class="fw-bold text-dark">{{ $user->restocks ? $user->restocks->count() : 0 }} kali</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-bar-graph text-success"></i>
                            <span class="small text-muted">Laporan Penjualan</span>
                        </div>
                        <span class="fw-bold text-dark">{{ $user->reports ? $user->reports->count() : 0 }} kali</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-primary"></i>
                            <span class="small text-muted">Log Aktivitas Tercatat</span>
                        </div>
                        <span class="fw-bold text-dark">{{ $user->activityLogs ? $user->activityLogs->count() : 0 }} entri</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Right Column: RBAC Overview & Activity History (8 cols) -->
    <div class="col-lg-8">
        <div class="d-flex flex-column gap-4">
            
            <!-- RBAC Current Permissions Card -->
            <div class="card-box bg-white border rounded-3 overflow-hidden shadow-sm">
                <div class="py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold text-dark mb-0">Status Hak Akses Modul</h6>
                    </div>
                    <a href="{{ route('users.access.show', $user->id) }}" class="btn btn-sm btn-outline-primary rounded-2 px-3">
                        <i class="bi bi-sliders me-1"></i> Ubah Hak Akses
                    </a>
                </div>

                @if ($isAdmin)
                    <div class="p-4 text-center">
                        <div class="avatar-circle mx-auto mb-2 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center rounded-circle" style="width: 48px; height: 48px; font-size: 1.3rem;">
                            <i class="bi bi-shield-fill-check"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Akses Penuh Administrator</h6>
                        <p class="text-muted small mb-0">
                            Sebagai Super Admin, pengguna ini memiliki otorisasi penuh ke seluruh fungsi (Lihat, Tambah, Ubah, Detail, Hapus) di semua modul aplikasi Warjok.
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Modul</th>
                                    <th class="text-center" style="width: 80px;">LIHAT</th>
                                    <th class="text-center" style="width: 80px;">TAMBAH</th>
                                    <th class="text-center" style="width: 80px;">UBAH</th>
                                    <th class="text-center" style="width: 80px;">DETAIL</th>
                                    <th class="text-center" style="width: 80px;">HAPUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($menuLabels as $menuKey => $label)
                                    @php
                                        $perm = $accessList->firstWhere('menu_access', $menuKey);
                                        $hasIdx = $perm && (string)$perm->index_acs === '1';
                                        $hasCrt = $perm && (string)$perm->create_acs === '1';
                                        $hasEdt = $perm && (string)$perm->edit_acs === '1';
                                        $hasShw = $perm && (string)$perm->show_acs === '1';
                                        $hasDel = $perm && (string)$perm->delete_acs === '1';
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold text-dark small">{{ $label }}</td>
                                        <td class="text-center">
                                            {!! $hasIdx ? '<span class="badge bg-success text-white"><i class="bi bi-check"></i></span>' : '<span class="text-muted">—</span>' !!}
                                        </td>
                                        <td class="text-center">
                                            {!! $hasCrt ? '<span class="badge bg-success text-white"><i class="bi bi-check"></i></span>' : '<span class="text-muted">—</span>' !!}
                                        </td>
                                        <td class="text-center">
                                            {!! $hasEdt ? '<span class="badge bg-success text-white"><i class="bi bi-check"></i></span>' : '<span class="text-muted">—</span>' !!}
                                        </td>
                                        <td class="text-center">
                                            {!! $hasShw ? '<span class="badge bg-success text-white"><i class="bi bi-check"></i></span>' : '<span class="text-muted">—</span>' !!}
                                        </td>
                                        <td class="text-center">
                                            {!! $hasDel ? '<span class="badge bg-success text-white"><i class="bi bi-check"></i></span>' : '<span class="text-muted">—</span>' !!}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Recent Activity History Card -->
            <div class="card-box bg-white border rounded-3 p-4 shadow-sm">
                <div class="d-flex align-items-center justify-content-between pb-2 border-bottom mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                         Aktivitas Terakhir
                    </h6>
                    <a href="{{ route('activity_logs.index') }}" class="small text-decoration-none">
                        Lihat Semua Log <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                @php
                    $recentLogs = $user->activityLogs ? $user->activityLogs->sortByDesc('created_at')->take(5) : collect();
                @endphp

                @if ($recentLogs->count() > 0)
                    <div class="d-flex flex-column gap-2">
                        @foreach ($recentLogs as $actLog)
                            <div class="activity-item">
                                <div class="activity-item-top">
                                    <span class="badge bg-secondary bg-opacity-25 text-dark font-monospace activity-badge">
                                        {{ strtoupper($actLog->action) }}
                                    </span>
                                    <span class="activity-time">
                                        {{ $actLog->created_at ? $actLog->created_at->diffForHumans() : '-' }}
                                    </span>
                                </div>
                                <div class="activity-desc">
                                    {{ $actLog->description }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        Belum ada aktivitas yang tercatat untuk pengguna ini.
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/users.js') }}"></script>
@endpush
