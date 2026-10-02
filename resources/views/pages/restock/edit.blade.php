@extends('layouts.admin')

@section('title', 'Edit Transaksi Restock — Warung Pojok Oremus')

@push('styles')
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
    $productsList   = $products ?? collect();
    $unitsList      = $units ?? collect();
    $oldItems       = old('items');
    $existingItems  = $restock->items ?? collect();
    $isConfirmed    = $restock->status_restock === 'CONFIRMED';

    // ── Hitung sisa waktu rollback (3 jam sejak updated_at) ──
    $canRollback       = false;
    $rollbackDeadline  = null;
    $remainingTimeStr  = '';
    $deadlineFormatted = '';

    if ($isConfirmed) {
        $rollbackDeadline  = $restock->updated_at->copy()->addHours(3);
        $canRollback       = now()->lt($rollbackDeadline);
        $deadlineFormatted = $rollbackDeadline->translatedFormat('d M Y, H:i') . ' WIB';

        if ($canRollback) {
            $diff    = now()->diff($rollbackDeadline);
            $hours   = (int) $diff->h;
            $minutes = (int) $diff->i;
            $seconds = (int) $diff->s;

            if ($hours > 0 && $minutes > 0) {
                $remainingTimeStr = "{$hours} jam {$minutes} menit lagi";
            } elseif ($hours > 0) {
                $remainingTimeStr = "{$hours} jam lagi";
            } elseif ($minutes > 0) {
                $remainingTimeStr = "{$minutes} menit lagi";
            } else {
                $remainingTimeStr = "Kurang dari 1 menit lagi";
            }
        }
    }
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

{{-- Banner terkunci jika status CONFIRMED --}}
@if ($isConfirmed)
<div class="alert d-flex align-items-start gap-3 rounded-3 mb-4 py-3 px-3 border-0 shadow-sm"
     style="background-color: #fef9ec; border-left: 4px solid #f59e0b !important;">
    <i class="bi bi-lock-fill text-warning fs-5 mt-1 flex-shrink-0"></i>
    <div>
        <div class="fw-bold text-dark mb-1">Transaksi ini sudah berstatus <span class="badge bg-success">SELESAI</span> — Form dikunci</div>
        <div class="text-secondary small">Semua field tidak dapat diubah karena transaksi sudah dikonfirmasi. Gunakan tombol <strong>Rollback Restock</strong> untuk membatalkan transaksi ini.</div>
        @if ($canRollback)
            <div class="mt-2 small">
                <i class="bi bi-clock me-1 text-warning"></i>
                Rollback masih tersedia selama: <span class="fw-bold text-warning" id="rollbackCountdown">{{ $remainingTimeStr }}</span>
                (batas {{ $deadlineFormatted }})
            </div>
        @else
            <div class="mt-2 small text-danger">
                <i class="bi bi-clock-history me-1"></i>
                Batas waktu rollback (3 jam) sudah terlewat. Transaksi ini tidak dapat dibatalkan.
            </div>
        @endif
    </div>
