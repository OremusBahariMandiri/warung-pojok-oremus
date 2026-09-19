@extends('layouts.admin')

@section('title', 'Buat Laporan Penjualan — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">management laporan</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">buat laporan</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $productsList = $products ?? collect();
    $oldItems = old('items', []);
@endphp

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Buat Laporan Penjualan</h4>
        <p class="text-muted small mb-0">Isi tanggal penjualan dan tambahkan produk yang terjual.</p>
    </div>
    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Menyimpan Laporan Penjualan:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('reports.store') }}" method="POST" id="formCreateReport">
    @csrf

    <div class="row g-4">
        <!-- Section 1: Informasi Laporan -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4 mb-3">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="report_date" class="form-label small fw-semibold text-dark">Tanggal Penjualan <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control rounded-2 @error('report_date') is-invalid @enderror"
                            id="report_date" name="report_date" value="{{ old('report_date') }}">
                        @error('report_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-12">
                        <label for="notes" class="form-label small fw-semibold text-dark">Catatan Laporan (Opsional)</label>
                        <textarea class="form-control rounded-2 @error('notes') is-invalid @enderror"
                            id="notes" name="notes" rows="5">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Detail Penjualan (Repeater Multi-Items) -->
        <div class="col-lg-12">
            <div class="card-box px-3 bg-white border rounded-3 shadow-sm mb-4 reports-card-box">
                <div class="py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Detail Penjualan</h6>
                        <span class="text-muted small">Pilih produk dan masukkan kuantitas yang terjual. Harga jual, HPP, dan margin terhitung otomatis.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-2 px-3 d-inline-flex align-items-center gap-2" onclick="addReportItemRow()">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>

                {{-- ═══ DESKTOP TABLE VIEW ═══ --}}
                <div class="reports-table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 w-100" id="reportItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center col-no">No</th>
                                <th class="col-product">Pilih Produk</th>
                                <th class="text-center col-qty">Jumlah Terjual</th>
                                <th class="text-end col-price">Harga Jual</th>
                                <th class="text-end col-total-price">Total Penjualan</th>
                                <th class="text-end col-hpp">HPP / Unit</th>
                                <th class="text-end col-total-hpp">Total HPP</th>
                                <th class="text-end col-margin">Margin</th>
                                <th class="text-center no-sort col-action">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="reportItemRows">
                            @if (!empty($oldItems) && is_array($oldItems))
                                @foreach ($oldItems as $idx => $item)
                                    @php
                                        $selectedProd = $productsList->firstWhere('id', $item['product_id'] ?? null);
                                        $qty = (int)($item['quantity'] ?? 1);
                                        $price = (float)($item['selling_price'] ?? ($selectedProd ? $selectedProd->selling_price : 0));
                                        $hpp = (float)($item['hpp'] ?? ($selectedProd ? $selectedProd->current_hpp : 0));
                                        $totalSales = $qty * $price;
                                        $totalHpp = $qty * $hpp;
                                        $margin = $totalSales - $totalHpp;
                                        $stock = $selectedProd ? $selectedProd->current_stock : 0;
                                        $unit = $selectedProd && $selectedProd->unit ? ($selectedProd->unit->short_name ?: $selectedProd->unit->unit_name) : 'Pcs';
                                    @endphp
                                    <tr class="report-item-row align-middle" data-current-stock="{{ $stock }}" data-selling-price="{{ $price }}" data-hpp="{{ $hpp }}">
                                        <td class="row-number text-center text-muted fw-medium small col-no">{{ $loop->iteration }}</td>
                                        <td class="col-product">
                                            <div class="searchable-select-wrapper">
                                                <select name="items[{{ $idx }}][product_id]" class="d-none product-select" onchange="onProductSelectChange(this)">
                                                    <option value="" disabled {{ empty($item['product_id']) ? 'selected' : '' }}>Pilih Produk...</option>
                                                    @foreach ($productsList as $p)
                                                        @php
                                                            $unitName = $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs';
                                                            $pPrice = (float)($p->selling_price ?: 0);
                                                            $pHpp = (float)($p->current_hpp ?: 0);
                                                            $pStock = (int)($p->current_stock ?: 0);
                                                            $pSku = $p->sku ?: 'PRD-' . $p->id;
                                                            $pLabel = $p->prod_name . ' (' . $pSku . ') - Stok: ' . $pStock . ' ' . $unitName;
                                                        @endphp
                                                        <option value="{{ $p->id }}" data-price="{{ $pPrice }}" data-hpp="{{ $pHpp }}" data-stock="{{ $pStock }}" data-unit="{{ $unitName }}" {{ ($item['product_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                                            {{ $pLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="searchable-select-trigger" onclick="toggleSearchableSelect(this, event)" tabindex="0" role="combobox">
                                                    @if ($selectedProd)
                                                        @php $sLabel = $selectedProd->prod_name . ' (' . ($selectedProd->sku ?: 'PRD-' . $selectedProd->id) . ') - Stok: ' . $stock . ' ' . $unit; @endphp
                                                        <span class="selected-text">{{ $sLabel }}</span>
                                                    @else
                                                        <span class="placeholder-text">Pilih Produk...</span>
                                                    @endif
                                                </div>
                                                <div class="searchable-select-dropdown">
                                                    <div class="searchable-select-search-wrap">
                                                        <input type="text" class="searchable-select-search" oninput="filterSearchableOptions(this)" placeholder="Cari produk..." autocomplete="off">
                                                    </div>
                                                    <div class="searchable-select-options">
                                                        @foreach ($productsList as $p)
                                                            @php
                                                                $unitName = $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs';
                                                                $pSku = $p->sku ?: 'PRD-' . $p->id;
                                                                $pStock = (int)($p->current_stock ?: 0);
                                                                $optLabel = $p->prod_name . ' (' . $pSku . ') - Stok: ' . $pStock . ' ' . $unitName;
                                                            @endphp
                                                            <div class="searchable-select-option {{ ($item['product_id'] ?? '') == $p->id ? 'selected' : '' }}"
                                                                data-value="{{ $p->id }}" data-label="{{ $optLabel }}"
                                                                onclick="selectSearchableOption(this)">{{ $optLabel }}</div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="stock-info-text">Stok tersedia: <span class="stock-num">{{ $selectedProd ? $stock . ' ' . $unit : '-' }}</span></div>
                                            <div class="stock-warning-text" style="{{ $qty > $stock ? 'display: block;' : 'display: none;' }}">
                                                <i class="bi bi-exclamation-circle-fill me-1"></i>Jumlah terjual ({{ $qty }}) melebihi stok tersedia ({{ $stock }})!
                                            </div>
                                        </td>
                                        <td class="col-qty">
                                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty {{ $qty > $stock ? 'is-invalid' : '' }}" value="{{ $qty }}" min="1" max="{{ $stock }}" oninput="onItemQtyChange(this)">
                                        </td>
                                        <td class="col-price text-end font-monospace small">
                                            <span class="item-readonly-badge item-price-display">Rp {{ number_format($price, 0, ',', '.') }}</span>
                                            <input type="hidden" name="items[{{ $idx }}][selling_price]" class="item-selling-price" value="{{ $price }}">
                                        </td>
                                        <td class="col-total-price text-end font-monospace fw-semibold text-dark">
                                            <span class="item-readonly-badge item-total-price-display">Rp {{ number_format($totalSales, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="col-hpp text-end font-monospace small">
                                            <span class="item-readonly-badge item-hpp-display">Rp {{ number_format($hpp, 0, ',', '.') }}</span>
                                            <input type="hidden" name="items[{{ $idx }}][hpp]" class="item-hpp-price" value="{{ $hpp }}">
                                        </td>
                                        <td class="col-total-hpp text-end font-monospace small text-muted">
                                            <span class="item-readonly-badge item-total-hpp-display">Rp {{ number_format($totalHpp, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="col-margin text-end font-monospace fw-bold">
                                            <span class="item-readonly-badge item-margin-display {{ $margin < 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($margin, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="text-center col-action">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 shadow-none" onclick="removeReportItemRow(this)" title="Hapus Baris">
                                                <i class="bi bi-x-circle-fill fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="emptyItemRow">
                                    <td colspan="9" class="text-center py-4 text-muted small">
                                        Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item penjualan.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- ═══ MOBILE CARD VIEW ═══ --}}
                <div class="reports-cards-mobile" id="reportCardsMobile">
                    @if (!empty($oldItems) && is_array($oldItems))
                        {{-- Cards di-build ulang oleh JS pada DOMContentLoaded --}}
                    @else
                        <div class="reports-mobile-empty" id="reportMobileEmptyState">
                            Belum ada produk. Klik "+ Tambah" di atas.
                        </div>
                    @endif
                </div>

                <!-- Live Summary Preview & Action Bar -->
                <div class="p-3 bg-light d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div class="d-flex flex-wrap align-items-center gap-4">
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Total Jenis Produk</div>
                            <div class="fw-bold text-dark fs-6" id="displayTotalItems">0 Produk</div>
                        </div>
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Total Jumlah Terjual</div>
                            <div class="fw-bold text-dark fs-6" id="displayTotalQty">0 Unit</div>
                        </div>
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Total Penjualan</div>
                            <div class="fw-bold font-monospace text-dark fs-6" id="displayTotalSales">Rp 0</div>
                        </div>
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Total HPP</div>
                            <div class="fw-bold font-monospace text-muted fs-6" id="displayTotalHpp">Rp 0</div>
                        </div>
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Total Margin</div>
                            <div class="fw-bold font-monospace text-success fs-6" id="displayTotalMargin">Rp 0</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light border rounded-2 px-3">Batal</a>
                        <button type="submit" class="btn btn-sm btn-success text-white fw-bold px-4 rounded-2 d-inline-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
    window.reportProductsList = @json($productsList);
</script>
<script src="{{ asset('js/reports.js') }}"></script>
@endpush