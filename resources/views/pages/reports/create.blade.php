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
    $unitsList    = $units ?? collect();
    $oldItems     = old('items', []);

    /*
     * Tanggal asal dari query param ?date=YYYY-MM-DDTHH:mm (dikirim modal date picker)
     * Prioritas: old() dulu (jika validation error redirect balik), lalu query param, lalu now()
     */
    $dateFromQuery = request('date');
    $defaultDate   = old('report_date',
                        $dateFromQuery ?: now()->format('Y-m-d\TH:i')
                    );
@endphp

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Buat Laporan Penjualan</h4>
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

    {{-- Simpan tanggal asli dari modal (tanpa waktu) sebagai hidden field
         agar redirect balik saat validation error tetap membawa tanggal yang dipilih --}}
    <input type="hidden" name="_date_from_modal" value="{{ $dateFromQuery }}">

    <div class="row g-4">
        <!-- Section 1: Informasi Laporan -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4 mb-3">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="report_date" class="form-label small fw-semibold text-dark">
                            Tanggal Penjualan <span class="text-danger">*</span>
                        </label>

                        {{-- ── Readonly: nilai berasal dari modal date picker ──
                             Input datetime-local dibuat readonly agar user tidak bisa mengubah
                             tanggal yang sudah divalidasi modal. Disertai hidden input
                             agar nilai tetap terkirim ke server. --}}
                        <div class="position-relative">
                            <input type="datetime-local"
                                   class="form-control rounded-2 bg-light @error('report_date') is-invalid @enderror"
                                   id="report_date_display"
                                   value="{{ $defaultDate }}"
                                   readonly
                                   tabindex="-1"
                                   style="pointer-events:none; cursor:default;">
                            <input type="hidden"
                                   name="report_date"
                                   id="report_date"
                                   value="{{ $defaultDate }}">
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted"
                                  style="pointer-events:none;">
                                <i class="bi bi-lock-fill" style="font-size:.8rem;" title="Tanggal dikunci dari pilihan sebelumnya"></i>
                            </span>
                        </div>
                        <div class="form-text text-muted" style="font-size:.75rem;">
                            <i class="bi bi-info-circle me-1"></i>
                            Tanggal dikunci sesuai pilihan di langkah sebelumnya.
                            Untuk mengubah tanggal, <a href="{{ route('reports.index') }}" class="text-decoration-none">kembali</a> dan pilih ulang.
                        </div>
                        @error('report_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-12">
                        <label for="notes" class="form-label small fw-semibold text-dark">Catatan Laporan (Opsional)</label>
                        <textarea class="form-control rounded-2 @error('notes') is-invalid @enderror"
                            id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
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
                        <span class="text-muted small">Pilih produk terlebih dahulu, lalu pilih satuan jual dan masukkan kuantitas terjual. Stok akhir dan margin terhitung otomatis.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-2 px-3 d-inline-flex align-items-center gap-2" onclick="addReportItemRow()">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>

                {{-- ══════════════════════════════════════════════════════════════
                     DESKTOP TABLE — 5-col + expand row (≥768px)
                     Total HPP dipindah ke expand row
                     ══════════════════════════════════════════════════════════════ --}}
                <div class="reports-create-table-desktop-wrap">
                    <table class="table table-bordered table-hover align-middle mb-0 w-100" id="reportItemsTableCreate">
                        <thead class="table-light">
                            <tr class="align-middle">
                                <th class="text-center col-no" style="width:56px;">No</th>
                                <th class="col-product-unit" style="min-width:260px;">Produk / Satuan</th>
                                <th class="text-center col-qty" style="width:95px;">Jumlah</th>
                                <th class="text-center col-total-sales" style="width:130px;">Total Penjualan</th>
                                <th class="text-end col-total-hpp" style="width:150px;" id="thTotalHpp">Total HPP</th>
                                <th class="text-center col-margin" style="width:125px;">Margin</th>
                                <th class="text-center no-sort col-action" style="width:45px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="reportItemRows">
                            @if (!empty($oldItems) && is_array($oldItems))
                                @foreach ($oldItems as $idx => $item)
                                    @php
                                        $selectedUnitId = $item['selling_unit_id'] ?? null;
                                        $selectedProdId = $item['product_id'] ?? null;
                                        $qtyVal         = isset($item['quantity'])    && $item['quantity']    !== '' ? $item['quantity']    : '';
                                        $stockFinalVal  = isset($item['stock_final']) && $item['stock_final'] !== '' ? $item['stock_final'] : '';
                                        $sellingPriceVal = $item['selling_price'] ?? 0;
                                        $hppVal          = $item['hpp'] ?? 0;
                                    @endphp
                                    {{-- Main row --}}
                                    <tr class="report-item-row align-middle">
                                        <td class="row-number text-center text-muted fw-medium small col-no report-expand-toggle" onclick="_toggleExpandRow(this)" style="cursor:pointer;">
                                            <span class="row-expand-arrow"></span>
                                            <span class="row-no-num">{{ $loop->iteration }}</span>
                                        </td>
                                        <td class="col-product-unit">
                                            <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm product-select rounded-2 mb-1" onchange="onProductSelectChange(this)" required>
                                                <option value="" disabled {{ empty($selectedProdId) ? 'selected' : '' }}>Pilih Produk...</option>
                                                @foreach ($productsList as $p)
                                                    <option value="{{ $p->id }}" {{ $selectedProdId == $p->id ? 'selected' : '' }}>
                                                        {{ $p->prod_name }} ({{ $p->prod_code ?: 'PRD-' . $p->id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <select name="items[{{ $idx }}][selling_unit_id]" class="form-select form-select-sm selling-unit-select rounded-2" onchange="onSellingUnitChange(this)" required>
                                                <option value="" disabled {{ empty($selectedUnitId) ? 'selected' : '' }}>Pilih Satuan...</option>
                                                @foreach ($unitsList as $u)
                                                    <option value="{{ $u->id }}" {{ $selectedUnitId == $u->id ? 'selected' : '' }}>
                                                        {{ $u->unit_name }} ({{ $u->short_name ?: $u->unit_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="col-qty item-qty-cell">
                                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="{{ $qtyVal }}" min="1" oninput="onItemQtyOrStockFinalChange(this)" required>
                                        </td>
                                        <td class="col-total-sales text-end font-monospace fw-semibold text-dark item-total-sales-cell">
                                            <span class="item-readonly-badge item-total-sales-display">Rp 0</span>
                                        </td>
                                        <td class="col-total-hpp text-end font-monospace fw-semibold text-dark item-total-hpp-cell">
                                            <span class="item-readonly-badge item-total-hpp-display">Rp 0</span>
                                        </td>
                                        <td class="col-margin text-end font-monospace fw-bold">
                                            <span class="item-readonly-badge item-margin-display text-success">Rp 0</span>
                                        </td>
                                        <td class="text-center col-action">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 shadow-none" onclick="removeReportItemRow(this)" title="Hapus Baris">
                                                <i class="bi bi-trash3-fill fs-6"></i>
                                            </button>
                                        </td>
                                        {{-- Hidden data container --}}
                                        <td class="report-item-hidden-data" style="display:none;">
                                            <div class="item-info-stock"><span class="item-stock-val">-</span></div>
                                            <div class="item-info-hpp"><span class="item-hpp-method-val">-</span></div>
                                            <input type="text" class="item-purchase-price-display" value="Rp 0" readonly tabindex="-1">
                                            <span class="item-selling-price-display">Rp 0</span>
                                            <input type="hidden" name="items[{{ $idx }}][selling_price]" class="item-selling-price" value="{{ $sellingPriceVal }}">
                                            <span class="item-hpp-display">Rp 0</span>
                                            <input type="hidden" name="items[{{ $idx }}][hpp]" class="item-hpp-price" value="{{ $hppVal }}">
                                            <span class="item-total-hpp-display">Rp 0</span>
                                            <input type="hidden" name="items[{{ $idx }}][total_sales_manual]" class="item-total-sales-manual" value="{{ $item['total_sales_manual'] ?? 0 }}">
                                            <input type="hidden" name="items[{{ $idx }}][total_hpp_manual]" class="item-total-hpp-manual" value="{{ $item['total_hpp_manual'] ?? 0 }}">
                                            <input type="number" name="items[{{ $idx }}][stock_final]" class="item-stock-final" value="{{ $stockFinalVal }}" readonly tabindex="-1" required>
                                        </td>
                                    </tr>
                                    {{-- Expand detail row --}}
                                    <tr class="report-item-expand-row d-none" data-expand-for="{{ $idx }}">
                                        <td colspan="7" class="p-0">
                                            <div class="report-expand-details">
                                                <div class="row g-0">
                                                    <div class="col-4 expand-cell">
                                                        <div class="expand-label">Stok saat ini</div>
                                                        <div class="expand-value expand-stock-val">-</div>
                                                    </div>
                                                    <div class="col-4 expand-cell">
                                                        <div class="expand-label">Metode HPP</div>
                                                        <div class="expand-value expand-hpp-method-val">-</div>
                                                    </div>
                                                    <div class="col-4 expand-cell expand-cell-last">
                                                        <div class="expand-label">Sisa Stok</div>
                                                        <div class="expand-value expand-stock-final">-</div>
                                                    </div>
                                                    <div class="col-4 expand-cell expand-cell-bottom">
                                                        <div class="expand-label">Harga Beli</div>
                                                        <div class="expand-value expand-purchase-price">Rp 0</div>
                                                    </div>
                                                    <div class="col-4 expand-cell expand-cell-bottom">
                                                        <div class="expand-label">Harga Jual</div>
                                                        <div class="expand-value expand-selling-price">Rp 0</div>
                                                    </div>
                                                    <div class="col-4 expand-cell expand-cell-last expand-cell-bottom">
                                                        <div class="expand-label">HPP / Unit</div>
                                                        <div class="expand-value expand-hpp">Rp 0</div>
                                                    </div>
                                                    <div class="col-6 expand-cell expand-cell-bottom expand-cell-noborder expand-total-hpp-wrap">
                                                        <div class="expand-label">Total HPP</div>
                                                        <div class="expand-value expand-total-hpp">Rp 0</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="emptyItemRow">
                                    <td colspan="7" class="text-center py-4 text-muted small">
                                        Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item penjualan.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- ══════════════════════════════════════════════════════════════
                     MOBILE TABLE — 13-col horizontal scroll (≤767px)
                     ══════════════════════════════════════════════════════════════ --}}
                <div class="reports-create-table-mobile-wrap">
                    <table class="table table-bordered align-middle mb-0" id="reportItemsTableMobile">
                        <thead class="table-light">
                            <tr class="align-middle">
                                <th class="col-no text-center">No</th>
                                <th class="col-product">Produk</th>
                                <th class="col-unit">Satuan</th>
                                <th class="col-info">Informasi</th>
                                <th class="col-qty text-center">Jumlah</th>
                                <th class="col-stock-final text-center">Sisa Stok</th>
                                <th class="col-price text-end">Harga Beli</th>
                                <th class="col-selling-price text-end">Harga Jual</th>
                                <th class="col-total-sales text-end">Total Penjualan</th>
                                <th class="col-total-hpp text-end">TOTAL HPP</th>
                                <th class="col-hpp text-end">HPP</th>
                                <th class="col-margin text-end">Margin</th>
                                <th class="col-action text-center no-sort">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="reportItemRowsMobile">
                            @if (!empty($oldItems) && is_array($oldItems))
                                @foreach ($oldItems as $idx => $item)
                                    @php
                                        $selectedUnitId = $item['selling_unit_id'] ?? null;
                                        $selectedProdId = $item['product_id'] ?? null;
                                        $qtyVal         = isset($item['quantity'])    && $item['quantity']    !== '' ? $item['quantity']    : '';
                                        $stockFinalVal  = isset($item['stock_final']) && $item['stock_final'] !== '' ? $item['stock_final'] : '';
                                    @endphp
                                    {{-- Mobile mirror row — NO name attributes --}}
                                    <tr class="report-item-row-mobile align-middle" data-mobile-idx="{{ $idx }}">
                                        <td class="col-no text-center text-muted fw-medium small">
                                            <span class="row-no-num">{{ $loop->iteration }}</span>
                                        </td>
                                        <td class="col-product">
                                            <select class="form-select form-select-sm product-select-mobile rounded-2" onchange="onMobileProductChange(this)">
                                                <option value="" disabled {{ empty($selectedProdId) ? 'selected' : '' }}>Pilih Produk...</option>
                                                @foreach ($productsList as $p)
                                                    <option value="{{ $p->id }}" {{ $selectedProdId == $p->id ? 'selected' : '' }}>
                                                        {{ $p->prod_name }} ({{ $p->prod_code ?: 'PRD-' . $p->id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="col-unit">
                                            <select class="form-select form-select-sm selling-unit-select-mobile rounded-2" onchange="onMobileUnitChange(this)">
                                                <option value="" disabled {{ empty($selectedUnitId) ? 'selected' : '' }}>Pilih Satuan...</option>
                                                @foreach ($unitsList as $u)
                                                    <option value="{{ $u->id }}" {{ $selectedUnitId == $u->id ? 'selected' : '' }}>
                                                        {{ $u->unit_name }} ({{ $u->short_name ?: $u->unit_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="col-info">
                                            <div class="col-info-text">Stok: <span class="item-stock-val">-</span></div>
                                            <div class="col-info-text">HPP: <span class="item-hpp-method-val">-</span></div>
                                        </td>
                                        <td class="col-qty">
                                            <input type="number" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty-mobile" value="{{ $qtyVal }}" min="1" placeholder="" oninput="onMobileQtyChange(this)">
                                        </td>
                                        <td class="col-stock-final">
                                            <input type="number" class="form-control form-control-sm font-monospace text-center rounded-2 item-stock-final bg-light text-secondary" value="{{ $stockFinalVal }}" readonly tabindex="-1">
                                        </td>
                                        <td class="col-price text-end"><span class="item-readonly-badge item-purchase-price-display">Rp 0</span></td>
                                        <td class="col-selling-price text-end"><span class="item-readonly-badge item-selling-price-display">Rp 0</span></td>
                                        <td class="col-total-sales text-end"><span class="item-readonly-badge item-total-sales-display">Rp 0</span></td>
                                        <td class="col-hpp text-end"><span class="item-readonly-badge item-hpp-display">Rp 0</span></td>
                                        <td class="col-total-hpp text-end"><span class="item-readonly-badge item-total-hpp-display">Rp 0</span></td>
                                        <td class="col-margin text-end"><span class="item-readonly-badge item-margin-display text-success">Rp 0</span></td>
                                        <td class="col-action text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 shadow-none" onclick="removeReportItemMobileRow(this)" title="Hapus Baris">
                                                <i class="bi bi-trash3-fill fs-6"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="emptyItemRowMobile">
                                    <td colspan="13" class="text-center py-4 text-muted small">
                                        Belum ada produk. Klik "+ Tambah" di atas.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Mobile card container --}}
                <div class="reports-cards-mobile d-none" id="reportCardsMobile"></div>

                <!-- Live Summary Preview & Action Bar -->
                <div class="p-3 bg-light d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mt-3">
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
                            <div class="text-muted small" style="font-size: 0.72rem;">Total Margin Kotor</div>
                            <div class="fw-bold font-monospace text-success fs-6" id="displayTotalMargin">Rp 0</div>
                        </div>
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Total Margin Bersih</div>
                            <div class="fw-bold font-monospace text-primary fs-6" id="displayTotalNetMargin">Rp 0</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light border rounded-2 px-3">Batal</a>
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
    window.reportProductsList = @json($productsList);
    window.reportUnitsList    = @json($unitsList);
</script>
<script src="{{ asset('js/reports.js') }}"></script>
@endpush