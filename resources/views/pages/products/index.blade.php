@extends('layouts.admin')

@section('title', 'Manajemen Produk — Warung Pojok Oremus')
@section('page-title', 'Manajemen Produk')

@push('styles')
<!-- DataTables BSD & Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Manajemen Produk</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $productList = $products ?? collect();
    $totalCount = $productList->count();
    $safeStockCount = $productList->filter(fn($p) => (int)$p->current_stock > (int)$p->min_stock)->count();
    $lowStockCount = $productList->filter(fn($p) => (int)$p->current_stock <= (int)$p->min_stock)->count();

    $avgMargin = 0;
    if ($totalCount > 0) {
        $margins = $productList->map(function($p) {
            $selling = (float)($p->selling_price ?? 0);
            $hpp = (float)($p->current_hpp ?? $p->unit_price ?? 0);
            return $selling > 0 ? (($selling - $hpp) / $selling) * 100 : 0;
        });
        $avgMargin = round($margins->avg(), 1);
    }
@endphp

<!-- Session Alert Messages -->
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-4 rounded-3 border-0 shadow-sm" role="alert" style="background-color: var(--green-50); color: var(--green-600);">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('success') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-4 rounded-3 border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('error') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Main Section: Toolbar & DataTables Container -->
<div class="card-box">
    <!-- Action & Filter Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <!-- Left: Search & Filters -->
        <div class="d-flex flex-wrap align-items-center gap-2 grow">
            <div class="position-relative grow" style="max-width: 320px;">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="dtSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-3" placeholder="Cari nama produk / SKU..." style="border-color: var(--border);">
            </div>

            <select id="dtFilterStock" class="form-select form-select-sm rounded-3 w-auto" style="border-color: var(--border);">
                <option value="">Semua Status Stok</option>
                <option value="aman">Stok Aman</option>
                <option value="kritis">Stok Kritis / Habis</option>
            </select>

            <select id="dtFilterHpp" class="form-select form-select-sm rounded-3 w-auto" style="border-color: var(--border);">
                <option value="">Semua Metode HPP</option>
                <option value="calculated">Hitung Komposisi</option>
                <option value="manual">Manual Fixed</option>
            </select>
        </div>

        <!-- Right: Action Buttons -->
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('products.create') }}" class="btn btn-sm btn-success rounded-3 text-white fw-semibold d-inline-flex align-items-center gap-2 px-3">
                <i class="bi bi-plus-circle-fill"></i> Tambah
            </a>
        </div>
    </div>

    <!-- DataTables Table -->
    <div class="table-responsive">
        <table class="table table-products align-middle mb-0 w-100" id="productsDataTable">
            <thead>
                <tr>
                    <th style="width: 30px;" class="no-sort">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                    </th>
                    <th>Info Produk & SKU</th>
                    <th>Satuan</th>
                    <th class="text-end">Harga Jual</th>
                    <th class="text-end">HPP Total</th>
                    <th class="text-center">Margin %</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center">Metode HPP</th>
                    <th class="text-end no-sort" style="width: 100px;">Aksi</th>
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
                    @endphp
                    <tr data-stock="{{ $stockStatus }}" data-hpp="{{ $item->hpp_method }}">
                        <td>
                            <input class="form-check-input row-checkbox" type="checkbox" value="{{ $item->id }}">
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($item->thumbnail && !in_array($item->thumbnail, ['thumbnail/default.png', 'products/default.png']))
                                    <img src="{{ asset('storage/' . $item->thumbnail) }}" alt="{{ $item->prod_name }}" class="product-thumb">
                                @else
                                    <div class="product-thumb-placeholder text-success" style="background: var(--green-50);">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold text-dark">{{ $item->prod_name }}</div>
                                    <div class="text-muted small">SKU: <span class="font-monospace text-secondary">{{ $item->sku ?? 'PRD-' . sprintf('%03d', $item->id) }}</span></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-soft-secondary font-monospace">{{ strtoupper($item->unit->short_name ?? $item->unit->unit_name ?? 'PORSI') }}</span>
                        </td>
                        <td class="text-end fw-bold text-dark">
                            Rp {{ number_format($item->selling_price, 0, ',', '.') }}
                        </td>
                        <td class="text-end font-monospace text-secondary">
                            Rp {{ number_format($hpp, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @if ($marginPercent >= 40)
                                <span class="margin-pill margin-pill-high">
                                    <i class="bi bi-arrow-up-short"></i> {{ $marginPercent }}%
                                </span>
                            @elseif ($marginPercent >= 20)
                                <span class="margin-pill margin-pill-medium">
                                    <i class="bi bi-dash"></i> {{ $marginPercent }}%
                                </span>
                            @else
                                <span class="margin-pill margin-pill-low">
                                    <i class="bi bi-arrow-down-short"></i> {{ $marginPercent }}%
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ((int)$item->current_stock <= (int)$item->min_stock)
                                <span class="badge badge-soft-danger px-2 py-1 rounded-pill">
                                    <i class="bi bi-exclamation-circle-fill me-1"></i>{{ $item->current_stock }} / Min {{ $item->min_stock }}
                                </span>
                            @else
                                <span class="badge badge-soft-success px-2 py-1 rounded-pill">
                                    <i class="bi bi-check-circle-fill me-1"></i>{{ $item->current_stock }} {{ $item->unit->short_name ?? $item->unit->unit_name ?? '' }}
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($item->hpp_method === 'calculated')
                                <span class="badge badge-soft-info">Hitung Komposisi</span>
                            @else
                                <span class="badge badge-soft-secondary">Manual Fixed</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <!-- Desktop Action Buttons -->
                            <div class="d-none d-md-inline-flex gap-1">
                                <a href="{{ route('products.show', $item->id) }}" class="action-btn action-btn-primary" title="Detail Produk">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('products.edit', $item->id) }}" class="action-btn" title="Edit Produk">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="action-btn action-btn-danger" title="Hapus Produk" onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->prod_name) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            <!-- Mobile Action Dropdown -->
                            <div class="dropdown d-inline-block d-md-none">
                                <button class="dropdown-action-btn dropdown-toggle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Aksi
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 py-1">
                                    <li>
                                        <a class="dropdown-item py-2 small" href="{{ route('products.show', $item->id) }}">
                                            <i class="bi bi-eye text-primary me-2"></i> Detail Produk
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 small" href="{{ route('products.edit', $item->id) }}">
                                            <i class="bi bi-pencil text-secondary me-2"></i> Edit Produk
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <button type="button" class="dropdown-item py-2 small text-danger" onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->prod_name) }}')">
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
</div>

