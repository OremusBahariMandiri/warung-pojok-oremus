@extends('layouts.admin')

@section('title', 'Catat Restock Baru — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/restock.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('restock.index') }}" class="text-decoration-none text-muted">management restock</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">tambah restock</li>
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
        <h4 class="fw-bold text-dark mb-1">Transaksi Restock Baru</h4>
    </div>
    <a href="{{ route('restock.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<!-- Display Validation Errors if Any -->
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Menyimpan Restock:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('restock.store') }}" method="POST" id="formCreateRestock">
    @csrf

    <div class="row g-4">
        <!-- Section 1: Informasi Transaksi Restock -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4 mb-3">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="supplier_name" class="form-label small fw-semibold text-dark">Nama Supplier<span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-2 @error('supplier_name') is-invalid @enderror"
                            id="supplier_name" name="supplier_name" value="{{ old('supplier_name') }}" required autofocus>
                        @error('supplier_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="restock_date" class="form-label small fw-semibold text-dark">Tanggal Penerimaan</label>
                        <input type="datetime-local" class="form-control rounded-2 @error('restock_date') is-invalid @enderror"
                            id="restock_date" name="restock_date" value="{{ old('restock_date', now()->format('Y-m-d\TH:i')) }}">
                        @error('restock_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="notes" class="form-label small fw-semibold text-dark">Catatan Restock (Opsional)</label>
                        <textarea class="form-control rounded-2 @error('notes') is-invalid @enderror"
                            id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Daftar Item Produk Masuk (Repeater) -->
        <div class="col-lg-12">
            <div class="card-box px-3 bg-white border rounded-3 shadow-sm mb-4 restock-card-box">
                <div class="py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Daftar Produk Masuk</h6>
                        <span class="text-muted small">Tambahkan produk dan jumlah kuantitas yang direstock.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-2 px-3 d-inline-flex align-items-center gap-2" onclick="addRestockItemRow()">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>

                {{-- ═══ DESKTOP TABLE VIEW ═══ --}}
                <div class="restock-table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 w-100" id="restockItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center col-no">No</th>
                                <th class="col-product">Pilih Produk</th>
                                {{-- Merged: Stok + Metode HPP → "Informasi" --}}
                                <th class="text-center col-info">Informasi</th>
                                <th class="text-center col-qty">Jumlah Masuk</th>
                                <th class="text-end col-price">Harga Modal / Unit</th>
                                <th class="text-end col-subtotal">Subtotal Biaya</th>
                                <th class="text-center no-sort col-action">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="restockItemRows">
                            @if (!empty($oldItems) && is_array($oldItems))
                                @foreach ($oldItems as $idx => $item)
                                    @php
                                        $selectedProd = $productsList->firstWhere('id', $item['product_id'] ?? null);
                                        $qty = (int)($item['quantity'] ?? 1);
                                        $unitPrice = (float)($item['unit_price'] ?? ($selectedProd ? $selectedProd->current_hpp : 0));
                                        $subtotal = $qty * $unitPrice;
                                        $isAuto = $selectedProd && $selectedProd->hpp_method === 'calculated';
                                        $unitName = $selectedProd && $selectedProd->unit
                                            ? ($selectedProd->unit->short_name ?: $selectedProd->unit->unit_name)
                                            : 'Pcs';
                                    @endphp
                                    <tr class="restock-item-row align-middle">
                                        <td class="row-number text-center text-muted fw-medium small col-no">{{ $loop->iteration }}</td>
                                        <td class="col-product">
                                            <div class="searchable-select-wrapper">
                                                <select name="items[{{ $idx }}][product_id]" class="d-none product-select" onchange="onProductSelectChange(this)" required>
                                                    <option value="" disabled {{ empty($item['product_id']) ? 'selected' : '' }}>Pilih Produk...</option>
                                                    @foreach ($productsList as $p)
                                                        @php
                                                            $uName = $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs';
                                                            $cost = (float)($p->current_hpp ?: $p->unit_price ?: 0);
                                                        @endphp
                                                        <option value="{{ $p->id }}"
                                                            data-hpp-method="{{ $p->hpp_method }}"
                                                            data-cost="{{ $cost }}"
                                                            data-stock="{{ $p->current_stock }}"
                                                            data-unit="{{ $uName }}"
                                                            {{ ($item['product_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                                            {{ $p->prod_name }} ({{ $p->sku ?: 'PRD-' . $p->id }}) - Stok: {{ $p->current_stock }} {{ $uName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="searchable-select-trigger" onclick="toggleSearchableSelect(this, event)" tabindex="0" role="combobox">
                                                    @if ($selectedProd)
                                                        @php
                                                            $sLabel = $selectedProd->prod_name . ' (' . ($selectedProd->sku ?: 'PRD-' . $selectedProd->id) . ') - Stok: ' . $selectedProd->current_stock . ' ' . $unitName;
                                                        @endphp
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
                                                                $uName = $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs';
                                                                $optLabel = $p->prod_name . ' (' . ($p->sku ?: 'PRD-' . $p->id) . ') - Stok: ' . $p->current_stock . ' ' . $uName;
                                                            @endphp
                                                            <div class="searchable-select-option {{ ($item['product_id'] ?? '') == $p->id ? 'selected' : '' }}"
                                                                data-value="{{ $p->id }}"
                                                                data-label="{{ $optLabel }}"
                                                                onclick="selectSearchableOption(this)">
                                                                {{ $optLabel }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- MERGED INFO CELL: Stok + Metode HPP --}}
                                        <td class="text-center col-info">
                                            <div class="item-info-cell current-stock-cell">
                                                <div class="item-info-stock">
                                                    Stok: {{ $selectedProd ? $selectedProd->current_stock . ' ' . $unitName : '-' }}
                                                </div>
                                                <div class="item-info-hpp">
                                                    HPP: {{ $selectedProd ? ($isAuto ? 'Otomatis' : 'Manual') : '-' }}
                                                </div>
                                            </div>
                                        </td>

                                        <td class="col-qty">
                                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="{{ $qty }}" min="1" oninput="onItemQtyOrPriceChange(this)" required>
                                        </td>
                                        <td class="col-price">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                                <input type="number" name="items[{{ $idx }}][unit_price]"
                                                    class="form-control form-control-sm font-monospace text-end rounded-end-2 item-unit-price {{ $isAuto ? 'bg-light' : '' }}"
                                                    value="{{ (int)$unitPrice }}" min="0"
                                                    oninput="onItemQtyOrPriceChange(this)"
                                                    {{ $isAuto ? 'readonly' : '' }} required>
                                            </div>
                                            <div class="unit-price-hint text-muted text-end small">
                                                {{ $isAuto ? 'Otomatis dari HPP' : 'Dapat disesuaikan' }}
                                            </div>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark item-subtotal-cell col-subtotal">
                                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center col-action">
                                            <button type="button" class="btn-delete-row" onclick="removeRestockItemRow(this)" title="Hapus Baris">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="emptyItemRow">
                                    <td colspan="7" class="text-center py-4 text-muted small">
                                        Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- ═══ MOBILE CARD VIEW ═══ --}}
                <div class="restock-cards-mobile" id="restockCardsMobile">
                    @if (!empty($oldItems) && is_array($oldItems))
                        @foreach ($oldItems as $idx => $item)
                            @php
                                $selectedProd = $productsList->firstWhere('id', $item['product_id'] ?? null);
                                $qty = (int)($item['quantity'] ?? 1);
                                $unitPrice = (float)($item['unit_price'] ?? ($selectedProd ? $selectedProd->current_hpp : 0));
                                $subtotal = $qty * $unitPrice;
                                $isAuto = $selectedProd && $selectedProd->hpp_method === 'calculated';
                                $unitName = $selectedProd && $selectedProd->unit
                                    ? ($selectedProd->unit->short_name ?: $selectedProd->unit->unit_name)
                                    : 'Pcs';
                            @endphp
                            {{-- Mobile cards are generated by JS (addRestockItemRow); this covers old() repopulation --}}
                        @endforeach
                    @else
                        <div class="restock-mobile-empty" id="mobileEmptyState">
                            Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas.
                        </div>
                    @endif
                </div>

                <!-- Live Summary Preview & Action Bar -->
                <div class="p-3 bg-light d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-4">
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Total Jenis Produk</div>
                            <div class="fw-bold text-dark fs-6" id="displayTotalItems">0 Produk</div>
                        </div>
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Total Jumlah Unit</div>
                            <div class="fw-bold text-dark fs-6" id="displayTotalQty">0 Unit</div>
                        </div>
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Total Biaya Restock</div>
                            <div class="fw-bold font-monospace text-success fs-6" id="displayGrandTotal">Rp 0</div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('restock.index') }}" class="btn btn-sm btn-light border rounded-2 px-3">Batal</a>
                        <button type="submit" class="btn btn-sm btn-success text-white fw-bold px-4 rounded-2 d-inline-flex align-items-center gap-2">
                            Simpan
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
    window.restockProductsList = @json($productsList);
</script>
<script src="{{ asset('js/restock.js') }}"></script>
@endpush