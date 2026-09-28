@extends('layouts.admin')

@section('title', 'Catat Restock Baru — Warung Pojok Oremus')

@push('styles')
<!-- Select2 CSS & Bootstrap 5 Theme -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
    $unitsList = $units ?? collect();
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
    <input type="hidden" name="status_restock" id="status_restock" value="{{ old('status_restock', 'DRAFT') }}">

    <div class="row g-4">
        <!-- Section 1: Informasi Restock & Supplier -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4 mb-3">
                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Restock & Supplier</h6>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="invoice_number" class="form-label small fw-semibold text-dark">Nomor Invoice</label>
                        <input type="text" class="form-control font-monospace bg-light rounded-2 text-secondary"
                            id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $generatedInvoice ?? '') }}" readonly>
                    </div>

                    <div class="col-md-12">
                        <label for="restock_code" class="form-label small fw-semibold text-dark">Kode Restock</label>
                        <input type="text" class="form-control font-monospace bg-light rounded-2 text-secondary"
                            id="restock_code" name="restock_code" value="{{ old('restock_code', $generatedCode ?? '') }}" readonly>
                    </div>

                    <div class="col-md-12">
                        <label for="supplier_name" class="form-label small fw-semibold text-dark">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-2 @error('supplier_name') is-invalid @enderror"
                            id="supplier_name" name="supplier_name" value="{{ old('supplier_name') }}" required>
                        @error('supplier_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="restock_date" class="form-label small fw-semibold text-dark">Tanggal Penerimaan <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control rounded-2 @error('restock_date') is-invalid @enderror"
                            id="restock_date" name="restock_date" value="{{ old('restock_date', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('restock_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="notes" class="form-label small fw-semibold text-dark">Catatan Restock (Opsional)</label>
                        <textarea class="form-control rounded-2 @error('notes') is-invalid @enderror"
                            id="notes" name="notes" rows="5">{{ old('notes') }}</textarea>
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

                {{-- DESKTOP TABLE VIEW --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-hover align-middle mb-0 w-100" id="restockItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 45px;">No</th>
                                <th class="text-center" style="width: 160px;">Satuan Beli</th>
                                <th class="text-center" style="min-width: 180px;">Nama Produk</th>
                                <th style="width: 120px;">Informasi</th>
                                <th class="text-center" style="width: 100px;">Jumlah Masuk</th>
                                <th class="text-center" style="width: 140px;">Harga Beli</th>
                                <th class="text-center" style="width: 120px;">Subtotal</th>
                                <th class="text-center no-sort" style="width: 55px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="restockItemRows">
                            @if (!empty($oldItems) && is_array($oldItems))
                                @foreach ($oldItems as $idx => $item)
                                    @php
                                        $selectedProd = $productsList->firstWhere('id', $item['product_id'] ?? null);
                                        $selectedUnitId = $item['restock_unit_id'] ?? ($selectedProd ? $selectedProd->unit_id : null);
                                        $filteredProds = $selectedUnitId ? $productsList->where('unit_id', $selectedUnitId) : $productsList;
                                        $qty = (int)($item['quantity'] ?? 1);
                                        $purchasePrice = isset($item['purchase_price']) && $item['purchase_price'] !== '' ? (float)$item['purchase_price'] : null;
                                        $subtotal = $purchasePrice !== null ? ($qty * $purchasePrice) : 0;
                                        $unitName = $selectedProd && $selectedProd->unit
                                            ? ($selectedProd->unit->short_name ?: $selectedProd->unit->unit_name)
                                            : 'Pcs';
                                        $costDisplay = $selectedProd ? ($selectedProd->unit_price ?: $selectedProd->current_hpp ?: 0) : 0;
                                    @endphp
                                    <tr class="restock-item-row align-middle">
                                        <td class="row-number text-center text-muted fw-medium small">{{ $loop->iteration }}</td>
                                        <td>
                                            <select name="items[{{ $idx }}][restock_unit_id]" class="form-select form-select-sm select2-restock-unit rounded-2" onchange="onUnitSelectChange(this)" required>
                                                <option value="" disabled {{ empty($selectedUnitId) ? 'selected' : '' }}>Pilih Satuan...</option>
                                                @foreach ($unitsList as $u)
                                                    <option value="{{ $u->id }}" {{ $selectedUnitId == $u->id ? 'selected' : '' }}>
                                                        {{ $u->unit_name }} ({{ $u->short_name ?: $u->unit_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm select2-restock-product rounded-2" onchange="onProductSelectChange(this)" required>
                                                <option value="" disabled {{ empty($item['product_id']) ? 'selected' : '' }}>Pilih Produk...</option>
                                                @foreach ($filteredProds as $p)
                                                    @php
                                                        $uName = $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs';
                                                        $defaultCost = (float)($p->unit_price ?: $p->current_hpp ?: 0);
                                                    @endphp
                                                    <option value="{{ $p->id }}"
                                                        data-unit-id="{{ $p->unit_id }}"
                                                        data-unit="{{ $uName }}"
                                                        data-stock="{{ $p->current_stock }}"
                                                        data-cost="{{ $defaultCost }}"
                                                        {{ ($item['product_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                                        {{ $p->prod_name }} ({{ $p->prod_code ?: 'PRD-' . $p->id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="item-info-cell">
                                                <div class="item-info-stock text-dark fw-medium small">Stok: <span class="item-stock-val">{{ $selectedProd ? $selectedProd->current_stock . ' ' . $unitName : '-' }}</span></div>
                                                <div class="item-info-hpp text-muted small">HPP: <span class="item-hpp-val">{{ $selectedProd ? 'Rp ' . number_format($costDisplay, 0, ',', '.') : '-' }}</span></div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="{{ $qty }}" min="1" oninput="onItemQtyOrPriceChange(this)" required>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                                <input type="number" name="items[{{ $idx }}][purchase_price]"
                                                    class="form-control form-control-sm font-monospace text-end rounded-end-2 item-price"
                                                    value="{{ $purchasePrice !== null ? (int)$purchasePrice : '' }}" min="0" placeholder=""
                                                    oninput="onItemQtyOrPriceChange(this)" required>
                                            </div>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark item-subtotal-cell">
                                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="removeRestockItemRow(this)" title="Hapus Baris">
                                                <i class="bi bi-trash3-fill fs-6"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="emptyItemRow">
                                    <td colspan="8" class="text-center py-4 text-muted small">
                                        Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE CARD VIEW --}}
                <div id="restockMobileItemCards" class="d-block d-md-none py-3">
                    @if (!empty($oldItems) && is_array($oldItems))
                        @foreach ($oldItems as $idx => $item)
                            @php
                                $selectedProd = $productsList->firstWhere('id', $item['product_id'] ?? null);
                                $selectedUnitId = $item['restock_unit_id'] ?? ($selectedProd ? $selectedProd->unit_id : null);
                                $filteredProds = $selectedUnitId ? $productsList->where('unit_id', $selectedUnitId) : $productsList;
                                $qty = (int)($item['quantity'] ?? 1);
                                $purchasePrice = isset($item['purchase_price']) && $item['purchase_price'] !== '' ? (float)$item['purchase_price'] : null;
                                $subtotal = $purchasePrice !== null ? ($qty * $purchasePrice) : 0;
                                $unitName = $selectedProd && $selectedProd->unit ? ($selectedProd->unit->short_name ?: $selectedProd->unit->unit_name) : 'Pcs';
                                $costDisplay = $selectedProd ? ($selectedProd->unit_price ?: $selectedProd->current_hpp ?: 0) : 0;
                            @endphp
                            <div class="restock-mobile-item-card mb-3" data-mobile-index="{{ $idx }}">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-secondary rounded-pill">Item {{ $loop->iteration }}</span>
                                    <button type="button" class="btn btn-link text-danger p-0 border-0 small" onclick="removeMobileItemCard(this)" title="Hapus">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold text-dark mb-1">Satuan Beli</label>
                                    <select name="items[{{ $idx }}][restock_unit_id]" class="form-select form-select-sm select2-restock-unit rounded-2" onchange="onUnitSelectChange(this)" required>
                                        <option value="" disabled {{ empty($selectedUnitId) ? 'selected' : '' }}>Pilih Satuan...</option>
                                        @foreach ($unitsList as $u)
                                            <option value="{{ $u->id }}" {{ $selectedUnitId == $u->id ? 'selected' : '' }}>
                                                {{ $u->unit_name }} ({{ $u->short_name ?: $u->unit_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold text-dark mb-1">Nama Produk</label>
                                    <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm select2-restock-product rounded-2" onchange="onMobileProductSelectChange(this)" required>
                                        <option value="" disabled {{ empty($item['product_id']) ? 'selected' : '' }}>Pilih Produk...</option>
                                        @foreach ($filteredProds as $p)
                                            @php $defaultCost = (float)($p->unit_price ?: $p->current_hpp ?: 0); @endphp
                                            <option value="{{ $p->id }}"
                                                data-unit-id="{{ $p->unit_id }}"
                                                data-unit="{{ $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs' }}"
                                                data-stock="{{ $p->current_stock }}"
                                                data-cost="{{ $defaultCost }}"
                                                {{ ($item['product_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                                {{ $p->prod_name }} ({{ $p->prod_code ?: 'PRD-' . $p->id }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="rounded-2 bg-light border px-3 py-2 mb-2 small">
                                    <span class="text-muted">Stok: </span><span class="fw-semibold mobile-stock-val">{{ $selectedProd ? $selectedProd->current_stock . ' ' . $unitName : '-' }}</span>
                                    &nbsp;|&nbsp;
                                    <span class="text-muted">HPP: </span><span class="fw-semibold mobile-hpp-val">{{ $selectedProd ? 'Rp ' . number_format($costDisplay, 0, ',', '.') : '-' }}</span>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-5">
                                        <label class="form-label small fw-semibold text-dark mb-1">Jumlah Masuk</label>
                                        <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm font-monospace text-center item-qty" value="{{ $qty }}" min="1" oninput="onMobileItemChange(this)" required>
                                    </div>
                                    <div class="col-7">
                                        <label class="form-label small fw-semibold text-dark mb-1">Harga Beli/Unit</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-end-0">Rp</span>
                                            <input type="number" name="items[{{ $idx }}][purchase_price]" class="form-control form-control-sm font-monospace text-end item-price" value="{{ $purchasePrice !== null ? (int)$purchasePrice : '' }}" min="0" placeholder="" oninput="onMobileItemChange(this)" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded-2 bg-success bg-opacity-10 border border-success border-opacity-25">
                                    <span class="small fw-semibold text-success">Subtotal</span>
                                    <span class="fw-bold font-monospace text-success mobile-subtotal-val">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div id="mobileEmptyItemRow" class="text-center py-4 text-muted small">
                            Belum ada produk. Klik "+ Tambah" di atas untuk menambahkan item.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Section 3 & 4: Ringkasan Restock + Action Buttons (digabung dalam satu card) -->
        <div class="col-lg-12 mt-3">
            <div class="card-box bg-white border rounded-3 p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Ringkasan Restock</h6>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="subtotal" class="form-label small fw-semibold text-dark">Subtotal Biaya</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" class="form-control font-monospace fw-semibold text-end bg-light" id="subtotal" name="subtotal" value="{{ old('subtotal') }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label for="discount" class="form-label small fw-semibold text-dark">Diskon</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" class="form-control font-monospace fw-semibold text-end" id="discount" name="discount" value="{{ old('discount') }}" min="0" oninput="calculateRestockTotals()">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label for="grand_total" class="form-label small fw-bold text-dark">Grand Total</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0">Rp</span>
                            <input type="number" class="form-control font-monospace fw-bold fs-5 text-end bg-light text-success" id="grand_total" name="grand_total" value="{{ old('grand_total') }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                {{-- DESKTOP: Batal kiri, Draft + Simpan kanan (layout asli) --}}
                <div class="d-none d-md-flex align-items-center justify-content-between gap-2 mt-4 pt-3">
                    <a href="{{ route('restock.index') }}" class="btn btn-light border rounded-2 px-4">
                        Batal
                    </a>
                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-outline-secondary rounded-2 px-4 d-inline-flex align-items-center gap-2" onclick="submitRestockAs('DRAFT')">
                            Simpan sebagai Draft
                        </button>
                        <button type="button" class="btn btn-success rounded-2 px-4 d-inline-flex align-items-center gap-2" onclick="openConfirmRestockModal()">
                            Simpan
                        </button>
                    </div>
                </div>

                {{-- MOBILE: Draft & Simpan sejajar (between), Batal full-width di bawah --}}
                <div class="d-flex d-md-none flex-column gap-2 mt-4 pt-3">
                    <div class="d-flex justify-content-between gap-2">
                        <button type="button" class="btn btn-secondary rounded-2 flex-fill d-inline-flex align-items-center justify-content-center gap-2" onclick="submitRestockAs('DRAFT')">
                            Simpan Draft
                        </button>
                        <button type="button" class="btn btn-success rounded-2 flex-fill d-inline-flex align-items-center justify-content-center gap-2" onclick="openConfirmRestockModal()">
                            Simpan
                        </button>
                    </div>
                    <a href="{{ route('restock.index') }}" class="btn btn-light border rounded-2 w-100 text-center">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal Konfirmasi Simpan & Selesaikan Restock -->
<div class="modal fade" id="modalConfirmRestock" tabindex="-1" aria-labelledby="modalConfirmRestockLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalConfirmRestockLabel">
                    <i class="bi bi-exclamation-circle-fill text-warning fs-5"></i> Konfirmasi Selesaikan Restock
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-secondary mb-3">
                    Apakah Anda yakin ingin menyimpan dan <strong>menyelesaikan</strong> transaksi restock ini?
                </p>
                <div class="alert alert-warning border-warning border-opacity-25 rounded-2 p-3 mb-0 small">
                    <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> Perhatian:</div>
                    <ul class="mb-0 ps-3">
                        <li>Stok master produk akan <strong>otomatis bertambah</strong> sesuai kuantitas masuk.</li>
                        <li>Status transaksi akan tersimpan sebagai <span class="badge bg-success">CONFIRMED</span> (Selesai).</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success rounded-2 px-4" onclick="executeConfirmRestockSubmit()">
                    <i class="bi bi-check2-all me-1"></i> Ya, Simpan & Selesaikan
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    window.restockProductsList = @json($productsList);
    window.restockUnitsList = @json($unitsList);
</script>
<script src="{{ asset('js/restock.js') }}"></script>
@endpush