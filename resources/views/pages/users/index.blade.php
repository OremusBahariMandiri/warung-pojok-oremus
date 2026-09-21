@extends('layouts.admin')

@section('title', 'Kelola Pengguna & Hak Akses — Warung Pojok Oremus')

@push('styles')
<!-- DataTables BSD & Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item text-muted">pengaturan sistem</li>
        <li class="breadcrumb-item active text-dark" aria-current="page">kelola pengguna</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    // Hanya tampilkan pengguna biasa (non-admin / bukan Super Admin)
    $userList = ($users ?? collect())
        ->filter(fn ($u) => ! $u->is_admin)
        ->values();

    $totalUsers = $userList->count();
@endphp

<!-- Header Title di atas DataTable -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Manajemen Pengguna</h4>
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

{{-- ═══════════════════════════════════════════════════════════════
     DESKTOP LAYOUT (>= 768px) — DataTables Table
     ═══════════════════════════════════════════════════════════════ --}}
<div class="card-box border rounded-3 overflow-hidden shadow-sm bg-white mb-4 d-none d-md-block">

    <!-- Action & Filter Header -->
    <div class="py-3 d-flex align-items-center justify-content-between gap-3">
        <!-- Left: Search Box -->
        <div class="position-relative user-search-box">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" id="dtSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2" placeholder="Cari nama pengguna, email, atau NRK...">
        </div>

        <!-- Right: Filter Button -->
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-2 px-3 position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterUser">
                <i class="bi bi-funnel"></i> Filter
                <span id="activeFilterBadge" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                    <span class="visually-hidden">Filter Aktif</span>
                </span>
            </button>
            <a href="{{ route('users.create') }}" class="btn btn-sm btn-success rounded-2 text-white fw-semibold d-inline-flex align-items-center gap-2 px-3">
                <i class="bi bi-plus-lg"></i> Tambah
            </a>
        </div>
    </div>

    <!-- DataTables Table -->
    <div class="users-dt-scroll">
        <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap" id="usersDataTable">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Pengguna</th>
                    <th style="width: 120px;" class="text-center">NRK</th>
                    <th style="width: 140px;" class="text-center">Peran</th>
                    <th style="width: 170px;" class="text-center">Hak Akses</th>
                    <th style="width: 140px;" class="text-center">Terdaftar</th>
                    <th style="width: 140px;" class="text-center no-sort">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($userList as $u)
                    @php
                        $name = $u->employee_name ?? $u->email;
                        $initials = strtoupper(substr(trim($name), 0, 2));
                        $isAdmin = (bool)$u->is_admin;
                        $accessCount = $u->accesses ? $u->accesses->where('index_acs', '1')->count() : 0;
                        $roleSlug = $isAdmin ? 'admin' : 'cashier';
                    @endphp
                    <tr data-role="{{ $roleSlug }}">
                        {{-- #1 No --}}
                        <td class="text-center text-muted fw-medium small">{{ $loop->iteration }}</td>

                        {{-- #2 Nama & Email --}}
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar-sm {{ $isAdmin ? 'user-avatar-admin' : 'user-avatar-cashier' }}">
                                    {{ $initials }}
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold text-dark fs-sm">{{ $name }}</span>
                                    <span class="text-muted small" style="font-size: 0.76rem;">{{ $u->email }}</span>
                                </div>
                            </div>
                        </td>

                        {{-- #3 NRK --}}
                        <td class="text-center">
                            <span class="nrk-tag">{{ $u->nrk }}</span>
                        </td>

                        {{-- #4 Peran --}}
                        <td class="text-center">
                            @if ($isAdmin)
                                <span class="badge-role-admin">Super Admin</span>
                            @else
                                <span class="badge-role-cashier"> Staff</span>
                            @endif
                        </td>

                        {{-- #5 Hak Akses --}}
                        <td class="text-center">
                            @if ($isAdmin)
                                <span class="badge text-success px-2 py-1 small">Full Access
                                </span>
                            @else
                                <span class="text-primary px-2 py-1 small">
                                    {{ $accessCount }} Modul Diizinkan
                                </span>
                            @endif
                        </td>

                        {{-- #6 Terdaftar --}}
                        <td class="text-center text-muted small">
                            <div>{{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}</div>
                            <div style="font-size:0.7rem;">{{ $u->created_at ? $u->created_at->diffForHumans() : '' }}</div>
                        </td>

                        {{-- #7 Aksi --}}
                        <td class="text-center">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('users.show', $u->id) }}" class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1" title="Profil Pengguna">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('users.access.show', $u->id) }}" class="btn btn-sm btn-outline-success rounded-2 px-2 py-1" title="Atur Hak Akses">
                                    <i class="bi bi-shield-lock"></i>
                                </a>
                                <a href="{{ route('users.edit', $u->id) }}" class="btn btn-sm btn-outline-warning rounded-2 px-2 py-1 text-dark" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if (auth()->id() !== $u->id)
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" title="Hapus"
                                            onclick="openDeleteUserModal({{ $u->id }}, '{{ addslashes($name) }}', '{{ addslashes($u->email) }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════
     MOBILE LAYOUT (< 768px) — Responsive Card List
     ═══════════════════════════════════════════════════════════════ --}}
<div class="d-block d-md-none mb-4">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        
        <!-- Mobile Header Toolbar -->
        <div class="card-header bg-white p-3 border-0 d-flex flex-column gap-2">
            <!-- Search Bar -->
            <div class="position-relative w-100">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="mobileUserSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2 w-100" placeholder="Cari nama, email, NRK...">
            </div>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-2 w-100">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center justify-content-center gap-1 px-3 grow position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterUser">
                    <i class="bi bi-funnel"></i> Filter
                    <span id="activeFilterBadgeMobile" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                        <span class="visually-hidden">Filter Aktif</span>
                    </span>
                </button>
                <a href="{{ route('users.create') }}" class="btn btn-sm btn-success rounded-2 text-white fw-semibold d-inline-flex align-items-center justify-content-center gap-1 px-3 grow">
                    <i class="bi bi-plus-lg"></i> Tambah
                </a>
            </div>
        </div>

        <!-- Mobile Card Items Container -->
        <div class="card-body p-3 pt-0" id="mobileUserCards">
            @forelse ($userList as $u)
                @php
                    $name = $u->employee_name ?? $u->email;
                    $initials = strtoupper(substr(trim($name), 0, 2));
                    $isAdmin = (bool)$u->is_admin;
                    $accessCount = $u->accesses ? $u->accesses->where('index_acs', '1')->count() : 0;
                    $roleSlug = $isAdmin ? 'admin' : 'cashier';
                    $searchKeywords = strtolower("{$name} {$u->email} {$u->nrk} " . ($isAdmin ? 'super admin' : 'kasir'));
                @endphp

                <div class="mobile-user-card"
                     data-role="{{ $roleSlug }}"
                     data-search="{{ $searchKeywords }}">

                    <!-- Card Header -->
                    <div class="muc-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar-sm {{ $isAdmin ? 'user-avatar-admin' : 'user-avatar-cashier' }}">
                                {{ $initials }}
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-dark small">{{ $name }}</span>
                                <span class="text-muted" style="font-size: 0.72rem;">{{ $u->email }}</span>
                            </div>
                        </div>
                        <div>
                            @if ($isAdmin)
                                <span class="badge-role-admin" style="font-size: 0.7rem;"><i class="bi bi-shield-fill-check"></i> Admin</span>
                            @else
                                <span class="badge-role-cashier" style="font-size: 0.7rem;"><i class="bi bi-person-fill"></i> Kasir</span>
                            @endif
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="muc-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small">NRK:</span> <span class="nrk-tag">{{ $u->nrk }}</span>
                        </div>
                        <div>
                            <a href="{{ route('users.access.show', $u->id) }}" class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1" style="font-size:0.75rem;">
                                <i class="bi bi-shield-shaded me-1"></i> Hak Akses
                            </a>
                        </div>
                    </div>

                    <!-- Card Footer Actions -->
                    <div class="muc-footer">
                        <a href="{{ route('users.show', $u->id) }}" class="btn btn-sm btn-outline-secondary rounded-2 flex-grow-1">
                            <i class="bi bi-eye me-1"></i> Detail
                        </a>
                        <a href="{{ route('users.edit', $u->id) }}" class="btn btn-sm btn-outline-warning rounded-2 px-3 text-dark">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if (auth()->id() !== $u->id)
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-3"
                                    onclick="openDeleteUserModal({{ $u->id }}, '{{ addslashes($name) }}', '{{ addslashes($u->email) }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted small">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    Belum ada pengguna yang terdaftar. Klik <strong>+ Tambah</strong> untuk menambahkan pengguna baru.
                </div>
            @endforelse

            <div id="mobileUserNoResult" class="text-center py-4 text-muted small d-none">
                Tidak ada pengguna yang cocok dengan pencarian atau filter.
            </div>
        </div>

    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     MODAL FILTER PERAN PENGGUNA
     ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalFilterUser" tabindex="-1" aria-labelledby="modalFilterUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark" id="modalFilterUserLabel">
                    <i class="bi bi-funnel me-1"></i> Filter Peran Pengguna
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div>
                    <label for="modalFilterRole" class="form-label small fw-semibold text-dark mb-1">Peran</label>
                    <select id="modalFilterRole" class="form-select form-select-sm rounded-2">
                        <option value="">Semua Peran</option>
                        <option value="cashier">Staff</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-light border rounded-2 px-3" id="btnResetFilter" data-bs-dismiss="modal">
                    Reset
                </button>
                <button type="button" class="btn btn-sm btn-success text-white fw-semibold rounded-2 px-4" id="btnApplyFilter" data-bs-dismiss="modal">
                    Terapkan
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     MODAL KONFIRMASI HAPUS PENGGUNA
     ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalDeleteUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-body text-center py-4 px-4">
                <div class="avatar-circle mx-auto mb-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center rounded-circle" style="width: 54px; height: 54px; font-size: 1.5rem;">
                    <i class="bi bi-trash"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Hapus Pengguna Ini?</h6>
                <p class="text-muted small mb-3">
                    Pengguna "<strong id="deleteUserName" class="text-dark"></strong>" (<span id="deleteUserEmail" class="text-muted"></span>) beserta seluruh hak aksesnya akan dihapus permanen.
                </p>

                <form id="deleteUserForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-light border px-3 rounded-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger px-3 rounded-2 fw-semibold">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- jQuery & DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="{{ asset('js/users.js') }}"></script>
@endpush