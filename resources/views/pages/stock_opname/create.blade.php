@extends('layouts.admin')

@section('title', 'Pemeriksaan Stok Opname — Warung Pojok Oremus')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('css/stock_opname.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('stock-opname.index') }}" class="text-decoration-none text-muted">stock opname</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">tambah opname</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $productsList = $products ?? collect();
    $oldItems = old('items', []);
@endphp

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Pemeriksaan Stok Opname</h4>
    </div>
    <a href="{{ route('stock-opname.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Menyimpan Stock Opname:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('stock-opname.store') }}" method="POST" id="formStockOpname">
    @csrf
    <input type="hidden" name="status_opname" id="status_opname" value="{{ old('status_opname', 'DRAFT') }}">

    <div class="row g-4">

        <!-- ── Section 1: Informasi Umum ── -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4">
                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                   Informasi Stock Opname
                </h6>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="opname_code" class="form-label small fw-semibold text-dark">Kode Stock Opname</label>
                        <input type="text" class="form-control font-monospace bg-light rounded-2 text-secondary fw-bold"
                            id="opname_code" name="opname_code" value="{{ old('opname_code', $generatedCode ?? '') }}" readonly>
                    </div>
                    <div class="col-md-12">
                        <label for="opname_date" class="form-label small fw-semibold text-dark">Tanggal Pemeriksaan <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control rounded-2 @error('opname_date') is-invalid @enderror"
                            id="opname_date" name="opname_date" value="{{ old('opname_date', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('opname_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-12">
                        <label for="notes" class="form-label small fw-semibold text-dark">Catatan Pemeriksaan (Opsional)</label>
                        <textarea class="form-control rounded-2 @error('notes') is-invalid @enderror"
                            id="notes" name="notes" rows="5">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Section 2: Daftar Produk + Action (satu card) ── -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4">

                <!-- Header -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                           Daftar Produk & Hasil Hitung Fisik
                        </h6>
                        <span class="text-muted small">Pilih produk, sistem akan menampilkan stok saat ini. Masukkan hasil stok fisik di lapangan.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-2 px-3 d-inline-flex align-items-center gap-1" id="btnAddRow">
                        <i class="bi bi-plus-lg"></i> Tambah Produk
                    </button>
                </div>

                <!-- Desktop Table -->
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
                            @if (!empty($oldItems) && is_array($oldItems))
                                @foreach ($oldItems as $idx => $item)
                                    @php $selectedProd = $productsList->firstWhere('id', $item['product_id'] ?? null); @endphp
                                    <tr class="opname-row">
                                        <td class="text-center fw-semibold text-secondary row-number">{{ $loop->iteration }}</td>
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm product-select" required>
                                                <option value="">-- Pilih Produk --</option>
                                                @foreach ($productsList as $prod)
                                                    <option value="{{ $prod->id }}"
                                                        data-unit="{{ $prod->unit->unit_name ?? '-' }}"
                                                        data-stock="{{ $prod->current_stock }}"
                                                        {{ ($item['product_id'] ?? '') == $prod->id ? 'selected' : '' }}>
                                                        {{ $prod->prod_code }} - {{ $prod->prod_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center unit-cell text-muted small fw-semibold d-none">{{ $selectedProd->unit->unit_name ?? '-' }}</td>
                                        <td class="text-center system-stock-cell fw-bold">{{ $selectedProd->initial_stock ?? 0 }}</td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][physical_stock]"
                                                class="form-control form-control-sm text-center fw-bold physical-stock-input"
                                                value="{{ $item['physical_stock'] ?? 0 }}" min="0" required>
                                        </td>
                                        <td class="text-center diff-cell text-secondary">-</td>
                                        <td class="text-center">
                                            <button type="button" class="btn-delete-row" title="Hapus Baris"><i class="bi bi-trash3"></i></button>
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
                                    <td class="text-center system-stock-cell fw-bold">-</td>
                                    <td>
                                        <input type="number" name="items[0][physical_stock]"
                                            class="form-control form-control-sm text-center fw-bold physical-stock-input"
                                            min="0" required>
                                    </td>
                                    <td class="text-center diff-cell text-secondary">-</td>
                                    <td class="text-center">
                                        <button type="button" class="btn-delete-row" title="Hapus Baris"><i class="bi bi-trash3"></i></button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards (diisi JS) -->
                <div class="d-block d-md-none" id="opnameMobileCards"></div>


                <!-- Action Buttons -->
                <div class="d-none d-md-flex flex-wrap align-items-center justify-content-between gap-2 mt-5">
                    <a href="{{ route('stock-opname.index') }}" class="btn btn-light border rounded-2 px-4">
                        Batal
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-2 px-4 d-inline-flex align-items-center gap-2" id="btnSaveDraft">
                           Simpan sebagai Draft
                        </button>
                        <button type="button" class="btn btn-success rounded-2 px-4 d-inline-flex align-items-center gap-2" id="btnOpenConfirmModal">
                        Simpan
                        </button>
                    </div>
                </div>

                {{-- Mobile Layout button --}}
                <div class="d-flex d-md-none flex-column gap-2 mt-4">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary rounded-2 flex-fill d-inline-flex align-items-center justify-content-center gap-2" id="btnSaveDraftMobile">
                            Simpan Draft
                        </button>
                        <button type="button" class="btn btn-success rounded-2 flex-fill d-inline-flex align-items-center justify-content-center gap-2" id="btnOpenConfirmModalMobile">
                            Simpan
                        </button>
                    </div>
                    <a href="{{ route('stock-opname.index') }}" class="btn btn-outline-secondary rounded-2 w-100">
                       Batal
                    </a>
                </div>

            </div>
        </div>

    </div>
</form>

<!-- Modal Konfirmasi -->
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
                <p class="text-secondary mb-3">Apakah Anda yakin ingin menyelesaikan transaksi <strong>Stock Opname</strong> ini?</p>
                <div class="alert alert-warning border-warning border-opacity-25 rounded-2 p-3 mb-3 small">
                    <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> Perhatian:</div>
                    <ul class="mb-0 ps-3">
                        <li>Stok master produk akan <strong>langsung disinkronkan</strong> dengan angka Stok Fisik.</li>
                        <li>Status transaksi akan menjadi <span class="badge bg-success">COMPLETED</span> dan tidak dapat diedit kembali.</li>
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

<!-- Template Row -->
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
        <td class="text-center system-stock-cell fw-bold">-</td>
        <td>
            <input type="number" name="items[__INDEX__][physical_stock]"
                class="form-control form-control-sm text-center fw-bold physical-stock-input"
                min="0" required>
        </td>
        <td class="text-center diff-cell text-secondary">-</td>
        <td class="text-center">
            <button type="button" class="btn-delete-row" title="Hapus Baris"><i class="bi bi-trash3"></i></button>
        </td>
    </tr>
</template>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/stock_opname.js') }}"></script>
<script>
$(document).ready(function () {
    $('#btnSaveDraftMobile').on('click', function () { $('#btnSaveDraft').trigger('click'); });
    $('#btnOpenConfirmModalMobile').on('click', function () { $('#btnOpenConfirmModal').trigger('click'); });
});
</script>
@endpush