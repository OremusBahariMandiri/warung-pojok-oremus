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
    $oldComponents = old('components', []);
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

                <!-- Deskripsi Produk -->
                <div class="mb-3">
                    <label for="description" class="form-label small fw-semibold text-dark">Deskripsi Produk</label>
                    <textarea class="form-control rounded-3" id="description" name="description" rows="3">{{ old('description') }}</textarea>
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
                    <div class="col-md-6">
                        <label for="initial_stock" class="form-label small fw-semibold text-dark">Stok Awal</label>
                        <input type="number" class="form-control rounded-3" id="initial_stock" name="initial_stock" value="{{ old('initial_stock') }}" min="0">
                    </div>
                    <div class="col-md-6">
                        <label for="min_stock" class="form-label small fw-semibold text-dark">Minimum Stok <span class="text-danger">*</span></label>
                        <input type="number" class="form-control rounded-3 @error('min_stock') is-invalid @enderror" id="min_stock" name="min_stock" value="{{ old('min_stock') }}" min="0" required>
                        @error('min_stock')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Card 3: Kalkulasi HPP & Rincian Komponen -->
            <div class="card-box mb-4">
                <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                    Kalkulasi HPP & Rincian Komponen
                </h6>

                <!-- Satuan Jual (Select2 Searchable) -->
                <div class="mb-3">
                    <label for="unit_id" class="form-label small fw-semibold text-dark">Satuan Jual <span class="text-danger">*</span></label>
                    <select class="form-select select2-unit rounded-3 @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required>
                        <option value="" disabled {{ old('unit_id') ? '' : 'selected' }}>Pilih Satuan...</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" data-type="{{ $unit->type ?? '' }}" data-name="{{ strtolower($unit->unit_name) }}" data-short="{{ strtolower($unit->short_name) }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->unit_name }} ({{ $unit->short_name }})
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Opsi Metode HPP (Muncul saat satuan olahan/Gelas/Pcs) -->
                <div id="hppMethodSection" class="mb-3 d-none">
                    <label for="hpp_method" class="form-label small fw-semibold text-dark">Metode Perhitungan HPP <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3" id="hpp_method" name="hpp_method" onchange="handleHppMethodChange()">
                        <option value="CALCULATED" {{ old('hpp_method', 'CALCULATED') == 'CALCULATED' ? 'selected' : '' }}>Otomatis (Calculated / By System)</option>
                        <option value="MANUAL" {{ old('hpp_method') == 'MANUAL' ? 'selected' : '' }}>Manual (Fixed Cost)</option>
                    </select>
                </div>

                <!-- Section: Dynamic HPP Components Repeater (Muncul saat Satuan Olahan & Metode CALCULATED) -->
                <div id="calculatedHppSection" class="mb-3 d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label small fw-semibold text-dark mb-0">Komposisi HPP Tambahan</label>
                        <button type="button" class="btn btn-xs btn-outline-success rounded-2 py-1 px-2 text-xs d-inline-flex align-items-center gap-1" onclick="addHppComponentRow()">
                            <i class="bi bi-plus-lg"></i> Tambah Komponen
                        </button>
                    </div>

                    <div class="table-responsive mb-2">
                        <table class="table table-sm table-borderless align-middle mb-0" id="hppComponentsTable">
                            <thead class="table-light rounded-2">
                                <tr style="font-size: 0.75rem;">
                                    <th>Komponen HPP</th>
                                    <th class="text-end" style="width: 160px;">Biaya (Rp)</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="hppComponentRows">
                                @if (!empty($oldComponents) && is_array($oldComponents))
                                    @foreach ($oldComponents as $idx => $comp)
                                        <tr class="component-row">
                                            <td>
                                                <select name="components[{{ $idx }}][hpp_id]" class="form-select form-select-sm select2-hpp rounded-2 component-select" onchange="onComponentSelectChange(this)">
                                                    <option value="">Pilih Komponen</option>
                                                    @foreach ($hppList as $hpp)
                                                        <option value="{{ $hpp->id }}" data-cost="{{ $hpp->unit_cost }}" {{ ($comp['hpp_id'] ?? '') == $hpp->id ? 'selected' : '' }}>
                                                            {{ $hpp->name }} (Rp {{ number_format($hpp->unit_cost, 0, ',', '.') }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="components[{{ $idx }}][cost]" class="form-control form-control-sm font-monospace text-end rounded-2 component-cost bg-light" value="{{ $comp['cost'] ?? 0 }}" min="0" readonly>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="removeHppComponentRow(this)" title="Hapus"><i class="bi bi-x-circle-fill"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Total Biaya HPP Komponen Info -->
                    <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded-2 mb-3">
                        <span class="small text-muted">Total Biaya HPP Komponen:</span>
                        <span class="fw-bold font-monospace text-dark" id="displayComponentHpp">Rp 0</span>
                    </div>
                </div>

                <!-- Harga Bahan Utama (Muncul untuk semua jenis atau saat diinput) -->
                <div class="mb-3" id="unitPriceSection">
                    <label for="unit_price" class="form-label small fw-semibold text-dark">Harga Bahan Utama <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rp</span>
                        <input type="number" class="form-control font-monospace fw-semibold text-end" id="unit_price" name="unit_price" value="{{ old('unit_price') }}" min="0" oninput="calculateTotalHpp()" required>
                    </div>
                </div>

                <!-- Input HPP Fixed (Muncul saat Metode Perhitungan HPP = MANUAL) -->
                <div class="mb-3 d-none" id="manualHppSection">
                    <label for="current_hpp" class="form-label small fw-semibold text-dark">HPP Fixed <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rp</span>
                        <input type="number" class="form-control font-monospace fw-semibold text-end" id="current_hpp" name="current_hpp" value="{{ old('current_hpp') }}" min="0" oninput="calculateTotalHpp()">
                    </div>
                </div>

                <!-- Harga Jual Konsumen -->
                <div class="mb-3">
                    <label for="selling_price" class="form-label small fw-semibold text-dark">Harga Jual Konsumen<span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rp</span>
                        <input type="number" class="form-control font-monospace fw-bold text-end text-dark" id="selling_price" name="selling_price" value="{{ old('selling_price') }}" min="0" oninput="calculateTotalHpp()" required>
                    </div>
                </div>

                <!-- Live Margin & Profit Display Card -->
                <div class="calc-preview-card text-center mb-4">
                    <div class="row g-2 align-items-center">
                        <div class="col-6 border-end">
                            <div class="text-muted small" style="font-size: 0.75rem;">Total HPP Produk</div>
                            <div class="h5 fw-bold font-monospace text-dark mb-0" id="displayTotalHpp">Rp 0</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small" style="font-size: 0.75rem;">Estimasi Margin Bersih</div>
                            <div class="h5 fw-bold text-danger mb-0" id="displayMarginPercent">0%</div>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top text-muted small" style="font-size: 0.78rem;">
                        Estimasi Laba per Satuan: <span class="fw-bold text-dark font-monospace" id="displayProfitAmount">Rp 0</span>
                    </div>
                </div>

                <!-- Submit Action -->
                <div class="d-flex align-items-center justify-content-between mt-4">
                    <a href="{{ route('products.index') }}" class="btn btn-light border rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-success text-white fw-bold px-5 rounded-3 d-inline-flex align-items-center gap-2">
                        Simpan
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
</script>
<script src="{{ asset('js/products.js') }}"></script>
@endpush