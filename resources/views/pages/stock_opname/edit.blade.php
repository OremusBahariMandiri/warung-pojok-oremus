@extends('layouts.admin')

@section('title', 'Edit Stock Opname ' . $stockOpname->opname_code . ' — Warung Pojok Oremus')

@push('styles')
<!-- Select2 CSS & Bootstrap 5 Theme -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('css/stock_opname.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('stock-opname.index') }}" class="text-decoration-none text-muted">stock opname</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">edit opname</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $productsList  = $products ?? collect();
    $existingItems = old('items', $stockOpname->items->toArray() ?? []);
    $isCompleted   = $stockOpname->status_opname === 'COMPLETED';
    $readonly      = $isCompleted; // true = semua input dikunci

    // ── Rollback window: 3 jam sejak opname_date ──────────────────
    // Gunakan updated_at sebagai acuan waktu selesai jika tersedia,
    // fallback ke opname_date agar tetap aman.
    $completedAt      = $stockOpname->updated_at ?? $stockOpname->opname_date;
    $rollbackDeadline = $completedAt ? $completedAt->copy()->addHours(3) : null;
    $canRollback      = $isCompleted && $rollbackDeadline && now()->lessThan($rollbackDeadline);
    $rollbackExpired  = $isCompleted && $rollbackDeadline && now()->greaterThanOrEqualTo($rollbackDeadline);

    // Sisa waktu rollback (untuk tampilan countdown)
    $minutesLeft = $canRollback ? (int) now()->diffInMinutes($rollbackDeadline) : 0;
    $hoursLeft   = $canRollback ? floor($minutesLeft / 60) : 0;
    $minsLeft    = $canRollback ? ($minutesLeft % 60) : 0;
@endphp

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            {{ $readonly ? 'Detail' : 'Edit' }} Stock Opname: {{ $stockOpname->opname_code }}
        </h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('stock-opname.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

{{-- ── Banner: Form Dikunci (COMPLETED) ───────────────────────────── --}}
@if ($isCompleted)
<div class="opname-locked-banner mb-4">
    <div class="opname-locked-banner__icon">
        <i class="bi bi-lock-fill"></i>
    </div>
    <div class="opname-locked-banner__body">
        <div class="opname-locked-banner__title">
            Transaksi ini sudah berstatus
            <span class="badge bg-success ms-1">SELESAI</span>
            — Form dikunci
        </div>
        <div class="opname-locked-banner__desc">
            Semua field tidak dapat diubah karena transaksi sudah dikonfirmasi.
            @if ($canRollback)
                Gunakan tombol <strong>Rollback Opname</strong> di bawah untuk membatalkan transaksi ini.
            @endif
        </div>

        {{-- Sub-baris: status rollback window --}}
        @if ($canRollback)
            {{-- Masih bisa rollback, tampilkan countdown live --}}
            <div class="opname-locked-banner__rollback-info opname-locked-banner__rollback-info--available mt-1"
                 id="rollbackInfoAvailable">
                <i class="bi bi-clock me-1"></i>
                Batas rollback:
                <strong>
                    {{ $rollbackDeadline->format('d M Y, H:i') }} WIB
                </strong>
                (sisa <span id="rollbackCountdown">--</span>)
            </div>
        @elseif ($rollbackExpired)
            {{-- Sudah lewat batas rollback --}}
            <div class="opname-locked-banner__rollback-info opname-locked-banner__rollback-info--expired mt-1"
                 id="rollbackInfoExpired">
                <i class="bi bi-clock me-1"></i>
                Batas waktu rollback (3 jam) sudah terlewat. Transaksi ini tidak dapat dibatalkan.
            </div>
        @endif
    </div>
    {{-- TIDAK ADA tombol rollback di sini — dipindah ke action buttons bawah --}}
</div>
@endif

{{-- ── Validation Errors ───────────────────────────────────────────── --}}
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Memperbarui Stock Opname:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- data-readonly dipakai JS untuk mendeteksi mode readonly --}}
<form action="{{ route('stock-opname.update', $stockOpname->id) }}" method="POST" id="formStockOpname"
      data-readonly="{{ $readonly ? 'true' : 'false' }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="status_opname" id="status_opname" value="{{ old('status_opname', $stockOpname->status_opname) }}">

    <div class="row g-4">
        <!-- Section 1: Informasi Umum Stock Opname -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4">
                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                    Informasi Stock Opname
                </h6>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="opname_code" class="form-label small fw-semibold text-dark">Kode Stock Opname</label>
                        <input type="text" class="form-control font-monospace bg-light rounded-2 text-secondary fw-bold"
                            id="opname_code" name="opname_code" value="{{ $stockOpname->opname_code }}" readonly>
                    </div>

                    <div class="col-md-12">
                        <label for="opname_date" class="form-label small fw-semibold text-dark">Tanggal Pemeriksaan <span class="text-danger">*</span></label>
                        <input type="datetime-local"
                            class="form-control rounded-2 @error('opname_date') is-invalid @enderror {{ $readonly ? 'bg-light' : '' }}"
                            id="opname_date" name="opname_date"
                            value="{{ old('opname_date', $stockOpname->opname_date ? $stockOpname->opname_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                            {{ $readonly ? 'readonly' : '' }} required>
                        @error('opname_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="notes" class="form-label small fw-semibold text-dark">Catatan Pemeriksaan (Opsional)</label>
                        <textarea class="form-control rounded-2 @error('notes') is-invalid @enderror {{ $readonly ? 'bg-light' : '' }}"
                            id="notes" name="notes" rows="5"
                            {{ $readonly ? 'readonly' : '' }}>{{ old('notes', $stockOpname->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2 + Action Buttons: digabung dalam satu card -->
        <div class="col-lg-12 mt-4">
            <div class="card-box bg-white border rounded-3 p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            Daftar Produk & Hasil Hitung Fisik
                        </h6>
                        <span class="text-muted small">
                            @if ($readonly)
                                Daftar produk terkunci — transaksi sudah selesai.
                            @else
                                Pilih produk, sistem akan menampilkan stok saat ini. Masukkan hasil stok fisik di lapangan.
                            @endif
                        </span>
                    </div>
                    {{-- Tombol tambah produk hanya tampil jika tidak readonly --}}
                    @if (!$readonly)
                    <button type="button" class="btn btn-sm btn-outline-success rounded-2 px-3 d-inline-flex align-items-center gap-1" id="btnAddRow">
                        <i class="bi bi-plus-lg"></i> Tambah Produk
                    </button>
                    @endif
                </div>

                {{-- ── DESKTOP TABLE (hidden on mobile) ── --}}
                <div class="table-responsive opname-table-responsive d-none d-md-block">
                    <table class="table table-bordered align-middle mb-0" id="opnameItemsTable">
                        <thead>
                            <tr>
                                <th class="col-no text-center">No</th>
                                <th class="col-product">Produk <span class="text-danger">*</span></th>
                                <th class="col-unit text-center d-none">Satuan</th>
                                <th class="col-sys-stock text-center">Stok Sistem</th>
                                <th class="col-phys-stock text-center">Stok Fisik <span class="text-danger">*</span></th>
                                <th class="col-diff text-center">Selisih</th>
                                <th class="col-action text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (!empty($existingItems) && count($existingItems) > 0)
                                @foreach ($existingItems as $idx => $item)
                                    @php
                                        $productId   = is_array($item) ? ($item['product_id'] ?? null) : $item->product_id;
                                        $physStock   = (int)(is_array($item) ? ($item['physical_stock'] ?? 0) : $item->physical_stock);
                                        $selectedProd = $productsList->firstWhere('id', $productId);
                                        $sysStock    = (int)($selectedProd->current_stock ?? 0);
                                        $diff        = $physStock - $sysStock;
                                    @endphp
                                    <tr class="opname-row">
                                        <td class="text-center fw-semibold text-secondary row-number">{{ $loop->iteration }}</td>
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]"
                                                class="form-select form-select-sm product-select"
                                                {{ $readonly ? 'disabled' : '' }} required>
                                                <option value="">-- Pilih Produk --</option>
                                                @foreach ($productsList as $prod)
                                                    <option value="{{ $prod->id }}"
                                                        data-unit="{{ $prod->unit->unit_name ?? '-' }}"
                                                        data-stock="{{ $prod->current_stock }}"
                                                        {{ $productId == $prod->id ? 'selected' : '' }}>
                                                        {{ $prod->prod_code }} - {{ $prod->prod_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            {{-- Jika readonly, kirim product_id via hidden input karena disabled tidak terkirim --}}
                                            @if ($readonly)
                                                <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $productId }}">
                                            @endif
                                        </td>
                                        <td class="text-center unit-cell text-muted small fw-semibold d-none">
                                            {{ $selectedProd->unit->unit_name ?? '-' }}
                                        </td>
                                        <td class="text-center system-stock-cell fw-bold text-navy">
                                            {{ $selectedProd ? $sysStock : '-' }}
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][physical_stock]"
                                                class="form-control form-control-sm text-center fw-bold physical-stock-input {{ $readonly ? 'bg-light' : '' }}"
                                                value="{{ $physStock }}" min="0"
                                                {{ $readonly ? 'readonly' : '' }} required>
                                        </td>
                                        <td class="text-center diff-cell diff-badge-container">
                                            @if ($productId)
                                                @if ($diff === 0)
                                                    <span class="diff-badge zero"><i class="bi bi-check-circle-fill"></i> 0 (Sesuai)</span>
                                                @elseif ($diff > 0)
                                                    <span class="diff-badge surplus"><i class="bi bi-arrow-up-circle-fill"></i> +{{ $diff }} (Lebih)</span>
                                                @else
                                                    <span class="diff-badge deficit"><i class="bi bi-arrow-down-circle-fill"></i> {{ $diff }} (Kurang)</span>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{-- Tombol hapus hanya tampil jika tidak readonly --}}
                                            @if (!$readonly)
                                                <button type="button" class="btn-delete-row" title="Hapus Baris">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="opname-row">
                                    <td class="text-center fw-semibold text-secondary row-number">1</td>
                                    <td>
                                        <select name="items[0][product_id]" class="form-select form-select-sm product-select" required>
                                            <option value="">-- Pilih Produk --</option>
                                            @foreach ($productsList as $prod)
                                                <option value="{{ $prod->id }}"
                                                    data-unit="{{ $prod->unit->unit_name ?? '-' }}"
                                                    data-stock="{{ $prod->current_stock }}">
                                                    {{ $prod->prod_code }} - {{ $prod->prod_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center unit-cell text-muted small fw-semibold d-none">-</td>
                                    <td class="text-center system-stock-cell fw-bold text-navy">-</td>
                                    <td>
                                        <input type="number" name="items[0][physical_stock]"
                                            class="form-control form-control-sm text-center fw-bold physical-stock-input"
                                            value="0" min="0" required>
                                    </td>
                                    <td class="text-center diff-cell diff-badge-container">-</td>
                                    <td class="text-center">
                                        <button type="button" class="btn-delete-row" title="Hapus Baris">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- ── MOBILE CARD VIEW (hidden on desktop) ── --}}
                <div class="d-block d-md-none" id="opnameMobileCards">
                    @if (!empty($existingItems) && count($existingItems) > 0)
                        @foreach ($existingItems as $idx => $item)
                            @php
                                $productId    = is_array($item) ? ($item['product_id'] ?? null) : $item->product_id;
                                $physStock    = (int)(is_array($item) ? ($item['physical_stock'] ?? 0) : $item->physical_stock);
                                $selectedProd = $productsList->firstWhere('id', $productId);
                                $sysStock     = $selectedProd ? (int)($selectedProd->current_stock ?? 0) : null;
                                $diff         = $sysStock !== null ? ($physStock - $sysStock) : null;
                            @endphp
                            <div class="opname-item-card" data-row-idx="{{ $idx }}">
                                <div class="card-header-row">
                                    <span class="card-num-badge">{{ $loop->iteration }}</span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold text-dark small mobile-product-name">
                                            @if ($selectedProd)
                                                {{ $selectedProd->prod_code }} - {{ $selectedProd->prod_name }}
                                            @else
                                                <span class="text-muted">-- Belum dipilih --</span>
                                            @endif
                                        </div>
                                        <div class="text-muted d-none" style="font-size:0.75rem;">Satuan: <span class="mobile-unit-val">{{ $selectedProd->unit->unit_name ?? '-' }}</span></div>
                                    </div>
                                    {{-- Tombol hapus di mobile hanya tampil jika tidak readonly --}}
                                    @if (!$readonly)
                                        <button type="button" class="btn-delete-row ms-2" title="Hapus"><i class="bi bi-trash3"></i></button>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <label class="mobile-field-label">Produk <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm mobile-product-select"
                                        data-row-idx="{{ $idx }}"
                                        {{ $readonly ? 'disabled' : '' }}>
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach ($productsList as $prod)
                                            <option value="{{ $prod->id }}"
                                                data-unit="{{ $prod->unit->unit_name ?? '-' }}"
                                                data-stock="{{ $prod->current_stock }}"
                                                {{ $productId == $prod->id ? 'selected' : '' }}>
                                                {{ $prod->prod_code }} - {{ $prod->prod_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="mobile-field-label">Stok Sistem</label>
                                        <div class="fw-bold mobile-sys-stock-val">{{ $sysStock !== null ? $sysStock : '-' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <label class="mobile-field-label">Stok Fisik <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-sm text-center fw-bold mobile-phys-input {{ $readonly ? 'bg-light' : '' }}"
                                            data-row-idx="{{ $idx }}" value="{{ $physStock }}" min="0"
                                            {{ $readonly ? 'readonly' : '' }}>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <label class="mobile-field-label">Selisih</label>
                                    <div class="mobile-diff-val">
                                        @if ($diff !== null)
                                            @if ($diff === 0)
                                                <span class="diff-badge zero"><i class="bi bi-check-circle-fill"></i> 0 (Sesuai)</span>
                                            @elseif ($diff > 0)
                                                <span class="diff-badge surplus"><i class="bi bi-arrow-up-circle-fill"></i> +{{ $diff }} (Lebih)</span>
                                            @else
                                                <span class="diff-badge deficit"><i class="bi bi-arrow-down-circle-fill"></i> {{ $diff }} (Kurang)</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="opname-item-card" data-row-idx="0">
                            <div class="card-header-row">
                                <span class="card-num-badge">1</span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-dark small mobile-product-name">
                                        <span class="text-muted">-- Belum dipilih --</span>
                                    </div>
                                    <div class="text-muted d-none" style="font-size:0.75rem;">Satuan: <span class="mobile-unit-val">-</span></div>
                                </div>
                                <button type="button" class="btn-delete-row ms-2" title="Hapus"><i class="bi bi-trash3"></i></button>
                            </div>
                            <div class="mb-3">
                                <label class="mobile-field-label">Produk <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm mobile-product-select" data-row-idx="0">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach ($productsList as $prod)
                                        <option value="{{ $prod->id }}"
                                            data-unit="{{ $prod->unit->unit_name ?? '-' }}"
                                            data-stock="{{ $prod->current_stock }}">
                                            {{ $prod->prod_code }} - {{ $prod->prod_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="mobile-field-label">Stok Sistem</label>
                                    <div class="fw-bold mobile-sys-stock-val">-</div>
                                </div>
                                <div class="col-6">
                                    <label class="mobile-field-label">Stok Fisik <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-sm text-center fw-bold mobile-phys-input"
                                        data-row-idx="0" value="0" min="0">
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="mobile-field-label">Selisih</label>
                                <div class="mobile-diff-val">-</div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ── ACTION BUTTONS ── --}}
                {{-- DESKTOP --}}
                <div class="d-none d-md-flex align-items-center justify-content-between gap-2 mt-4 pt-3">
                    <a href="{{ route('stock-opname.index') }}" class="btn btn-light border rounded-2 px-4">
                        {{ $readonly ? 'Kembali' : 'Batal' }}
                    </a>
                    @if (!$readonly)
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-2 px-4 d-inline-flex align-items-center gap-2" id="btnSaveDraft">
                            Simpan Perubahan Draft
                        </button>
                        <button type="button" class="btn btn-success rounded-2 px-4 d-inline-flex align-items-center gap-2" id="btnOpenConfirmModal">
                            Simpan
                        </button>
                    </div>
                   @elseif ($isCompleted)
                    @if ($canRollback)
                        <button type="button"
                            id="btnRollbackDesktop"
                            class="btn btn-outline-danger rounded-2 px-4 d-inline-flex align-items-center gap-2"
                            data-bs-toggle="modal"
                            data-bs-target="#modalRollbackOpname"
                            data-deadline="{{ $rollbackDeadline->timestamp * 1000 }}">
                            <i class="bi bi-arrow-counterclockwise"></i> Rollback Opname
                        </button>
                    @else
                        <span class="text-muted small fst-italic">
                            <i class="bi bi-clock-history me-1"></i> Rollback tidak tersedia (batas 3 jam terlewat)
                        </span>
                    @endif
                @endif
                </div>

                {{-- MOBILE --}}
                <div class="d-flex d-md-none flex-column gap-2 mt-4 pt-3 border-top">
                    @if (!$readonly)
                    <div class="d-flex justify-content-between gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-2 flex-fill d-inline-flex align-items-center justify-content-center gap-2" id="btnSaveDraftMobile">
                            Simpan Draft
                        </button>
                        <button type="button" class="btn btn-success rounded-2 flex-fill d-inline-flex align-items-center justify-content-center gap-2" id="btnOpenConfirmModalMobile">
                            Simpan
                        </button>
                    </div>
                    @elseif ($isCompleted)
                        @if ($canRollback)
                            <button type="button"
                                id="btnRollbackMobile"
                                class="btn btn-outline-danger rounded-2 w-100 d-inline-flex align-items-center justify-content-center gap-2"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRollbackOpname"
                                data-deadline="{{ $rollbackDeadline->timestamp * 1000 }}">
                                <i class="bi bi-arrow-counterclockwise"></i> Rollback Opname
                            </button>
                        @else
                            <span class="text-muted small fst-italic">
                                <i class="bi bi-clock-history me-1"></i> Rollback tidak tersedia (batas 3 jam terlewat)
                            </span>
                        @endif
                    @endif
                    <a href="{{ route('stock-opname.index') }}" class="btn btn-light border rounded-2 w-100 text-center">
                        {{ $readonly ? 'Kembali' : 'Batal' }}
                    </a>
                </div>

            </div>
        </div>
    </div>
</form>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- MODAL: Konfirmasi Rollback Opname                           --}}
{{-- Hanya dirender jika masih dalam window rollback             --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if ($canRollback)
<div class="modal fade" id="modalRollbackOpname" tabindex="-1" aria-labelledby="modalRollbackLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">

            {{-- Header --}}
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2 fs-6" id="modalRollbackLabel">
                    <i class="bi bi-arrow-counterclockwise fs-5"></i> Rollback Transaksi Stock Opname
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body px-4 pt-3 pb-2">
                {{-- Pertanyaan konfirmasi --}}
                <p class="text-secondary mb-3" style="font-size: 0.92rem;">
                    Apakah Anda yakin ingin <strong>membatalkan (rollback)</strong><br>
                    transaksi Stock Opname <strong>{{ $stockOpname->opname_code }}</strong>?
                </p>

                {{-- Box sisa waktu rollback (live countdown) --}}
                <div class="rounded-2 border border-warning-subtle mb-3 px-3 py-2" style="background: #fffbeb;">
                    <div class="fw-bold text-dark small mb-1">Sisa waktu rollback</div>
                    <div class="d-flex align-items-center gap-2" style="font-size: 0.88rem;">
                        <i class="bi bi-clock text-warning"></i>
                        <span class="fw-bold text-warning" id="rollbackCountdownModal">--</span>
                        <span class="text-secondary">— batas hingga
                            <strong class="text-dark">{{ $rollbackDeadline->format('d M Y, H:i') }} WIB</strong>
                        </span>
                    </div>
                </div>

                {{-- Box perhatian (merah) --}}
                <div class="rounded-2 border border-danger-subtle p-3 small" style="background: #fff5f5;">
                    <div class="fw-bold text-danger mb-2 d-flex align-items-center gap-1">
                        <i class="bi bi-exclamation-triangle"></i> Perhatian:
                    </div>
                    <ul class="mb-0 ps-3 text-secondary" style="line-height: 1.8;">
                        <li>Stok produk akan <strong class="text-dark">dikembalikan</strong> ke kondisi sebelum opname dilakukan.</li>
                        <li>Status transaksi akan kembali menjadi <span class="badge bg-secondary" style="font-size:0.72rem;">DRAFT</span> — data opname <strong class="text-dark">tidak dihapus</strong>.</li>
                        <li>Tindakan ini <strong class="text-dark">tidak dapat dibatalkan</strong>.</li>
                    </ul>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer border-0 px-4 pt-2 pb-4 d-flex justify-content-between gap-2">
                <button type="button"
                    class="btn btn-outline-secondary rounded-2 px-4"
                    data-bs-dismiss="modal">
                    Batal
                </button>
                <form action="{{ route('stock-opname.destroy', $stockOpname->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded-2 px-4 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-arrow-counterclockwise"></i> Ya, Rollback Sekarang
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- MODAL: Konfirmasi Selesaikan Opname (hanya saat DRAFT)      --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if (!$readonly)
<div class="modal fade" id="modalConfirmComplete" tabindex="-1" aria-labelledby="modalConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalConfirmLabel">
                    <i class="bi bi-exclamation-circle-fill text-warning fs-5"></i> Konfirmasi Selesaikan Stock Opname
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-secondary mb-3">
                    Apakah Anda yakin ingin menyelesaikan transaksi <strong>Stock Opname</strong> ini?
                </p>
                <div class="alert alert-warning border-warning border-opacity-25 rounded-2 p-3 mb-3 small">
                    <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> Perhatian:</div>
                    <ul class="mb-0 ps-3">
                        <li>Stok master produk akan <strong>langsung disinkronkan</strong> dengan angka Stok Fisik.</li>
                        <li>Status transaksi akan menjadi <span class="badge bg-success">SELESAI</span> dan tidak dapat diedit kembali.</li>
                    </ul>
                </div>
                <div class="p-3 bg-light rounded-2 small text-secondary">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Total Produk Diperiksa:</span>
                        <strong class="text-dark" id="modalTotalChecked">0</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Produk Berselisih:</span>
                        <strong class="text-warning" id="modalTotalDiff">0</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success rounded-2 px-4" id="btnConfirmCompleteSubmit">
                    <i class="bi bi-check2-all me-1"></i> Ya, Selesaikan & Sinkron Stok
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Template Row for Dynamic Addition (hanya dipakai saat tidak readonly) -->
<template id="rowTemplate">
    <tr class="opname-row">
        <td class="text-center fw-semibold text-secondary row-number"></td>
        <td>
            <select name="items[__INDEX__][product_id]" class="form-select form-select-sm product-select" required>
                <option value="">-- Pilih Produk --</option>
                @foreach ($productsList as $prod)
                    <option value="{{ $prod->id }}"
                        data-unit="{{ $prod->unit->unit_name ?? '-' }}"
                        data-stock="{{ $prod->current_stock }}">
                        {{ $prod->prod_code }} - {{ $prod->prod_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td class="text-center unit-cell text-muted small fw-semibold d-none">-</td>
        <td class="text-center system-stock-cell fw-bold text-navy">-</td>
        <td>
            <input type="number" name="items[__INDEX__][physical_stock]"
                class="form-control form-control-sm text-center fw-bold physical-stock-input"
                value="0" min="0" required>
        </td>
        <td class="text-center diff-cell diff-badge-container">-</td>
        <td class="text-center">
            <button type="button" class="btn-delete-row" title="Hapus Baris">
                <i class="bi bi-trash3"></i>
            </button>
        </td>
    </tr>
</template>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/stock_opname.js') }}"></script>

{{-- ── Live Rollback Countdown (external JS) ───────────────────────── --}}
{{-- Hanya diload jika transaksi COMPLETED dan masih ada deadline       --}}
@if ($isCompleted && $rollbackDeadline)
<script src="{{ asset('js/stock_opname.js') }}"
        data-deadline="{{ $rollbackDeadline->timestamp * 1000 }}">
</script>
@endif

@endpush