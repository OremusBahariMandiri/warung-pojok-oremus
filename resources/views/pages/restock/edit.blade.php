@extends('layouts.admin')

@section('title', 'Edit Transaksi Restock — Warung Pojok Oremus')

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
        <li class="breadcrumb-item active text-dark" aria-current="page">edit restock</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $productsList = $products ?? collect();
    $unitsList = $units ?? collect();
    $oldItems = old('items');
    $existingItems = $restock->items ?? collect();
@endphp

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Transaksi Restock: {{ $restock->restock_code }}</h4>
    </div>
    <a href="{{ route('restock.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<!-- Display Validation Errors if Any -->
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Memperbarui Restock:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('restock.update', $restock->id) }}" method="POST" id="formCreateRestock">
    @csrf
    @method('PUT')
    <input type="hidden" name="status_restock" id="status_restock" value="{{ old('status_restock', $restock->status_restock) }}">

    <div class="row g-4">
        <!-- Section 1: Informasi Umum & Supplier -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4 mb-3">
                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Restock & Supplier</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="invoice_number" class="form-label small fw-semibold text-dark">Nomor Invoice</label>
                        <input type="text" class="form-control font-monospace bg-light rounded-2 text-secondary"
                            id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $restock->invoice_number) }}" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="restock_code" class="form-label small fw-semibold text-dark">Kode Restock</label>
                        <input type="text" class="form-control font-monospace bg-light rounded-2 text-secondary"
                            id="restock_code" name="restock_code" value="{{ old('restock_code', $restock->restock_code) }}" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="supplier_name" class="form-label small fw-semibold text-dark">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-2 @error('supplier_name') is-invalid @enderror"
                            id="supplier_name" name="supplier_name" value="{{ old('supplier_name', $restock->supplier_name) }}" required autofocus>
                        @error('supplier_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="restock_date" class="form-label small fw-semibold text-dark">Tanggal Penerimaan <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control rounded-2 @error('restock_date') is-invalid @enderror"
                            id="restock_date" name="restock_date" value="{{ old('restock_date', $restock->restock_date ? $restock->restock_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required>
                        @error('restock_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="notes" class="form-label small fw-semibold text-dark">Catatan Restock (Opsional)</label>
                        <textarea class="form-control rounded-2 @error('notes') is-invalid @enderror"
                            id="notes" name="notes" rows="3">{{ old('notes', $restock->notes) }}</textarea>
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
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 w-100" id="restockItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center col-no" style="width: 40px;">No</th>
                                <th style="width: 170px;">Pilih Satuan Beli</th>
                                <th>Pilih Produk</th>
                                <th class="text-start" style="width: 150px;">INFORMASI</th>
                                <th class="text-center" style="width: 120px;">JUMLAH MASUK</th>
                                <th class="text-end" style="width: 170px;">HARGA BELI / UNIT</th>
                                <th class="text-end" style="width: 160px;">SUBTOTAL BIAYA</th>
                                <th class="text-center no-sort" style="width: 50px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="restockItemRows">
                            @if (!empty($oldItems) && is_array($oldItems))
                                @foreach ($oldItems as $idx => $item)
                                    @php
                                        $selectedProd = $productsList->firstWhere('id', $item['product_id'] ?? null);
                                        $qty = (int)($item['quantity'] ?? 1);
                                        $purchasePrice = (float)($item['purchase_price'] ?? 0);
                                        $subtotal = $qty * $purchasePrice;
                                        $unitName = $selectedProd && $selectedProd->unit
                                            ? ($selectedProd->unit->short_name ?: $selectedProd->unit->unit_name)
                                            : 'Pcs';
                                        $costDisplay = $selectedProd ? ($selectedProd->unit_price ?: $selectedProd->current_hpp ?: 0) : 0;
                                    @endphp
                                    <tr class="restock-item-row align-middle">
                                        <td class="row-number text-center text-muted fw-medium small">{{ $loop->iteration }}</td>
                                        <td>
                                            <select name="items[{{ $idx }}][restock_unit_id]" class="form-select form-select-sm select2-restock-unit rounded-2" onchange="onUnitSelectChange(this)" required>
                                                <option value="" disabled {{ empty($item['restock_unit_id']) ? 'selected' : '' }}>Pilih Satuan...</option>
                                                @foreach ($unitsList as $u)
                                                    <option value="{{ $u->id }}" {{ ($item['restock_unit_id'] ?? ($selectedProd ? $selectedProd->unit_id : '')) == $u->id ? 'selected' : '' }}>
                                                        {{ $u->unit_name }} ({{ $u->short_name ?: $u->unit_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm select2-restock-product rounded-2" onchange="onProductSelectChange(this)" required>
                                                <option value="" disabled {{ empty($item['product_id']) ? 'selected' : '' }}>Pilih Produk...</option>
                                                @foreach ($productsList as $p)
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
                                        <td class="text-start">
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
                                                    value="{{ (int)$purchasePrice }}" min="0"
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
                            @elseif ($existingItems->count() > 0)
                                @foreach ($existingItems as $idx => $item)
                                    @php
                                        $selectedProd = $productsList->firstWhere('id', $item->product_id);
                                        $qty = (int)$item->quantity;
                                        $purchasePrice = (float)$item->purchase_price;
                                        $subtotal = (float)$item->total_price;
                                        $unitName = $item->unit ? ($item->unit->short_name ?: $item->unit->unit_name) : ($selectedProd && $selectedProd->unit ? ($selectedProd->unit->short_name ?: $selectedProd->unit->unit_name) : 'Pcs');
                                        $costDisplay = $selectedProd ? ($selectedProd->unit_price ?: $selectedProd->current_hpp ?: 0) : 0;
                                    @endphp
                                    <tr class="restock-item-row align-middle">
                                        <td class="row-number text-center text-muted fw-medium small">{{ $loop->iteration }}</td>
                                        <td>
                                            <select name="items[{{ $idx }}][restock_unit_id]" class="form-select form-select-sm select2-restock-unit rounded-2" onchange="onUnitSelectChange(this)" required>
                                                <option value="" disabled>Pilih Satuan...</option>
                                                @foreach ($unitsList as $u)
                                                    <option value="{{ $u->id }}" {{ ($item->restock_unit_id ?? ($selectedProd ? $selectedProd->unit_id : '')) == $u->id ? 'selected' : '' }}>
                                                        {{ $u->unit_name }} ({{ $u->short_name ?: $u->unit_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm select2-restock-product rounded-2" onchange="onProductSelectChange(this)" required>
                                                <option value="" disabled>Pilih Produk...</option>
                                                @foreach ($productsList as $p)
                                                    @php
                                                        $uName = $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs';
                                                        $defaultCost = (float)($p->unit_price ?: $p->current_hpp ?: 0);
                                                    @endphp
                                                    <option value="{{ $p->id }}"
                                                        data-unit-id="{{ $p->unit_id }}"
                                                        data-unit="{{ $uName }}"
                                                        data-stock="{{ $p->current_stock }}"
                                                        data-cost="{{ $defaultCost }}"
                                                        {{ $item->product_id == $p->id ? 'selected' : '' }}>
                                                        {{ $p->prod_name }} ({{ $p->prod_code ?: 'PRD-' . $p->id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-start">
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
                                                    value="{{ (int)$purchasePrice }}" min="0"
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
            </div>
        </div>

        <!-- Section 3: Rincian Kalkulasi Pembayaran (Card Khusus Terpisah) -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Ringkasan Restock</h6>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="subtotal" class="form-label small fw-semibold text-dark">Subtotal Biaya</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" class="form-control font-monospace fw-semibold text-end bg-light" id="subtotal" name="subtotal" value="{{ old('subtotal', (int)$restock->subtotal) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label for="discount" class="form-label small fw-semibold text-dark">Diskon</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" class="form-control font-monospace fw-semibold text-end" id="discount" name="discount" value="{{ old('discount', (int)$restock->discount) }}" min="0" oninput="calculateRestockTotals()">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label for="grand_total" class="form-label small fw-bold text-dark">Grand Total</label>
                        <div class="input-group">
                            <span class="input-group-text bg-success text-white fw-bold border-end-0">Rp</span>
                            <input type="number" class="form-control font-monospace fw-bold fs-5 text-end bg-light text-success" id="grand_total" name="grand_total" value="{{ old('grand_total', (int)$restock->grand_total) }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Action Buttons Flow (Draft & Confirmation Modal) -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-3 d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                <a href="{{ route('restock.index') }}" class="btn btn-outline-secondary rounded-2 px-4">
                    Batal
                </a>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-outline-primary rounded-2 px-4 d-inline-flex align-items-center gap-2" onclick="submitRestockAs('DRAFT')">
                        <i class="bi bi-file-earmark-arrow-down"></i> Simpan Perubahan Draft
                    </button>
                    <button type="button" class="btn btn-success rounded-2 px-4 d-inline-flex align-items-center gap-2" onclick="openConfirmRestockModal()">
                        <i class="bi bi-check-circle-fill"></i> Simpan & Selesaikan
                    </button>
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
