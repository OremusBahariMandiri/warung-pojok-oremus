@extends('layouts.admin')

@section('title', 'Edit Laporan Penjualan â€" Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">management laporan</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">edit laporan</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $productsList    = $products ?? collect();
    $unitsList       = $units ?? collect();
    $oldItems        = old('items', []);
    $existingDetails = $report->details ?? collect();
@endphp

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Laporan Penjualan</h4>
        <span class="text-muted small">
            Tanggal: <strong>{{ $report->report_date ? $report->report_date->format('d M Y') : '-' }}</strong>
        </span>
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

<form action="{{ route('reports.update', $report->id) }}" method="POST" id="formEditReport">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <!-- Section 1: Informasi Laporan -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4 mb-3">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold text-dark">Tanggal Penjualan</label>
                        <input type="text" class="form-control rounded-2 bg-light text-muted"
                            value="{{ $report->report_date ? $report->report_date->format('d M Y, H:i') : '-' }}" readonly>
                        <small class="text-muted">Tanggal laporan tidak dapat diubah.</small>
                    </div>
                    <div class="col-md-12">
                        <label for="notes" class="form-label small fw-semibold text-dark">Catatan Laporan (Opsional)</label>
                        <textarea class="form-control rounded-2 @error('notes') is-invalid @enderror"
                            id="notes" name="notes" rows="3">{{ old('notes', $report->notes) }}</textarea>
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
                        <span class="text-muted small">Pilih produk, satuan jual, dan masukkan kuantitas terjual. Stok akhir dan margin terhitung otomatis.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-2 px-3 d-inline-flex align-items-center gap-2" onclick="addReportItemRow()">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>

                {{-- DESKTOP TABLE VIEW --}}
                <div class="reports-table-responsive d-none d-md-block">
                    <table class="table table-bordered table-hover align-middle mb-0 w-100" id="reportItemsTable">
                        <thead class="table-light">
                            <tr class="align-middle">
                                <th class="text-center col-no" style="width: 40px;">No</th>
                                <th class="text-center col-product" style="min-width: 220px;">Produk</th>
                                <th class="text-center col-unit" style="width: 130px;">Satuan</th>
                                <th class="text-center col-info" style="width: 130px;">Informasi</th>
                                <th class="text-center col-qty" style="width: 95px;">Jumlah</th>
                                <th class="text-center col-stock-final" style="width: 95px;">Sisa Stok</th>
                                <th class="text-center col-price" style="width: 110px;">Harga Beli</th>
                                <th class="text-center col-selling-price" style="width: 110px;">Harga Jual</th>
                                <th class="text-center col-total-sales" style="width: 125px;">Total Penjualan</th>
                                <th class="text-center col-hpp" style="width: 110px;">HPP</th>
                                <th class="text-center col-total-hpp" style="width: 120px;">Total HPP</th>
                                <th class="text-center col-margin" style="width: 120px;">Margin</th>
                                <th class="text-center no-sort col-action" style="width: 45px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="reportItemRows">
                            @if (!empty($oldItems) && is_array($oldItems))
                                @foreach ($oldItems as $idx => $item)
                                    @php
                                        $selectedUnitId = $item['selling_unit_id'] ?? null;
                                        $selectedProdId = $item['product_id'] ?? null;
                                        $qtyVal         = isset($item['quantity']) && $item['quantity'] !== '' ? $item['quantity'] : '';
                                        $stockFinalVal  = isset($item['stock_final']) && $item['stock_final'] !== '' ? $item['stock_final'] : '';
                                    @endphp
                                    {{-- FIX: gunakan $item['quantity'] bukan $detail->quantity --}}
                                    <tr class="report-item-row align-middle" data-original-qty="{{ $item['quantity'] ?? 0 }}">
                                        <td class="row-number text-center text-muted fw-medium small col-no">{{ $loop->iteration }}</td>
                                        <td class="col-product">
                                            <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm product-select rounded-2" onchange="onProductSelectChange(this)" required>
                                                <option value="" disabled {{ empty($selectedProdId) ? 'selected' : '' }}>Pilih Produk...</option>
                                                @foreach ($productsList as $p)
                                                    <option value="{{ $p->id }}" {{ $selectedProdId == $p->id ? 'selected' : '' }}>
                                                        {{ $p->prod_name }} ({{ $p->prod_code ?: 'PRD-' . $p->id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="col-unit">
                                            <select name="items[{{ $idx }}][selling_unit_id]" class="form-select form-select-sm selling-unit-select rounded-2" onchange="onSellingUnitChange(this)" required>
                                                <option value="" disabled {{ empty($selectedUnitId) ? 'selected' : '' }}>Pilih Satuan...</option>
                                                @foreach ($unitsList as $u)
                                                    <option value="{{ $u->id }}" {{ $selectedUnitId == $u->id ? 'selected' : '' }}>
                                                        {{ $u->unit_name }} ({{ $u->short_name ?: $u->unit_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="col-info text-start small">
                                            <div class="item-info-stock text-dark fw-medium" style="font-size: 0.78rem;">Stok saat ini: <span class="item-stock-val">-</span></div>
                                            <div class="item-info-hpp text-muted" style="font-size: 0.72rem;">Metode HPP: <span class="item-hpp-method-val">-</span></div>
                                        </td>
                                        <td class="col-qty">
                                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="{{ $qtyVal }}" min="1" oninput="onItemQtyOrStockFinalChange(this)" required>
                                        </td>
                                        <td class="col-stock-final">
                                            <input type="number" name="items[{{ $idx }}][stock_final]" class="form-control form-control-sm font-monospace text-center rounded-2 item-stock-final bg-light text-secondary" value="{{ $stockFinalVal }}" readonly tabindex="-1" required>
                                        </td>
                                        <td class="col-price text-end font-monospace small">
                                            <input type="text" class="form-control form-control-sm font-monospace text-end rounded-2 bg-light text-muted item-purchase-price-display" value="Rp 0" disabled tabindex="-1">
                                        </td>
                                        <td class="col-selling-price text-end font-monospace small">
                                            <span class="item-readonly-badge item-selling-price-display">Rp 0</span>
                                            <input type="hidden" name="items[{{ $idx }}][selling_price]" class="item-selling-price" value="0">
                                        </td>
                                        <td class="col-total-sales text-end font-monospace fw-semibold text-dark">
                                            <span class="item-readonly-badge item-total-sales-display">Rp 0</span>
                                        </td>
                                        <td class="col-hpp text-end font-monospace small">
                                            <span class="item-readonly-badge item-hpp-display">Rp 0</span>
                                            <input type="hidden" name="items[{{ $idx }}][hpp]" class="item-hpp-price" value="0">
                                        </td>
                                        <td class="col-total-hpp text-end font-monospace small text-muted">
                                            <span class="item-readonly-badge item-total-hpp-display">Rp 0</span>
                                        </td>
                                        <td class="col-margin text-end font-monospace fw-bold text-success">
                                            <span class="item-readonly-badge item-margin-display text-success">Rp 0</span>
                                        </td>
                                        <td class="text-center col-action">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 shadow-none" onclick="removeReportItemRow(this)" title="Hapus Baris">
                                                <i class="bi bi-trash3-fill fs-6"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @elseif ($existingDetails->count() > 0)
                                @foreach ($existingDetails as $idx => $detail)
                                    {{-- FIX: tambahkan data-original-qty="{{ $detail->quantity }}" --}}
                                    <tr class="report-item-row align-middle" data-original-qty="{{ $detail->quantity }}">
                                        <td class="row-number text-center text-muted fw-medium small col-no">{{ $loop->iteration }}</td>
                                        <td class="col-product">
                                            <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm product-select rounded-2" onchange="onProductSelectChange(this)" required>
                                                <option value="" disabled>Pilih Produk...</option>
                                                @foreach ($productsList as $p)
                                                    <option value="{{ $p->id }}" {{ $detail->product_id == $p->id ? 'selected' : '' }}>
                                                        {{ $p->prod_name }} ({{ $p->prod_code ?: 'PRD-' . $p->id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="col-unit">
                                            <select name="items[{{ $idx }}][selling_unit_id]" class="form-select form-select-sm selling-unit-select rounded-2" onchange="onSellingUnitChange(this)" required>
                                                <option value="" disabled>Pilih Satuan...</option>
                                                @foreach ($unitsList as $u)
                                                    <option value="{{ $u->id }}" {{ $detail->selling_unit_id == $u->id ? 'selected' : '' }}>
                                                        {{ $u->unit_name }} ({{ $u->short_name ?: $u->unit_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="col-info text-start small">
                                            <div class="item-info-stock text-dark fw-medium" style="font-size: 0.78rem;">Stok saat ini: <span class="item-stock-val">-</span></div>
                                            <div class="item-info-hpp text-muted" style="font-size: 0.72rem;">Metode HPP: <span class="item-hpp-method-val">-</span></div>
                                        </td>
                                        <td class="col-qty">
                                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="{{ $detail->quantity }}" min="1" oninput="onItemQtyOrStockFinalChange(this)" required>
                                        </td>
                                        <td class="col-stock-final">
                                            <input type="number" name="items[{{ $idx }}][stock_final]" class="form-control form-control-sm font-monospace text-center rounded-2 item-stock-final bg-light text-secondary" value="{{ $detail->stock_final }}" readonly tabindex="-1" required>
                                        </td>
                                        <td class="col-price text-end font-monospace small">
                                            <input type="text" class="form-control form-control-sm font-monospace text-end rounded-2 bg-light text-muted item-purchase-price-display" value="Rp 0" disabled tabindex="-1">
                                        </td>
                                        <td class="col-selling-price text-end font-monospace small">
                                            <span class="item-readonly-badge item-selling-price-display">Rp {{ number_format($detail->selling_price, 0, ',', '.') }}</span>
                                            <input type="hidden" name="items[{{ $idx }}][selling_price]" class="item-selling-price" value="{{ $detail->selling_price }}">
                                        </td>
                                        <td class="col-total-sales text-end font-monospace fw-semibold text-dark">
                                            <span class="item-readonly-badge item-total-sales-display">Rp {{ number_format($detail->total_price, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="col-hpp text-end font-monospace small">
                                            <span class="item-readonly-badge item-hpp-display">Rp {{ number_format($detail->hpp, 0, ',', '.') }}</span>
                                            <input type="hidden" name="items[{{ $idx }}][hpp]" class="item-hpp-price" value="{{ $detail->hpp }}">
                                        </td>
                                        <td class="col-total-hpp text-end font-monospace small text-muted">
                                            <span class="item-readonly-badge item-total-hpp-display">Rp {{ number_format($detail->total_hpp, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="col-margin text-end font-monospace fw-bold text-success">
                                            <span class="item-readonly-badge item-margin-display text-success">Rp {{ number_format($detail->margin, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="text-center col-action">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 shadow-none" onclick="removeReportItemRow(this)" title="Hapus Baris">
                                                <i class="bi bi-trash3-fill fs-6"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="emptyItemRow">
                                    <td colspan="13" class="text-center py-4 text-muted small">
                                        Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item penjualan.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE CARD VIEW --}}
                <div class="reports-cards-mobile d-block d-md-none" id="reportCardsMobile">
                    <div class="reports-mobile-empty" id="reportMobileEmptyState">
                        Belum ada produk. Klik "+ Tambah" di atas.
                    </div>
                </div>

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
                            <div class="text-muted small" style="font-size: 0.72rem;">Total Margin</div>
                            <div class="fw-bold font-monospace text-success fs-6" id="displayTotalMargin">Rp 0</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
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