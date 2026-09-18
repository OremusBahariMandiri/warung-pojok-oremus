@extends('layouts.admin')

@section('title', 'Manajemen Produk — Warung Pojok Oremus')

@push('styles')
<!-- DataTables BSD & Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/products.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">management produk</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $productList = $products ?? collect();
@endphp

<!-- Header Title di atas DataTable -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold text-dark mb-1">Manajemen Produk</h4>
        <p class="text-muted small mb-0">Kelola katalog produk, resep/komposisi HPP, penetapan harga jual, dan stok inventori.</p>
    </div>
</div>

<!-- Session Alert Messages -->
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-0 shadow-sm" role="alert" style="background-color: var(--green-50); color: var(--green-600);">
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

<!-- Main Section: Toolbar & DataTables Container -->
<div class="card-box px-3 border rounded-3 overflow-hidden shadow-sm bg-white mb-4">
    <!-- Action & Filter Header -->
    <div class="py-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <!-- Left: Search Box -->
        <div class="position-relative product-search-box">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" id="dtSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2" placeholder="Cari nama produk / SKU...">
        </div>

        <!-- Right: Action & Filter Buttons -->
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-2 px-3 position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterProduct">
                <i class="bi bi-funnel"></i> Filter
                <span id="activeFilterBadge" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                    <span class="visually-hidden">Filter Aktif</span>
                </span>
            </button>
            <a href="{{ route('products.create') }}" class="btn btn-sm btn-success rounded-2 text-white fw-semibold d-inline-flex align-items-center gap-2 px-3">
                <i class="bi bi-plus-lg"></i> Tambah
            </a>
        </div>
    </div>

    <!-- DataTables Table -->
    <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap" id="productsDataTable">
        <thead>
            <tr>
                <th style="width: 50px;" class="text-center">No</th>
                <th>Nama Produk</th>
                <th style="width: 120px;">Sku</th>
                <th style="width: 120px;">Satuan</th>
                <th class="text-end" style="width: 150px;">Harga Jual</th>
                <th class="text-end" style="width: 150px;">HPP Total</th>
                <th class="text-center" style="width: 110px;">Margin %</th>
                <th class="text-center" style="width: 140px;">Stok</th>
                <th class="text-center" style="width: 130px;">Metode HPP</th>
                <th class="text-center no-sort" style="width: 80px;">Gambar</th>
                <th class="text-center no-sort" style="width: 130px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($productList as $item)
                @php
                    $hpp = (float)($item->current_hpp ?? $item->unit_price ?? 0);
                    $selling = (float)($item->selling_price ?? 0);
                    $marginAmount = $selling - $hpp;
                    $marginPercent = $selling > 0 ? round(($marginAmount / $selling) * 100, 1) : 0;
                    $stockStatus = ((int)$item->current_stock <= (int)$item->min_stock) ? 'kritis' : 'aman';
                    $unitName = $item->unit->short_name ?? $item->unit->unit_name ?? 'PORSI';
                @endphp
                <tr data-stock="{{ $stockStatus }}" data-hpp="{{ $item->hpp_method }}">

                    {{-- #1 Nomor --}}
                    <td class="text-center text-muted fw-medium"
                        data-label="No">
                        {{ $loop->iteration }}
                    </td>

                    {{-- #2 Nama Produk --}}
                    <td data-label="Nama Produk">
                        <div class="text-dark text-center">{{ $item->prod_name }}</div>
                    </td>

                    {{-- #3 SKU --}}
                    <td data-label="SKU">
                        <div class="text-dark text-center">
                            {{ $item->sku ?? 'PRD-' . sprintf('%03d', $item->id) }}
                        </div>
                    </td>

                    {{-- #4 Satuan --}}
                    <td data-label="Satuan">
                        <div class="text-dark text-center">
                            {{ strtoupper($unitName) }}
                        </div>
                    </td>

                    {{-- #5 Harga Jual --}}
                    <td class="text-dark text-center"
                        data-label="Harga Jual">
                        Rp {{ number_format($item->selling_price, 0, ',', '.') }}
                    </td>

                    {{-- #6 HPP Total --}}
                    <td class="text-center text-secondary"
                        data-label="HPP">
                        Rp {{ number_format($hpp, 0, ',', '.') }}
                    </td>

                    {{-- #7 Margin % --}}
                    <td class="text-center {{ $marginPercent >= 30 ? 'text-success' : ($marginPercent >= 15 ? 'text-warning' : 'text-danger') }}"
                        data-label="Margin">
                        {{ $marginPercent }}%
                    </td>

                    {{-- #8 Stok --}}
                    <td class="text-center  text-dark"
                        data-label="Stok">
                        <span class="{{ (int)$item->current_stock <= (int)$item->min_stock ? 'text-danger fw-bold' : 'text-dark' }}">
                            {{ $item->current_stock }} {{ $unitName }}
                        </span>
                    </td>

                    {{-- #9 Metode HPP (tersembunyi di mobile via CSS) --}}
                    <td class="text-center text-dark"
                        data-label="Metode HPP">
                        {{ $item->hpp_method === 'calculated' ? 'Otomatis' : 'Manual' }}
                    </td>

                    {{-- #10 Gambar (tersembunyi di mobile via CSS) --}}
                    <td class="text-center">
                        @if ($item->thumbnail)
                            <img src="{{ asset('storage/' . $item->thumbnail) }}"
                                alt="{{ $item->prod_name }}"
                                style="width: 42px; height: 42px; object-fit: cover; border-radius: 6px; border: 1px solid #dee2e6;">
                        @else
                            <div class="bg-light text-muted rounded-2 border d-inline-flex align-items-center justify-content-center"
                                style="width: 42px; height: 42px;">
                                <i class="bi bi-image text-secondary"></i>
                            </div>
                        @endif
                    </td>

                    {{-- #11 Aksi --}}
                    <td class="text-center">
                        <!-- Desktop Action Buttons (juga dipakai di mobile card) -->
                        <div class="d-none d-md-inline-flex gap-1 justify-content-center">
                            <a href="{{ route('products.show', $item->id) }}"
                               class="btn btn-sm btn-info text-white px-2 py-1 rounded-2 shadow-none"
                               title="Detail"
                               style="background-color: #0ea5e9; border-color: #0ea5e9;">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('products.edit', $item->id) }}"
                               class="btn btn-sm btn-warning text-white px-2 py-1 rounded-2 shadow-none"
                               title="Edit"
                               style="background-color: #f59e0b; border-color: #f59e0b;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-danger text-white px-2 py-1 rounded-2 shadow-none"
                                    title="Hapus"
                                    style="background-color: #ef4444; border-color: #ef4444;"
                                    onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->prod_name) }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                        <!-- Mobile Dropdown (disembunyikan CSS di mobile) -->
                        <div class="dropdown d-inline-block d-md-none">
                            <button class="btn btn-sm btn-light border dropdown-toggle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 py-1">
                                <li>
                                    <a class="dropdown-item py-2 small" href="{{ route('products.show', $item->id) }}">
                                        <i class="bi bi-eye text-info me-2"></i> Detail Produk
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 small" href="{{ route('products.edit', $item->id) }}">
                                        <i class="bi bi-pencil text-warning me-2"></i> Edit Produk
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <button type="button" class="dropdown-item py-2 small text-danger"
                                            onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->prod_name) }}')">
                                        <i class="bi bi-trash me-2"></i> Hapus Produk
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

<!-- MODAL FILTER PRODUK -->
<div class="modal fade" id="modalFilterProduct" tabindex="-1" aria-labelledby="modalFilterProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-content-minimal">
            <div class="modal-header modal-header-minimal d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-2"
                        style="width:30px;height:30px;background-color:var(--accent-light,#f0fdf4);flex-shrink:0;">
                        <i class="bi bi-funnel text-success" style="font-size:14px;"></i>
                    </div>
                    <div class="fw-semibold text-dark" style="font-size:0.85rem;" id="modalFilterProductLabel">Filter Produk</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-body-minimal d-flex flex-column gap-3">
                <div>
                    <label for="modalFilterUnit" class="form-label small fw-semibold text-muted mb-1">Satuan Produk</label>
                    <select id="modalFilterUnit" class="form-select form-select-sm rounded-2">
                        <option value="">Semua Satuan</option>
                        @foreach ($productList->map(fn($p) => strtoupper($p->unit->short_name ?? $p->unit->unit_name ?? ''))->unique()->filter() as $unitName)
                            <option value="{{ $unitName }}">{{ $unitName }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="modalFilterHppMethod" class="form-label small fw-semibold text-muted mb-1">Metode HPP</label>
                    <select id="modalFilterHppMethod" class="form-select form-select-sm rounded-2">
                        <option value="">Semua Metode HPP</option>
                        <option value="calculated">Otomatis (Komposisi)</option>
                        <option value="manual">Manual Fixed</option>
                    </select>
                </div>

                <div>
                    <label for="modalFilterStock" class="form-label small fw-semibold text-muted mb-1">Status Stok</label>
                    <select id="modalFilterStock" class="form-select form-select-sm rounded-2">
                        <option value="">Semua Status Stok</option>
                        <option value="aman">Stok Aman</option>
                        <option value="kritis">Stok Kritis / Habis</option>
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

<!-- MODAL CONFIRM DELETE PRODUK -->
<div class="modal fade" id="modalDeleteProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-content-minimal">
            <div class="modal-body modal-body-minimal text-center py-4">
                <div class="avatar-circle mx-auto mb-3 bg-danger bg-opacity-10 text-danger" style="width: 54px; height: 54px; font-size: 1.5rem;">
                    <i class="bi bi-trash"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Hapus Produk Ini?</h6>
                <p class="text-muted small mb-3">Produk "<span id="deleteProductName" class="fw-semibold text-dark"></span>" akan dihapus permanen dari inventori.</p>

                <form id="deleteProductForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-light border px-3 rounded-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger px-3 rounded-2">Ya, Hapus</button>
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
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="{{ asset('js/products.js') }}"></script>
@endpush