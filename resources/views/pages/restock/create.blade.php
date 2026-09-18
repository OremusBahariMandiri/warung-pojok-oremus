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
        <p class="text-muted small mb-0">Isi informasi supplier dan tambahkan item produk yang masuk ke inventori.</p>
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
            <div class="card-box px-3 bg-white border rounded-3 overflow-hidden shadow-sm mb-4">
                <div class="py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Daftar Produk Masuk</h6>
                        <span class="text-muted small">Tambahkan produk dan jumlah kuantitas yang direstock.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-2 px-3 d-inline-flex align-items-center gap-2" onclick="addRestockItemRow()">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap" id="restockItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th style="min-width: 260px;">Pilih Produk</th>
                                <th class="text-center" style="width: 130px;">Stok Saat Ini</th>
                                <th class="text-center" style="width: 120px;">Metode HPP</th>
                                <th class="text-center" style="width: 130px;">Jumlah Masuk</th>
                                <th class="text-end" style="width: 170px;">Harga Modal / Unit</th>
                                <th class="text-end" style="width: 160px;">Subtotal Biaya</th>
                                <th class="text-center no-sort" style="width: 60px;">Aksi</th>
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
                                    @endphp
                                    <tr class="restock-item-row align-middle">
                                        <td class="row-number text-center text-muted fw-medium small">{{ $loop->iteration }}</td>
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm rounded-2 product-select" onchange="onProductSelectChange(this)" required>
                                                <option value="" disabled>Pilih Produk...</option>
                                                @foreach ($productsList as $p)
                                                    @php
                                                        $unitName = $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs';
                                                        $cost = (float)($p->current_hpp ?: $p->unit_price ?: 0);
                                                    @endphp
                                                    <option value="{{ $p->id }}" data-hpp-method="{{ $p->hpp_method }}" data-cost="{{ $cost }}" data-stock="{{ $p->current_stock }}" data-unit="{{ $unitName }}" {{ ($item['product_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                                        {{ $p->prod_name }} ({{ $p->sku ?: 'PRD-' . $p->id }}) - Stok: {{ $p->current_stock }} {{ $unitName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center current-stock-cell text-muted small">
                                            {{ $selectedProd ? $selectedProd->current_stock . ' ' . ($selectedProd->unit->short_name ?? 'Pcs') : '-' }}
                                        </td>
                                        <td class="text-center hpp-method-cell text-muted small">
                                            {{ $selectedProd ? ($selectedProd->hpp_method === 'calculated' ? 'Otomatis' : 'Manual') : '-' }}
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="{{ $qty }}" min="1" oninput="onItemQtyOrPriceChange(this)" required>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                                <input type="number" name="items[{{ $idx }}][unit_price]" class="form-control form-control-sm font-monospace text-end rounded-end-2 item-unit-price {{ ($selectedProd && $selectedProd->hpp_method === 'calculated') ? 'bg-light' : '' }}" value="{{ (int)$unitPrice }}" min="0" oninput="onItemQtyOrPriceChange(this)" {{ ($selectedProd && $selectedProd->hpp_method === 'calculated') ? 'readonly' : '' }} required>
                                            </div>
                                            <div class="unit-price-hint text-muted text-end small" style="font-size: 0.7rem;">
                                                {{ ($selectedProd && $selectedProd->hpp_method === 'calculated') ? 'Otomatis dari HPP' : 'Dapat disesuaikan' }}
                                            </div>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark item-subtotal-cell">
                                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 shadow-none" onclick="removeRestockItemRow(this)" title="Hapus Baris">
                                                <i class="bi bi-x-circle-fill fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="emptyItemRow">
                                    <td colspan="8" class="text-center py-4 text-muted small">
                                        Belum ada produk yang ditambahkan. Klik tombol "+ Tambah Produk" di atas untuk menambahkan item.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
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
    window.restockProductsList = @json($productsList);
</script>
<script src="{{ asset('js/restock.js') }}"></script>
@endpush
