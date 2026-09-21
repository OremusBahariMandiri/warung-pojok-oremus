@extends('layouts.admin')

@section('title', 'Master Komponen HPP — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/hpp.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">management master hpp</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $list = $hppList ?? collect();
@endphp

<!-- Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold text-dark mb-1">Master Komponen HPP</h4>
    </div>
</div>

<!-- Session Alerts -->
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-0 shadow-sm"
    role="alert" style="background-color: var(--green-50); color: var(--green-600);">
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

@if (session('warning'))
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('warning') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Main Card -->
<div class="card-box px-3 border rounded-3 overflow-hidden shadow-sm bg-white mb-4">

    <!-- Toolbar -->
    <div class="py-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <!-- Search -->
        <div class="position-relative hpp-search-box">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="font-size:0.8rem;"></i>
            <input type="text" id="dtSearchInput"
                class="form-control form-control-sm ps-5 pe-3 rounded-2"
                placeholder="Cari nama komponen, satuan, biaya...">
        </div>

        <!-- Buttons -->
        <div class="d-flex align-items-center gap-2">
            <button type="button"
                class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-2 px-3 position-relative"
                data-bs-toggle="modal" data-bs-target="#modalFilterHpp">
                <i class="bi bi-funnel"></i> Filter
                <span id="activeFilterBadge"
                    class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                    <span class="visually-hidden">Filter Aktif</span>
                </span>
            </button>
            <a href="{{ route('hpp.create') }}"
                class="btn btn-sm btn-success rounded-2 text-white fw-semibold d-inline-flex align-items-center gap-2 px-3">
                <i class="bi bi-plus-lg"></i> Tambah
            </a>
        </div>
    </div>

    <!-- DataTable -->
    {{--
        PENTING: Tidak pakai wrapper .table-responsive di sini.
        DataTables Responsive menangani sendiri kolom mana yang
        disembunyikan agar tidak perlu scroll horizontal.
    --}}
    <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap" id="hppDataTable">
        <thead>
            <tr>
                <th style="width:50px;" class="text-center">No</th>
                <th>Nama Komponen HPP</th>
                <th style="width:140px;">Satuan Pemakaian</th>
                <th class="text-end" style="width:180px;">Biaya Standar (Unit Cost)</th>
                <th class="text-center" style="width:140px;">Produk Terkait</th>
                <th class="text-center" style="width:140px;">Tanggal Dibuat</th>
                <th class="text-center no-sort" style="width:120px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($list as $item)
                <tr>
                    <td class="text-center text-muted fw-medium"
                        data-label="No">
                        {{ $loop->iteration }}
                    </td>

                    <td class="text-dark"
                        data-label="Nama Komponen">
                        {{ $item->name }}
                    </td>

                    <td data-label="Satuan">
                        <div class="text-center">
                            {{ strtoupper($item->unit) }}
                        </div>
                    </td>

                    <td class="text-end text-dark"
                        data-label="Unit Cost">
                        Rp {{ number_format($item->unit_cost, 0, ',', '.') }}
                    </td>

                    <td class="text-center"
                        data-label="Produk">
                        {{ (int)($item->products_count ?? 0) }} Produk
                    </td>

                    {{-- Kolom ini yang disembunyikan duluan saat layar sempit --}}
                    <td class="text-center text-muted small"
                        data-label="Dibuat">
                        {{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}
                    </td>

                    <td class="text-center">
                        <!-- Desktop Buttons -->
                        <div class="d-none d-md-inline-flex gap-1 justify-content-center">
                            <a href="{{ route('hpp.show', $item->id) }}"
                               class="btn btn-sm btn-info text-white px-2 py-1 rounded-2 shadow-none"
                               title="Detail"
                               style="background-color:#0ea5e9;border-color:#0ea5e9;">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('hpp.edit', $item->id) }}"
                               class="btn btn-sm btn-warning text-white px-2 py-1 rounded-2 shadow-none"
                               title="Edit"
                               style="background-color:#f59e0b;border-color:#f59e0b;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-danger text-white px-2 py-1 rounded-2 shadow-none"
                                    title="Hapus"
                                    style="background-color:#ef4444;border-color:#ef4444;"
                                    onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->name) }}', {{ (int)($item->products_count ?? 0) }})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                        <!-- Mobile Dropdown -->
                        <div class="dropdown d-inline-block d-md-none">
                            <button class="btn btn-sm btn-light border dropdown-toggle shadow-none"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 py-1">
                                <li>
                                    <a class="dropdown-item py-2 small" href="{{ route('hpp.show', $item->id) }}">
                                        <i class="bi bi-eye text-info me-2"></i> Rincian Komponen
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 small" href="{{ route('hpp.edit', $item->id) }}">
                                        <i class="bi bi-pencil text-warning me-2"></i> Edit Komponen
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <button type="button" class="dropdown-item py-2 small text-danger"
                                            onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->name) }}', {{ (int)($item->products_count ?? 0) }})">
                                        <i class="bi bi-trash me-2"></i> Hapus Komponen
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- MODAL FILTER -->
<div class="modal fade" id="modalFilterHpp" tabindex="-1" aria-labelledby="modalFilterHppLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-content-minimal">
            <div class="modal-header modal-header-minimal d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-2"
                        style="width:30px;height:30px;background-color:var(--accent-light,#f0fdf4);flex-shrink:0;">
                        <i class="bi bi-funnel text-success" style="font-size:14px;"></i>
                    </div>
                    <div class="fw-semibold text-dark" style="font-size:0.85rem;" id="modalFilterHppLabel">Filter Komponen HPP</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-body-minimal d-flex flex-column gap-3">
                <div>
                    <label for="modalFilterUnit" class="form-label small fw-semibold text-muted mb-1">Satuan Pemakaian</label>
                    <select id="modalFilterUnit" class="form-select form-select-sm rounded-2">
                        <option value="">Semua Satuan</option>
                        @foreach ($list->pluck('unit')->unique()->filter() as $unit)
                            <option value="{{ strtoupper($unit) }}">{{ strtoupper($unit) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="modalFilterUsage" class="form-label small fw-semibold text-muted mb-1">Status Penggunaan</label>
                    <select id="modalFilterUsage" class="form-select form-select-sm rounded-2">
                        <option value="">Semua Status Penggunaan</option>
                        <option value="used">Sudah Digunakan Produk</option>
                        <option value="unused">Belum Digunakan</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer modal-footer-minimal d-flex justify-content-between">
                <button type="button" id="btnResetFilter" class="btn btn-sm btn-light border px-3 rounded-2">Reset</button>
                <button type="button" id="btnApplyFilter" class="btn btn-sm btn-success px-3 rounded-2 text-white" data-bs-dismiss="modal">Terapkan Filter</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DELETE -->
<div class="modal fade" id="modalDeleteHpp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-content-minimal">
            <div class="modal-body modal-body-minimal text-center py-4">
                <div class="avatar-circle mx-auto mb-3 bg-danger bg-opacity-10 text-danger"
                    style="width:54px;height:54px;font-size:1.5rem;">
                    <i class="bi bi-trash"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Hapus Komponen HPP?</h6>
                <p class="text-muted small mb-2">
                    Komponen "<span id="deleteHppName" class="fw-semibold text-dark"></span>" akan dihapus dari master data.
                </p>
                <div id="deleteWarningProductCount"
                    class="alert alert-warning py-1 px-2 small mb-3 d-none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Digunakan oleh <span id="warningProductCount" class="fw-bold"></span> produk.
                </div>
                <form id="deleteHppForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-light border px-3 rounded-2"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger px-3 rounded-2">Ya, Hapus</button>
                    </div>
                </form>
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
<script src="{{ asset('js/hpp.js') }}"></script>
@endpush