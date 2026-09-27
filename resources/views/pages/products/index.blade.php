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
            <input type="text" id="dtSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2" placeholder="Cari kode produk, nama, satuan...">
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
                <th style="width: 130px;">Kode Produk</th>
                <th>Nama Produk</th>
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
                    $firstHpp = $item->productHpps->first();
                    $selling = (float)($firstHpp?->selling_price ?? $item->selling_price ?? 0);
                    $hpp = (float)($firstHpp?->current_hpp ?? $item->unit_price ?? 0);
                    $marginAmount = $selling - $hpp;
                    $marginPercent = $selling > 0 ? round(($marginAmount / $selling) * 100, 1) : 0;
                    $stockStatus = ((int)$item->current_stock <= (int)$item->min_stock) ? 'kritis' : 'aman';
                    $unitName = $item->unit->short_name ?? $item->unit->unit_name ?? 'PCS';
                    $hppMethod = $firstHpp?->hpp_method ?? $item->hpp_method ?? 'MANUAL';
                @endphp
                <tr data-stock="{{ $stockStatus }}" data-hpp="{{ $hppMethod }}">

                    {{-- #1 Nomor --}}
                    <td class="text-center text-muted fw-medium" data-label="No">
                        {{ $loop->iteration }}
                    </td>

                    {{-- #2 Kode Produk (Sebelum Nama Produk) --}}
                    <td data-label="Kode Produk">
                        <div class="text-dark font-monospace text-center">
                            {{ $item->prod_code ?? $item->sku ?? ('PRD-' . sprintf('%05d', $item->id)) }}
                        </div>
                    </td>

                    {{-- #3 Nama Produk --}}
                    <td data-label="Nama Produk">
                        <div class="text-dark fw-medium">{{ $item->prod_name }}</div>
                    </td>

                    {{-- #4 Satuan --}}
                    <td data-label="Satuan">
                        <div class="text-dark text-center">
                            {{ strtoupper($unitName) }}
                        </div>
                    </td>

                    {{-- #5 Harga Jual --}}
                    <td class="text-dark text-end font-monospace fw-semibold" data-label="Harga Jual">
                        Rp {{ number_format($selling, 0, ',', '.') }}
                    </td>

                    {{-- #6 HPP Total --}}
                    <td class="text-end text-secondary font-monospace" data-label="HPP">
                        Rp {{ number_format($hpp, 0, ',', '.') }}
                    </td>

                    {{-- #7 Margin % --}}
                    <td class="text-center fw-bold {{ $marginPercent >= 30 ? 'text-success' : ($marginPercent >= 15 ? 'text-warning' : 'text-danger') }}" data-label="Margin">
                        {{ $marginPercent }}%
                    </td>

                    {{-- #8 Stok --}}
                    <td class="text-center text-dark" data-label="Stok">
                        <span class="{{ (int)$item->current_stock <= (int)$item->min_stock ? 'text-danger fw-bold' : 'text-dark' }}">
                            {{ $item->current_stock }} {{ $unitName }}
                        </span>
                    </td>

                    {{-- #9 Metode HPP --}}
                    <td class="text-center text-dark" data-label="Metode HPP">
                        {{ strtoupper($hppMethod) === 'CALCULATED' ? 'Otomatis' : 'Manual' }}
                    </td>

                    {{-- #10 Gambar --}}
                    <td class="text-center">
                        @if ($item->thumbnail && !in_array($item->thumbnail, ['thumbnail/default.png', 'products/default.png']))
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
                        <!-- Desktop Action Buttons -->
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
                                    onclick="openDeleteModal('{{ $item->id }}', '{{ addslashes($item->prod_name) }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                        <!-- Mobile Action Dropdown -->
                        <div class="d-inline-block d-md-none">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle px-2 py-1"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('products.show', $item->id) }}">
                                            <i class="bi bi-eye text-info"></i> Detail Produk
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('products.edit', $item->id) }}">
                                            <i class="bi bi-pencil text-warning"></i> Edit Produk
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <button class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                onclick="openDeleteModal('{{ $item->id }}', '{{ addslashes($item->prod_name) }}')">
                                            <i class="bi bi-trash"></i> Hapus Produk
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Filter Produk -->
<div class="modal fade" id="modalFilterProduct" tabindex="-1" aria-labelledby="modalFilterProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom py-2 px-3 bg-light">
                <h6 class="modal-title fw-bold text-dark fs-sm d-flex align-items-center gap-2" id="modalFilterProductLabel">
                    <i class="bi bi-funnel text-success"></i> Filter Produk
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Filter Satuan -->
                <div class="mb-3">
                    <label class="form-label text-muted fs-xs fw-semibold">Satuan Produk</label>
                    <select class="form-select form-select-sm rounded-2" id="modalFilterUnit">
                        <option value="">Semua Satuan</option>
                        @php
                            $uniqueUnits = $productList->pluck('unit.short_name')->filter()->unique()->sort();
                        @endphp
                        @foreach ($uniqueUnits as $u)
                            <option value="{{ strtoupper($u) }}">{{ strtoupper($u) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Stok -->
                <div class="mb-2">
                    <label class="form-label text-muted fs-xs fw-semibold">Status Stok</label>
                    <select class="form-select form-select-sm rounded-2" id="modalFilterStock">
                        <option value="">Semua Status</option>
                        <option value="kritis">Stok Kritis / Menipis</option>
                        <option value="aman">Stok Aman</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" id="btnResetFilter">
                    Reset Filter
                </button>
                <button type="button" class="btn btn-sm btn-success text-white px-3 rounded-2" id="btnApplyFilter" data-bs-dismiss="modal">
                    Terapkan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Produk -->
<div class="modal fade" id="modalDeleteProduct" tabindex="-1" aria-labelledby="modalDeleteProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom py-2 px-3 bg-light">
                <h6 class="modal-title fw-bold text-danger fs-sm d-flex align-items-center gap-2" id="modalDeleteProductLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i> Hapus Produk
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 text-center">
                <p class="text-secondary small mb-1">Apakah Anda yakin ingin menghapus produk ini?</p>
                <p class="fw-bold text-dark mb-0 fs-sm" id="deleteProductName">-</p>
                <p class="text-muted fs-xs mt-2 mb-0">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer border-top py-2 px-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">
                    Batal
                </button>
                <form id="deleteProductForm" action="" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger text-white px-3 rounded-2">
                        Ya, Hapus
                    </button>
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
<script src="{{ asset('js/products.js') }}"></script>
@endpush