</div>
@endif

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
    {{-- Hidden fields ringkasan (tidak ditampilkan, tetap dikirim ke server) --}}
    <input type="hidden" id="subtotal"    name="subtotal"    value="{{ old('subtotal', (int)($restock->subtotal ?? 0)) }}">
    <input type="hidden" id="discount"    name="discount"    value="{{ old('discount', (int)($restock->discount ?? 0)) }}">
    <input type="hidden" id="grand_total" name="grand_total" value="{{ old('grand_total', (int)($restock->grand_total ?? 0)) }}">

    <div class="row g-4">

        <!-- Section 1: Informasi Restock & Supplier -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4 mb-3">
                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Restock & Supplier</h6>
                <div class="row g-3">

                    {{-- Kode Restock --}}
                    <div class="col-md-12">
                        <label for="restock_code" class="form-label small fw-semibold text-dark">Kode Restock</label>
                        <input type="text" class="form-control font-monospace bg-light rounded-2 text-secondary"
                            id="restock_code" name="restock_code"
                            value="{{ old('restock_code', $restock->restock_code) }}" readonly>
                    </div>

                    {{-- Nama Supplier --}}
                    <div class="col-md-12">
                        <label for="supplier_name" class="form-label small fw-semibold text-dark">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text"
                            class="form-control rounded-2 @error('supplier_name') is-invalid @enderror {{ $isConfirmed ? 'bg-light text-secondary' : '' }}"
                            id="supplier_name" name="supplier_name"
                            value="{{ old('supplier_name', $restock->supplier_name) }}"
                            {{ $isConfirmed ? 'readonly' : 'required' }}>
                        @error('supplier_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tanggal Penerimaan --}}
                    <div class="col-md-12">
                        <label for="restock_date" class="form-label small fw-semibold text-dark">Tanggal Penerimaan <span class="text-danger">*</span></label>
                        <input type="datetime-local"
                            class="form-control rounded-2 @error('restock_date') is-invalid @enderror {{ $isConfirmed ? 'bg-light text-secondary' : '' }}"
                            id="restock_date" name="restock_date"
                            value="{{ old('restock_date', $restock->restock_date ? $restock->restock_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                            {{ $isConfirmed ? 'readonly' : 'required' }}>
                        @error('restock_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Catatan --}}
                    <div class="col-md-12">
                        <label for="notes" class="form-label small fw-semibold text-dark">Catatan Restock (Opsional)</label>
                        <textarea
                            class="form-control rounded-2 @error('notes') is-invalid @enderror {{ $isConfirmed ? 'bg-light text-secondary' : '' }}"
                            id="notes" name="notes" rows="3"
                            {{ $isConfirmed ? 'readonly' : '' }}>{{ old('notes', $restock->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        <!-- Section 2: Daftar Produk Masuk + Action Buttons (satu card) -->
        <div class="col-lg-12">
            <div class="card-box px-3 bg-white border rounded-3 shadow-sm mb-4 restock-card-box">

                {{-- Header: judul + tombol Tambah (hanya muncul saat DRAFT) --}}
                <div class="py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Daftar Produk Masuk</h6>
                        <span class="text-muted small">
                            @if ($isConfirmed)
                                Daftar produk terkunci — transaksi sudah selesai.
                            @else
                                Tambahkan produk dan jumlah kuantitas yang direstock.
                            @endif
                        </span>
                    </div>
                    @if (!$isConfirmed)
                        <button type="button" class="btn btn-sm btn-outline-success rounded-2 px-3 d-inline-flex align-items-center gap-2"
                            onclick="addRestockItemRow()">
                            <i class="bi bi-plus-lg"></i> Tambah
                        </button>
                    @endif
                </div>

                {{-- DESKTOP TABLE VIEW --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-hover align-middle mb-0 w-100" id="restockItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 45px;">No</th>
                                <th class="text-center" style="min-width: 180px;">Nama Produk</th>
                                <th class="text-center d-none" style="width: 160px;">Satuan Beli</th>
                                <th class="text-center" style="width: 100px;">Jumlah Masuk</th>
                                <th class="text-center" style="width: 140px;">Harga Beli</th>
                                <th class="text-center" style="width: 120px;">Subtotal</th>
                                @if (!$isConfirmed)
                                    <th class="text-center no-sort" style="width: 55px;">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="restockItemRows">
                            @php
                                // Tentukan source data: old() jika validasi gagal, else existing DB items
                                $useOld   = !empty($oldItems) && is_array($oldItems);
                                $itemsArr = $useOld ? $oldItems : $existingItems;
                            @endphp

                            @if (count($itemsArr) > 0)
                                @foreach ($itemsArr as $idx => $item)
                                    @php
                                        if ($useOld) {
                                            $selectedProd   = $productsList->firstWhere('id', $item['product_id'] ?? null);
                                            $selectedUnitId = $item['restock_unit_id'] ?? ($selectedProd?->unit_id);
                                            $qty            = (int)($item['quantity'] ?? 1);
                                            $purchasePrice  = isset($item['purchase_price']) && $item['purchase_price'] !== '' ? (float)$item['purchase_price'] : null;
                                            $subtotal       = $purchasePrice !== null ? ($qty * $purchasePrice) : 0;
                                            $prodId         = $item['product_id'] ?? null;
                                        } else {
                                            $selectedProd   = $productsList->firstWhere('id', $item->product_id);
                                            $selectedUnitId = $item->restock_unit_id ?? ($selectedProd?->unit_id);
                                            $qty            = (int)$item->quantity;
                                            $purchasePrice  = (float)$item->purchase_price;
                                            $subtotal       = (float)$item->total_price;
                                            $prodId         = $item->product_id;
                                        }
                                    @endphp
                                    <tr class="restock-item-row align-middle">
                                        <td class="row-number text-center text-muted fw-medium small">{{ $loop->iteration }}</td>
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]"
                                                class="form-select form-select-sm select2-restock-product rounded-2"
                                                onchange="onProductSelectChange(this)"
                                                {{ !$isConfirmed ? 'required' : 'disabled' }}>
                                                <option value="" disabled {{ empty($prodId) ? 'selected' : '' }}>Pilih Produk...</option>
                                                @foreach ($productsList as $p)
                                                    @php
                                                        $uName       = $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs';
                                                        $defaultCost = (float)($p->unit_price ?: $p->current_hpp ?: 0);
                                                    @endphp
                                                    <option value="{{ $p->id }}"
                                                        data-unit-id="{{ $p->unit_id }}"
                                                        data-unit="{{ $uName }}"
                                                        data-stock="{{ $p->current_stock }}"
                                                        data-cost="{{ $defaultCost }}"
                                                        {{ $prodId == $p->id ? 'selected' : '' }}>
                                                        {{ $p->prod_name }} ({{ $p->prod_code ?: 'PRD-' . $p->id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            {{-- Jika confirmed, kirim value lewat hidden input karena select disabled tidak dikirim --}}
                                            @if ($isConfirmed)
                                                <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $prodId }}">
                                            @endif
                                        </td>
                                        <td class="d-none">
                                            <select name="items[{{ $idx }}][restock_unit_id]"
                                                class="form-select form-select-sm select2-restock-unit rounded-2"
                                                onchange="onUnitSelectChange(this)"
                                                {{ $isConfirmed ? 'disabled' : 'required' }}>
                                                <option value="" disabled {{ empty($selectedUnitId) ? 'selected' : '' }}>Pilih Satuan...</option>
                                                @foreach ($unitsList as $u)
                                                    <option value="{{ $u->id }}" {{ $selectedUnitId == $u->id ? 'selected' : '' }}>
                                                        {{ $u->unit_name }} ({{ $u->short_name ?: $u->unit_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($isConfirmed)
                                                <input type="hidden" name="items[{{ $idx }}][restock_unit_id]" value="{{ $selectedUnitId }}">
                                            @endif
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][quantity]"
                                                class="form-control form-control-sm font-monospace text-center rounded-2 item-qty {{ $isConfirmed ? 'bg-light text-secondary' : '' }}"
                                                value="{{ $qty }}" min="1"
                                                oninput="onItemQtyOrPriceChange(this)"
                                                {{ $isConfirmed ? 'readonly' : 'required' }}>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                                <input type="number" name="items[{{ $idx }}][purchase_price]"
                                                    class="form-control form-control-sm font-monospace text-end rounded-end-2 item-price {{ $isConfirmed ? 'bg-light text-secondary' : '' }}"
                                                    value="{{ $purchasePrice !== null ? (int)$purchasePrice : '' }}"
                                                    min="0"
                                                    oninput="onItemQtyOrPriceChange(this)"
                                                    {{ $isConfirmed ? 'readonly' : 'required' }}>
                                            </div>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark item-subtotal-cell">
                                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                                        </td>
                                        @if (!$isConfirmed)
                                            <td class="text-center">
                                                <button type="button" class="btn btn-link text-danger p-0 border-0"
                                                    onclick="removeRestockItemRow(this)" title="Hapus Baris">
                                                    <i class="bi bi-trash3-fill fs-6"></i>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @else
                                <tr id="emptyItemRow">
                                    <td colspan="{{ $isConfirmed ? 5 : 6 }}" class="text-center py-4 text-muted small">
                                        Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE CARD VIEW --}}
                <div id="restockMobileItemCards" class="d-block d-md-none py-3">
                    @php
                        $useOld   = !empty($oldItems) && is_array($oldItems);
                        $itemsArr = $useOld ? $oldItems : $existingItems;
                    @endphp

                    @if (count($itemsArr) > 0)
                        @foreach ($itemsArr as $idx => $item)
                            @php
                                if ($useOld) {
                                    $selectedProd   = $productsList->firstWhere('id', $item['product_id'] ?? null);
                                    $selectedUnitId = $item['restock_unit_id'] ?? ($selectedProd?->unit_id);
                                    $qty            = (int)($item['quantity'] ?? 1);
                                    $purchasePrice  = isset($item['purchase_price']) && $item['purchase_price'] !== '' ? (float)$item['purchase_price'] : null;
                                    $subtotal       = $purchasePrice !== null ? ($qty * $purchasePrice) : 0;
                                    $prodId         = $item['product_id'] ?? null;
                                } else {
                                    $selectedProd   = $productsList->firstWhere('id', $item->product_id);
                                    $selectedUnitId = $item->restock_unit_id ?? ($selectedProd?->unit_id);
                                    $qty            = (int)$item->quantity;
                                    $purchasePrice  = (float)$item->purchase_price;
                                    $subtotal       = (float)$item->total_price;
                                    $prodId         = $item->product_id;
                                }
                            @endphp
                            <div class="restock-mobile-item-card mb-3" data-mobile-index="{{ $idx }}">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-secondary rounded-pill">Item {{ $loop->iteration }}</span>
                                    @if (!$isConfirmed)
                                        <button type="button" class="btn btn-link text-danger p-0 border-0 small"
                                            onclick="removeMobileItemCard(this)" title="Hapus">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    @endif
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold text-dark mb-1">Nama Produk</label>
                                    <select name="items[{{ $idx }}][product_id]"
                                        class="form-select form-select-sm select2-restock-product rounded-2"
                                        onchange="onMobileProductSelectChange(this)"
                                        {{ $isConfirmed ? 'disabled' : 'required' }}>
                                        <option value="" disabled {{ empty($prodId) ? 'selected' : '' }}>Pilih Produk...</option>
                                        @foreach ($productsList as $p)
                                            <option value="{{ $p->id }}"
                                                data-unit-id="{{ $p->unit_id }}"
                                                data-unit="{{ $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs' }}"
                                                data-stock="{{ $p->current_stock }}"
                                                data-cost="{{ (float)($p->unit_price ?: $p->current_hpp ?: 0) }}"
                                                {{ $prodId == $p->id ? 'selected' : '' }}>
                                                {{ $p->prod_name }} ({{ $p->prod_code ?: 'PRD-' . $p->id }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($isConfirmed)
                                        <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $prodId }}">
                                    @endif
                                </div>
                                <div class="mb-2 d-none">
                                    <label class="form-label small fw-semibold text-dark mb-1">Satuan Beli</label>
                                    <select name="items[{{ $idx }}][restock_unit_id]"
                                        class="form-select form-select-sm select2-restock-unit rounded-2"
                                        onchange="onUnitSelectChange(this)"
                                        {{ $isConfirmed ? 'disabled' : 'required' }}>
                                        <option value="" disabled {{ empty($selectedUnitId) ? 'selected' : '' }}>Pilih Satuan...</option>
                                        @foreach ($unitsList as $u)
                                            <option value="{{ $u->id }}" {{ $selectedUnitId == $u->id ? 'selected' : '' }}>
                                                {{ $u->unit_name }} ({{ $u->short_name ?: $u->unit_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($isConfirmed)
                                        <input type="hidden" name="items[{{ $idx }}][restock_unit_id]" value="{{ $selectedUnitId }}">
                                    @endif
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-5">
                                        <label class="form-label small fw-semibold text-dark mb-1">Jumlah Masuk</label>
                                        <input type="number" name="items[{{ $idx }}][quantity]"
                                            class="form-control form-control-sm font-monospace text-center item-qty {{ $isConfirmed ? 'bg-light text-secondary' : '' }}"
                                            value="{{ $qty }}" min="1"
                                            oninput="onMobileItemChange(this)"
                                            {{ $isConfirmed ? 'readonly' : 'required' }}>
                                    </div>
                                    <div class="col-7">
                                        <label class="form-label small fw-semibold text-dark mb-1">Harga Beli/Unit</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-end-0">Rp</span>
                                            <input type="number" name="items[{{ $idx }}][purchase_price]"
                                                class="form-control form-control-sm font-monospace text-end item-price {{ $isConfirmed ? 'bg-light text-secondary' : '' }}"
                                                value="{{ $purchasePrice !== null ? (int)$purchasePrice : '' }}"
                                                min="0"
                                                oninput="onMobileItemChange(this)"
                                                {{ $isConfirmed ? 'readonly' : 'required' }}>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded-2 bg-success bg-opacity-10 border border-success border-opacity-25">
                                    <span class="small fw-semibold text-success">Subtotal</span>
                                    <span class="fw-bold font-monospace text-success mobile-subtotal-val">
                                        Rp {{ number_format($subtotal, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div id="mobileEmptyItemRow" class="text-center py-4 text-muted small">
                            Belum ada produk. Klik "+ Tambah" di atas untuk menambahkan item.
                        </div>
                    @endif
                </div>

                {{-- Action Buttons — bagian bawah card --}}
                <div class="border-top pt-4 pb-3">
                    @if ($isConfirmed)
                        {{-- CONFIRMED: hanya Kembali + Rollback (jika masih dalam 3 jam) --}}
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <a href="{{ route('restock.index') }}" class="btn btn-light border rounded-2 px-4">
                                Batal
                            </a>
                            @if ($canRollback)
                                <button type="button" class="btn btn-danger rounded-2 px-3 d-inline-flex align-items-center gap-2"
                                    onclick="openRollbackRestockModal()">
                                    <i class="bi bi-arrow-counterclockwise"></i> Rollback Restock
                                </button>
                            @else
                                <span class="text-muted small fst-italic">
                                    <i class="bi bi-clock-history me-1"></i> Rollback tidak tersedia (batas 3 jam terlewat)
                                </span>
                            @endif
                        </div>
                    @else
                        {{-- DRAFT: Batal, Simpan Draft, Simpan --}}
                        {{-- Desktop --}}
                        <div class="d-none d-md-flex align-items-center justify-content-between gap-2">
                            <a href="{{ route('restock.index') }}" class="btn btn-light border rounded-2 px-4">
                                Batal
                            </a>
                            <div class="d-flex gap-3">
                                <button type="button" class="btn btn-outline-secondary rounded-2 px-4 d-inline-flex align-items-center gap-2"
                                    onclick="submitRestockAs('DRAFT')">
                                    Simpan Perubahan Draft
                                </button>
                                <button type="button" class="btn btn-success rounded-2 px-4 d-inline-flex align-items-center gap-2"
                                    onclick="openConfirmRestockModal()">
                                    Simpan
                                </button>
                            </div>
                        </div>
                        {{-- Mobile --}}
                        <div class="d-flex d-md-none flex-column gap-2">
                            <div class="d-flex justify-content-between gap-2">
                                <button type="button" class="btn btn-secondary rounded-2 flex-fill d-inline-flex align-items-center justify-content-center gap-2"
                                    onclick="submitRestockAs('DRAFT')">
                                    Simpan Draft
                                </button>
                                <button type="button" class="btn btn-success rounded-2 flex-fill d-inline-flex align-items-center justify-content-center gap-2"
                                    onclick="openConfirmRestockModal()">
                                    Simpan
                                </button>
                            </div>
                            <a href="{{ route('restock.index') }}" class="btn btn-light border rounded-2 w-100 text-center">
                                Batal
                            </a>
                        </div>
                    @endif
                </div>

            </div>{{-- /card Daftar Produk Masuk --}}
        </div>

    </div>
</form>

{{-- ── Modal Rollback Restock (hanya muncul jika CONFIRMED & masih dalam 3 jam) ── --}}
@if ($isConfirmed && $canRollback)
<div class="modal fade" id="modalRollbackRestock" tabindex="-1" aria-labelledby="modalRollbackRestockLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="modalRollbackRestockLabel">
                    <i class="bi bi-arrow-counterclockwise fs-5"></i> Rollback Transaksi Restock
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-secondary mb-3">
                    Apakah Anda yakin ingin <strong>membatalkan (rollback)</strong> transaksi restock
                    <strong class="text-dark">{{ $restock->restock_code }}</strong>?
                </p>

                {{-- Sisa waktu rollback --}}
                <div class="d-flex align-items-center gap-2 rounded-2 px-3 py-2 mb-3"
                     style="background-color: #fff8e1; border: 1px solid #fde68a;">
                    <i class="bi bi-clock text-warning fs-5 flex-shrink-0"></i>
                    <div class="small">
                        <div class="fw-bold text-dark">Sisa waktu rollback</div>
                        <div class="text-secondary">
                            <span class="fw-bold text-warning" id="modalRollbackCountdown">{{ $remainingTimeStr }}</span>
                            — batas hingga <span class="fw-semibold text-dark">{{ $deadlineFormatted }}</span>
                        </div>
                    </div>
                </div>

                <div class="alert alert-danger border-danger border-opacity-25 rounded-2 p-3 mb-0 small">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Perhatian:</div>
                    <ul class="mb-0 ps-3">
                        <li>Stok produk akan <strong>dikurangi kembali</strong> sesuai kuantitas restock ini.</li>
                        <li>Harga bahan utama produk akan <strong>dikembalikan ke harga sebelum restock ini</strong>.</li>
                        <li>Status transaksi akan kembali menjadi <span class="badge bg-secondary">DRAFT</span> — data restock <strong>tidak dihapus</strong>.</li>
                        <li>Tindakan ini <strong>tidak dapat dibatalkan</strong>.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="{{ route('restock.destroy', $restock->id) }}" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded-2 px-4">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Ya, Rollback Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Modal Konfirmasi Simpan & Selesaikan (DRAFT only) ── --}}
@if (!$isConfirmed)
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
                        <li>Status transaksi akan tersimpan sebagai <span class="badge bg-success">SELESAI</span>.</li>
                        <li>Setelah disimpan, form hanya bisa diubah dalam <strong>3 jam</strong> lewat fitur rollback.</li>
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
@endif

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    window.restockProductsList  = @json($productsList);
    window.restockUnitsList     = @json($unitsList);
    window.rollbackDeadline     = @json($isConfirmed && $canRollback ? $rollbackDeadline->toIso8601String() : null);
</script>
<script src="{{ asset('js/restock.js') }}"></script>
@endpush