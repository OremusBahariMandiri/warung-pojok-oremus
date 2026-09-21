@extends('layouts.admin')

@section('title', 'Konfigurasi Hak Akses — ' . ($user->employee_name ?? $user->email) . ' — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item text-muted">pengaturan sistem</li>
        <li class="breadcrumb-item"><a href="{{ route('users.index') }}" class="text-decoration-none text-muted">kelola pengguna</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">hak akses pengguna</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $name = $user->employee_name ?? $user->email;
    $initials = strtoupper(substr(trim($name), 0, 2));
    $isAdmin = (bool)$user->is_admin;

    // Menu list organized with grouping & labels
    $menuGroups = [
        'MAIN MENU' => [
            'dashboard' => ['label' => 'Dashboard Utama', 'icon' => 'bi-grid-1x2'],
        ],
        'MASTER DATA' => [
            'products' => ['label' => 'Master – Manajemen Produk', 'icon' => 'bi-box-seam'],
            'units' => ['label' => 'Master – Manajemen Satuan', 'icon' => 'bi-tag'],
            'hpp' => ['label' => 'Master – Manajemen Komponen HPP', 'icon' => 'bi-calculator'],
        ],
        'INVENTORI' => [
            'restock' => ['label' => 'Inventori – Restock Barang', 'icon' => 'bi-arrow-repeat'],
        ],
        'LAPORAN & ANALITIK' => [
            'reports' => ['label' => 'Laporan – Laporan Penjualan & Margin', 'icon' => 'bi-bar-chart-line'],
        ],
        'PENGATURAN SISTEM' => [
            'users' => ['label' => 'Manajemen – Kelola Pengguna & Akses', 'icon' => 'bi-people'],
            'activity_logs' => ['label' => 'Manajemen – Log Aktivitas Sistem', 'icon' => 'bi-clock-history'],
        ],
    ];
@endphp

<!-- Header Title -->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Konfigurasi Hak Akses Pengguna</h4>
        <p class="text-muted small mb-0">Atur otorisasi izin menu per modul untuk akun karyawan.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
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

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('error') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- User Profile Info Banner -->
<div class="rbac-user-banner shadow-sm d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <div class="user-avatar-md {{ $isAdmin ? 'user-avatar-admin' : 'user-avatar-cashier' }}">
            {{ $initials }}
        </div>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold text-dark mb-0">{{ $name }}</h6>
                @if ($isAdmin)
                    <span class="badge-role-admin"><i class="bi bi-shield-fill-check"></i> Super Admin</span>
                @else
                    <span class="badge-role-cashier"><i class="bi bi-person-fill"></i> Staff</span>
                @endif
            </div>
            <div class="text-muted small mt-1">
                <span>{{ $user->email }}</span>
                <span class="mx-2">•</span>
                <span>NRK: <strong class="font-monospace text-dark">{{ $user->nrk }}</strong></span>
            </div>
        </div>
    </div>
    <div>
        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

@if ($isAdmin)
<div class="alert alert-warning d-flex align-items-center gap-2 py-2 px-3 mb-4 rounded-3 border-0 shadow-sm">
    <i class="bi bi-info-circle-fill fs-5"></i>
    <div class="small">
        <strong>Perhatian:</strong> Pengguna ini memiliki peran <strong>Super Admin</strong>. Secara default sistem, Super Admin memiliki akses penuh (*full access*) ke seluruh modul tanpa batasan permission.
    </div>
</div>
@endif

<!-- Main Form: SIPAS Style RBAC Matrix Card -->
<form action="{{ route('users.access.update', $user->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="rbac-card mb-4">
        
        <!-- Header Bar: Title + SIPAS Centang Semua & Hapus Semua -->
        <div class="rbac-header-bar">
            <div class="rbac-title">
                <i class="bi bi-shield-check text-success fs-5"></i>
                <span>Konfigurasi Akses</span>
            </div>
            <div class="rbac-actions">
                <button type="button" class="btn-rbac-check-all" onclick="checkAllAccess()">
                    <i class="bi bi-check2-all fs-6"></i> Centang Semua
                </button>
                <button type="button" class="btn-rbac-uncheck-all" onclick="uncheckAllAccess()">
                    <i class="bi bi-x-lg fs-6"></i> Hapus Semua
                </button>
            </div>
        </div>

        <!-- Table Matrix (SIPAS Style) -->
        <div class="rbac-table-container">
            <table class="rbac-table">
                <thead>
                    <tr>
                        <th style="min-width: 280px;">MENU</th>
                        <th style="width: 110px;" class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <span>LIHAT</span>
                                <button type="button" class="rbac-col-toggle-btn" onclick="toggleColumnAccess('index')" title="Toggle Semua Kolom Lihat">
                                    <i class="bi bi-dash-square"></i>
                                </button>
                            </div>
                        </th>
                        <th style="width: 110px;" class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <span>TAMBAH</span>
                                <button type="button" class="rbac-col-toggle-btn" onclick="toggleColumnAccess('create')" title="Toggle Semua Kolom Tambah">
                                    <i class="bi bi-dash-square"></i>
                                </button>
                            </div>
                        </th>
                        <th style="width: 110px;" class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <span>UBAH</span>
                                <button type="button" class="rbac-col-toggle-btn" onclick="toggleColumnAccess('edit')" title="Toggle Semua Kolom Ubah">
                                    <i class="bi bi-dash-square"></i>
                                </button>
                            </div>
                        </th>
                        <th style="width: 110px;" class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <span>DETAIL</span>
                                <button type="button" class="rbac-col-toggle-btn" onclick="toggleColumnAccess('show')" title="Toggle Semua Kolom Detail">
                                    <i class="bi bi-dash-square"></i>
                                </button>
                            </div>
                        </th>
                        <th style="width: 110px;" class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <span>HAPUS</span>
                                <button type="button" class="rbac-col-toggle-btn" onclick="toggleColumnAccess('delete')" title="Toggle Semua Kolom Hapus">
                                    <i class="bi bi-dash-square"></i>
                                </button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($menuGroups as $categoryName => $menus)
                        <!-- Category Subheader -->
                        <tr class="rbac-category-row">
                            <td colspan="6">
                                <i class="bi bi-collection me-1"></i> {{ $categoryName }}
                            </td>
                        </tr>

                        @foreach ($menus as $menuKey => $info)
                            @php
                                $userAccess = $user->accesses->firstWhere('menu_access', $menuKey);
                                $hasIndex = $userAccess ? ((string)$userAccess->index_acs === '1') : ($isAdmin);
                                $hasCreate = $userAccess ? ((string)$userAccess->create_acs === '1') : ($isAdmin);
                                $hasEdit = $userAccess ? ((string)$userAccess->edit_acs === '1') : ($isAdmin);
                                $hasShow = $userAccess ? ((string)$userAccess->show_acs === '1') : ($isAdmin);
                                $hasDelete = $userAccess ? ((string)$userAccess->delete_acs === '1') : ($isAdmin);
                                $allRowChecked = $hasIndex && $hasCreate && $hasEdit && $hasShow && $hasDelete;
                            @endphp
                            <tr data-menu="{{ $menuKey }}">
                                <!-- Menu Name + Row Master Checkbox -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="checkbox" class="form-check-input rbac-checkbox rbac-row-master" {{ $allRowChecked ? 'checked' : '' }} onchange="toggleRowAccess(this)" title="Centang / Hapus seluruh aksi modul ini">
                                        <span class="fw-semibold text-dark">{{ $info['label'] }}</span>
                                    </div>
                                </td>

                                <!-- LIHAT (index) -->
                                <td class="text-center">
                                    <input type="hidden" name="accesses[{{ $menuKey }}][index]" value="0">
                                    <input type="checkbox" name="accesses[{{ $menuKey }}][index]" value="1" class="form-check-input rbac-checkbox rbac-perm-checkbox" data-action="index" {{ $hasIndex ? 'checked' : '' }}>
                                </td>

                                <!-- TAMBAH (create) -->
                                <td class="text-center">
                                    <input type="hidden" name="accesses[{{ $menuKey }}][create]" value="0">
                                    <input type="checkbox" name="accesses[{{ $menuKey }}][create]" value="1" class="form-check-input rbac-checkbox rbac-perm-checkbox" data-action="create" {{ $hasCreate ? 'checked' : '' }}>
                                </td>

                                <!-- UBAH (edit) -->
                                <td class="text-center">
                                    <input type="hidden" name="accesses[{{ $menuKey }}][edit]" value="0">
                                    <input type="checkbox" name="accesses[{{ $menuKey }}][edit]" value="1" class="form-check-input rbac-checkbox rbac-perm-checkbox" data-action="edit" {{ $hasEdit ? 'checked' : '' }}>
                                </td>

                                <!-- DETAIL (show) -->
                                <td class="text-center">
                                    <input type="hidden" name="accesses[{{ $menuKey }}][show]" value="0">
                                    <input type="checkbox" name="accesses[{{ $menuKey }}][show]" value="1" class="form-check-input rbac-checkbox rbac-perm-checkbox" data-action="show" {{ $hasShow ? 'checked' : '' }}>
                                </td>

                                <!-- HAPUS (delete) -->
                                <td class="text-center">
                                    <input type="hidden" name="accesses[{{ $menuKey }}][delete]" value="0">
                                    <input type="checkbox" name="accesses[{{ $menuKey }}][delete]" value="1" class="form-check-input rbac-checkbox rbac-perm-checkbox" data-action="delete" {{ $hasDelete ? 'checked' : '' }}>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Footer Bar: Simpan Hak Akses & Batal -->
        <div class="rbac-footer-bar">
            <button type="submit" class="btn btn-success text-white fw-bold px-4 py-2 rounded-2 d-inline-flex align-items-center gap-2 shadow-sm">Simpan
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-light border px-4 py-2 rounded-2">
                Batal
            </a>
        </div>

    </div>

</form>

@endsection

@push('scripts')
<script src="{{ asset('js/users.js') }}"></script>
@endpush
