@extends('layouts.admin')

@section('title', 'Tambah Pengguna Baru — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item text-muted">pengaturan sistem</li>
        <li class="breadcrumb-item"><a href="{{ route('users.index') }}" class="text-decoration-none text-muted">kelola pengguna</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">tambah pengguna</li>
    </ol>
</nav>
@endsection

@section('content')

@php
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
        <h4 class="fw-bold text-dark mb-1">Tambah Pengguna Baru</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- Session & Validation Alert Messages -->
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-4 rounded-3 border-0 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2 mb-1">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <strong class="fs-sm">Terdapat kesalahan pengisian form:</strong>
    </div>
    <ul class="mb-0 small ps-4">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('users.store') }}" method="POST">
    @csrf

    <!-- Unified Card Wrapper -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        
        <!-- Card Body: Contains Left and Right Columns -->
        <div class="card-body p-4 bg-white">
            <div class="row g-4">
                <!-- Left: Account & Personal Info -->
                <div class="col-lg-5">
                    <div class="card-box bg-white border rounded-3 p-4 h-100">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center justify-content-between">
                            <span>Informasi Akun Karyawan</span>
                        </h6>

                        <!-- NRK -->
                        <div class="mb-3">
                            <label for="nrk" class="form-label small fw-semibold text-dark">
                                Nomor Registrasi Karyawan (NRK) <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nrk" id="nrk" class="form-control rounded-2 @error('nrk') is-invalid @enderror" value="{{ old('nrk') }}" required>
                            @error('nrk')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Employee Name -->
                        <div class="mb-3">
                            <label for="employee_name" class="form-label small fw-semibold text-dark">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="employee_name" id="employee_name" class="form-control rounded-2 @error('employee_name') is-invalid @enderror" value="{{ old('employee_name') }}" required>
                            @error('employee_name')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold text-dark">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email" id="email" class="form-control rounded-2 @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold text-dark">
                                Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" name="password" id="password" class="form-control rounded-2 @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Role: Super Admin Switch -->
                        <div class="p-3 bg-light rounded-3 border mt-4">
                            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 mb-0">
                                <div>
                                    <label class="form-check-label fw-bold text-dark small" for="is_admin">
                                        Role: Super Administrator
                                    </label>
                                    <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                                        Super Admin memiliki akses penuh (*unrestricted*) ke seluruh modul.
                                    </p>
                                </div>
                                <input class="form-check-input ms-3" type="checkbox" role="switch" name="is_admin" id="is_admin" value="1" {{ old('is_admin') ? 'checked' : '' }} style="width: 2.5em; height: 1.25em;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Initial RBAC Matrix Setup (SIPAS Style) -->
                <div class="col-lg-7">
                    <div class="rbac-card h-100 d-flex flex-column border rounded-3 overflow-hidden">
                        
                        <!-- Header Bar -->
                        <div class="rbac-header-bar">
                            <div class="rbac-title">
                                <span>Hak Akses Modul</span>
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

                        <!-- RBAC Matrix Table -->
                        <div class="rbac-table-container flex-grow-1">
                            <table class="rbac-table">
                                <thead>
                                    <tr>
                                        <th style="min-width: 220px;">MENU</th>
                                        <th style="width: 90px;" class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <span>LIHAT</span>
                                                <button type="button" class="rbac-col-toggle-btn" onclick="toggleColumnAccess('index')" title="Toggle Kolom Lihat"><i class="bi bi-dash-square"></i></button>
                                            </div>
                                        </th>
                                        <th style="width: 90px;" class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <span>TAMBAH</span>
                                                <button type="button" class="rbac-col-toggle-btn" onclick="toggleColumnAccess('create')" title="Toggle Kolom Tambah"><i class="bi bi-dash-square"></i></button>
                                            </div>
                                        </th>
                                        <th style="width: 90px;" class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <span>UBAH</span>
                                                <button type="button" class="rbac-col-toggle-btn" onclick="toggleColumnAccess('edit')" title="Toggle Kolom Ubah"><i class="bi bi-dash-square"></i></button>
                                            </div>
                                        </th>
                                        <th style="width: 90px;" class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <span>DETAIL</span>
                                                <button type="button" class="rbac-col-toggle-btn" onclick="toggleColumnAccess('show')" title="Toggle Kolom Detail"><i class="bi bi-dash-square"></i></button>
                                            </div>
                                        </th>
                                        <th style="width: 90px;" class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <span>HAPUS</span>
                                                <button type="button" class="rbac-col-toggle-btn" onclick="toggleColumnAccess('delete')" title="Toggle Kolom Hapus"><i class="bi bi-dash-square"></i></button>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($menuGroups as $categoryName => $menus)
                                        <tr class="rbac-category-row">
                                            <td colspan="6">
                                                <i class="bi bi-collection me-1"></i> {{ $categoryName }}
                                            </td>
                                        </tr>

                                        @foreach ($menus as $menuKey => $info)
                                            @php
                                                $oldPerms = old("accesses.{$menuKey}", []);
                                                $hasIndex = !empty($oldPerms['index']);
                                                $hasCreate = !empty($oldPerms['create']);
                                                $hasEdit = !empty($oldPerms['edit']);
                                                $hasShow = !empty($oldPerms['show']);
                                                $hasDelete = !empty($oldPerms['delete']);
                                                $allRowChecked = $hasIndex && $hasCreate && $hasEdit && $hasShow && $hasDelete;
                                            @endphp
                                            <tr data-menu="{{ $menuKey }}">
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <input type="checkbox" class="form-check-input rbac-checkbox rbac-row-master" {{ $allRowChecked ? 'checked' : '' }} onchange="toggleRowAccess(this)">
                                                        <span class="fw-semibold text-dark small">{{ $info['label'] }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <input type="hidden" name="accesses[{{ $menuKey }}][index]" value="0">
                                                    <input type="checkbox" name="accesses[{{ $menuKey }}][index]" value="1" class="form-check-input rbac-checkbox rbac-perm-checkbox" data-action="index" {{ $hasIndex ? 'checked' : '' }}>
                                                </td>
                                                <td class="text-center">
                                                    <input type="hidden" name="accesses[{{ $menuKey }}][create]" value="0">
                                                    <input type="checkbox" name="accesses[{{ $menuKey }}][create]" value="1" class="form-check-input rbac-checkbox rbac-perm-checkbox" data-action="create" {{ $hasCreate ? 'checked' : '' }}>
                                                </td>
                                                <td class="text-center">
                                                    <input type="hidden" name="accesses[{{ $menuKey }}][edit]" value="0">
                                                    <input type="checkbox" name="accesses[{{ $menuKey }}][edit]" value="1" class="form-check-input rbac-checkbox rbac-perm-checkbox" data-action="edit" {{ $hasEdit ? 'checked' : '' }}>
                                                </td>
                                                <td class="text-center">
                                                    <input type="hidden" name="accesses[{{ $menuKey }}][show]" value="0">
                                                    <input type="checkbox" name="accesses[{{ $menuKey }}][show]" value="1" class="form-check-input rbac-checkbox rbac-perm-checkbox" data-action="show" {{ $hasShow ? 'checked' : '' }}>
                                                </td>
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

                    </div>
                </div>
            </div>
        </div>

        <!-- Card Footer: Integrated Bottom Actions -->
        <div class="card-footer bg-light px-4 py-3 d-flex align-items-center justify-content-between border-0">
            <a href="{{ route('users.index') }}" class="btn btn-light border px-4 py-2 rounded-2">
                Batal
            </a>
            <button type="submit" class="btn btn-success text-white fw-bold px-4 py-2 rounded-2 d-inline-flex align-items-center gap-2 shadow-sm">
                Simpan
            </button>
        </div>

    </div>
</form>

@endsection

@push('scripts')
<script src="{{ asset('js/users.js') }}"></script>
@endpush