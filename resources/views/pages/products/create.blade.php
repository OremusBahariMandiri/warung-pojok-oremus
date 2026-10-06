@extends('layouts.admin')

@section('title', 'Tambah Produk Baru — Warung Pojok Oremus')

@push('styles')
<!-- Select2 CSS & Bootstrap 5 Theme -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('css/products.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}" class="text-decoration-none text-muted">management produk</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">tambah produk</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $hppList = $hppComponents ?? collect();
    $unitsList = $units ?? collect();
    $oldConfigs = old('selling_configs', []);
@endphp

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Tambah Produk Baru</h4>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<!-- Display Validation Errors if Any -->
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Menyimpan Produk:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="formCreateProduct">
    @csrf

    <div class="row g-4">
        <!-- Single Full-Width Column -->
        <div class="col-12">

            <!-- Card 1: Informasi Utama Produk -->
            <div class="card-box mb-4">
                <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                    Informasi Produk
                </h6>

                <!-- Kode Barang — Readonly dari backend -->
                <div class="mb-3">
                    <label for="prod_code" class="form-label small fw-semibold text-dark">Kode Barang</label>
                    <input type="text" class="form-control font-monospace bg-light rounded-3 text-secondary" id="prod_code" name="prod_code" value="{{ old('prod_code', $generatedCode ?? '') }}" readonly>
                </div>

                <!-- Nama Produk -->
                <div class="mb-3">
                    <label for="prod_name" class="form-label small fw-semibold text-dark">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3 @error('prod_name') is-invalid @enderror" id="prod_name" name="prod_name" value="{{ old('prod_name') }}" required>
                    @error('prod_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Harga Bahan Utama (Dipindahkan ke Informasi Produk) -->
                <div class="mb-3">
                    <label for="unit_price" class="form-label small fw-semibold text-dark">Harga Bahan Utama (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rp</span>
                        <input type="number" class="form-control font-monospace fw-semibold @error('unit_price') is-invalid @enderror" id="unit_price" name="unit_price" value="{{ old('unit_price') }}" min="0" required>
                    </div>
                    @error('unit_price')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Deskripsi Produk -->
                <div class="mb-3">
                    <label for="description" class="form-label small fw-semibold text-dark">Deskripsi Produk</label>
                    <textarea class="form-control rounded-3" id="description" name="description" rows="5">{{ old('description') }}</textarea>
                </div>

                <!-- Gambar / Foto Produk -->
                <div class="mb-0">
                    <label class="form-label small fw-semibold text-dark">Foto Produk</label>
                    <div class="upload-dropzone" onclick="document.getElementById('thumbnail').click()">
                        <i class="bi bi-cloud-arrow-up fs-2 text-muted mb-1"></i>
                        <div class="fw-semibold text-dark small">Klik untuk mengunggah foto produk</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">Format PNG, JPG, WEBP maksimal 2MB</div>
                        <input type="file" class="d-none" id="thumbnail" name="thumbnail" accept="image/*" onchange="previewThumbnail(this)">
                    </div>
                    <div id="thumbnailPreviewContainer" class="mt-2 d-none d-flex align-items-center gap-2">
                        <img id="thumbnailPreview" src="#" alt="Preview" class="product-thumb">
                        <span class="small text-muted" id="thumbnailFileName"></span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Manajemen Stok & Inventori -->
            <div class="card-box mb-4">
                <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                    Manajemen Stok & Inventori
                </h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="initial_stock" class="form-label small fw-semibold text-dark">Stok Awal</label>
                        <input type="number" class="form-control rounded-3" id="initial_stock" name="initial_stock" value="{{ old('initial_stock') }}" min="0" oninput="document.getElementById('current_stock').value = this.value">
                    </div>
                    <div class="col-md-4">
                        <label for="current_stock" class="form-label small fw-semibold text-dark">Stok Saat Ini</label>
                        <input type="number" class="form-control font-monospace bg-light rounded-3 text-secondary" id="current_stock" value="{{ old('initial_stock') }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="min_stock" class="form-label small fw-semibold text-dark">Minimum Stok <span class="text-danger">*</span></label>
                        <input type="number" class="form-control rounded-3 @error('min_stock') is-invalid @enderror" id="min_stock" name="min_stock" value="{{ old('min_stock', 5) }}" min="0" required>
                        @error('min_stock')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Card 3: Multiple Kalkulasi HPP & Rincian Komponen -->
            <div class="card-box mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <div>
                        <h6 class="card-box-title mb-0 d-flex align-items-center gap-2">
                            Kalkulasi HPP & Rincian Komponen
                        </h6>
                        <span class="text-muted small">Kelola harga jual dan rincian komponen HPP untuk setiap satuan jual produk.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-2 px-3 d-inline-flex align-items-center gap-1" id="btnAddSellingConfig" onclick="addSellingConfigCard()">
                        <i class="bi bi-plus-lg"></i> Tambah Satuan Jual
                    </button>
                </div>

                <!-- Toggle Produk Fleksibel -->
                <div class="alert alert-warning rounded-3 py-2 px-3 mb-3 d-flex align-items-start gap-2" id="flexibleProductAlert" style="display:none!important">
                    <i class="bi bi-exclamation-triangle-fill text-warning mt-1"></i>
                    <div class="small">
                        <strong>Mode Fleksibel Aktif.</strong> Harga jual, HPP/unit, dan harga beli di bawah hanya sebagai referensi. Total HPP dan Total Penjualan akan diisi manual saat pencatatan penjualan.
                    </div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" 
                        id="is_flexible_product" name="is_flexible_product" value="1"
                        onchange="onFlexibleProductToggle(this)"
                        {{ old('is_flexible_product') ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold text-dark" for="is_flexible_product">
                        Produk Fleksibel 
                        <span class="text-muted fw-normal">(harga & modal berubah tiap hari — HPP/harga jual diisi manual saat penjualan)</span>
                    </label>
                </div>

                <!-- Container Card Repeater Satuan Jual -->
                <div id="sellingConfigsContainer" class="d-flex flex-column gap-3 mb-4">
                    <!-- Cards will be populated by JS or old input loop -->
                </div>

                <!-- Submit Action -->
                <div class="d-flex align-items-center justify-content-between mt-4 pt-3">
                    <a href="{{ route('products.index') }}" class="btn btn-light border rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-success text-white fw-bold px-5 rounded-3 d-inline-flex align-items-center gap-2">
                        Simpan Produk
                    </button>
                </div>
            </div>

        </div>
    </div>
</form>

@endsection

@push('scripts')
<!-- jQuery & Select2 JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    window.hppMasterList = @json($hppList);
    window.unitsList = @json($unitsList);
    window.oldSellingConfigs = @json($oldConfigs);
</script>
<script src="{{ asset('js/products.js') }}"></script>
@endpush