<!-- MODAL CONFIRM DELETE -->
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
                        <button type="button" class="btn btn-sm btn-light border px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger px-3 rounded-3">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- jQuery & DataTables JS BSD CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        // Initialize DataTables
        const dataTable = $('#productsDataTable').DataTable({
            responsive: true,
            columnDefs: [
                { targets: 'no-sort', orderable: false }
            ],
            language: {
                emptyTable: "Belum ada data produk di database. Klik tombol 'Tambah Produk' untuk membuat produk baru.",
                zeroRecords: "Tidak ada produk yang cocok dengan pencarian",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ produk",
                infoEmpty: "Menampilkan 0 produk",
                infoFiltered: "(difilter dari _MAX_ total produk)",
                paginate: {
                    previous: "Sebelumnya",
                    next: "Selanjutnya"
                }
            },
            dom: 'rt<"d-flex flex-column flex-sm-row align-items-center justify-content-between pt-3 mt-3 border-top border-light-subtle gap-2"ip>',
            pageLength: 10
        });

        // Custom Search Input binding
        $('#dtSearchInput').on('keyup input', function () {
            dataTable.search(this.value).draw();
        });

        // Custom Filter Stock Status
        $('#dtFilterStock').on('change', function () {
            const val = this.value;
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (!val) return true;
                const rowNode = dataTable.row(dataIndex).node();
                const stock = $(rowNode).attr('data-stock');
                return stock === val;
            });
            dataTable.draw();
            $.fn.dataTable.ext.search.pop();
        });

        // Custom Filter HPP Method
        $('#dtFilterHpp').on('change', function () {
            const val = this.value;
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (!val) return true;
                const rowNode = dataTable.row(dataIndex).node();
                const hpp = $(rowNode).attr('data-hpp');
                return hpp === val;
            });
            dataTable.draw();
            $.fn.dataTable.ext.search.pop();
        });

        // Select All Checkbox Handler
        $('#selectAll').on('change', function () {
            $('.row-checkbox').prop('checked', this.checked);
        });
    });

    function openDeleteModal(id, name) {
        document.getElementById('deleteProductName').textContent = name;
        document.getElementById('deleteProductForm').action = "/products/" + id;
        const modal = new bootstrap.Modal(document.getElementById('modalDeleteProduct'));
        modal.show();
    }
</script>
@endpush
