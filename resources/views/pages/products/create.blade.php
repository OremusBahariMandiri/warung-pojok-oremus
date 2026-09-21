@extends('layouts.admin')

@section('title', 'Tambah Produk Baru — Warung Pojok Oremus')

@push('styles')
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
        <h4 class="fw-bold text-dark mb-1">Tambah Produk Baru & HPP</h4>
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
        <!-- Left Column: Product Information & Inventory -->
        <div class="col-lg-7">
            <!-- Card 1: Informasi Utama -->
            <div class="card-box">
                <div class="mb-3">
                    <label for="prod_name" class="form-label small fw-semibold text-dark">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3" id="prod_name" name="prod_name" value="{{ old('prod_name') }}" required autofocus>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <label for="sku" class="form-label small fw-semibold text-dark">Kode SKU</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace rounded-start-3" id="sku" name="sku" value="{{ old('sku') }}">
                            <button class="btn btn-outline-secondary rounded-end-3" type="button" onclick="generateRandomSKU()" title="Generate Random SKU">
                                <i class="bi bi-magic me-1"></i> Auto SKU
                            </button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label for="unit_id" class="form-label small fw-semibold text-dark">Satuan Jual <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3 @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required>
                            <option value="" disabled {{ old('unit_id') ? '' : 'selected' }}>Pilih Satuan...</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->unit_name }} ({{ $unit->short_name }})
                                </option>
                            @endforeach
                        </select>
                        @error('unit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label small fw-semibold text-dark">Deskripsi Produk</label>
                    <textarea class="form-control rounded-3" id="description" name="description" rows="3" >{{ old('description') }}</textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark">Gambar / Foto Produk</label>
                    <div class="upload-dropzone" onclick="document.getElementById('thumbnail').click()">
                        <i class="bi bi-cloud-arrow-up fs-2 text-muted mb-1"></i>
                        <div class="fw-semibold text-dark small">Klik untuk mengunggah foto produk</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">Format PNG, JPG, WEBP maksimal 2MB</div>
                        <input type="file" class="d-none" id="thumbnail" name="thumbnail" accept="image/*" onchange="previewThumbnail(this)">
                    </div>
                    <div id="thumbnailPreviewContainer" class="mt-2 d-none d-flex align-items-center gap-2">
                        <img id="thumbnailPreview" src="#" alt="Preview" class="product-thumb">
                        <span class="small text-muted" id="thumbnailFileName">file.jpg</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Manajemen Stok & Inventori -->
            <div class="card-box">
                <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                    <i class="bi bi-boxes text-primary"></i> Manajemen Stok & Inventori
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="current_stock" class="form-label small fw-semibold text-dark">Stok Produk</label>
                        <input type="number" class="form-control rounded-3" id="current_stock" name="current_stock" value="{{ old('current_stock') }}" min="0">
                    </div>
                    <div class="col-md-6">
                        <label for="min_stock" class="form-label small fw-semibold text-dark">Minimal Stok Produk <span class="text-danger">*</span></label>
                        <input type="number" class="form-control rounded-3" id="min_stock" name="min_stock" value="{{ old('min_stock') }}" min="0" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: HPP & Component Breakdown -->
        <div class="col-lg-5">
            <div class="card-box">
                <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                    <i class="bi bi-calculator text-success"></i> Kalkulasi HPP & Rincian Komponen
                </h6>

                <div class="mb-3">
                    <label for="hpp_method" class="form-label small fw-semibold text-dark">Metode Perhitungan HPP <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3" id="hpp_method" name="hpp_method" onchange="toggleHppSection()" required>
                        <option value="calculated" {{ old('hpp_method', 'calculated') == 'calculated' ? 'selected' : '' }}>Otomatis (Bahan Utama + Komponen HPP)</option>
                        <option value="manual" {{ old('hpp_method') == 'manual' ? 'selected' : '' }}>Manual</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="unit_price" class="form-label small fw-semibold text-dark">Harga Bahan Utama (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rp</span>
                        <input type="number" class="form-control font-monospace fw-semibold text-end" id="unit_price" name="unit_price" value="{{ old('unit_price') }}"  min="0" oninput="calculateTotalHpp()" required>
                    </div>
                </div>

                <!-- Section: Dynamic HPP Components Repeater (product_hpp) -->
                <div id="calculatedHppSection" class="mb-3 {{ old('hpp_method', 'calculated') == 'manual' ? 'd-none' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label small fw-semibold text-dark mb-0">Komposisi HPP Tambahan (`product_hpp`)</label>
                        <button type="button" class="btn btn-xs btn-outline-success rounded-2 py-1 px-2 text-xs" onclick="addHppComponentRow()">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Komponen
                        </button>
                    </div>

                    <div class="table-responsive mb-2">
                        <table class="table table-sm table-borderless align-middle mb-0" id="hppComponentsTable">
                            <thead class="table-light rounded-2">
                                <tr style="font-size: 0.75rem;">
                                    <th>Komponen HPP</th>
                                    <th class="text-end" style="width: 120px;">Biaya (Rp)</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="hppComponentRows">
                                @if (!empty($oldComponents) && is_array($oldComponents))
                                    @foreach ($oldComponents as $idx => $comp)
                                        <tr class="component-row">
                                            <td>
                                                <select name="components[{{ $idx }}][hpp_id]" class="form-select form-select-sm rounded-2 component-select" onchange="onComponentSelectChange(this)">
                                                    <option value="">Pilih Komponen</option>
                                                    @foreach ($hppList as $hpp)
                                                        <option value="{{ $hpp->id }}" data-cost="{{ $hpp->unit_cost }}" {{ ($comp['hpp_id'] ?? '') == $hpp->id ? 'selected' : '' }}>
                                                            {{ $hpp->name }} (Rp {{ number_format($hpp->unit_cost, 0, ',', '.') }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="components[{{ $idx }}][cost]" class="form-control form-control-sm font-monospace text-end rounded-2 component-cost" value="{{ $comp['cost'] ?? 0 }}" min="0" oninput="calculateTotalHpp()">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="removeHppComponentRow(this)" title="Hapus"><i class="bi bi-x-circle-fill"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                
                                @else
                                    <tr class="component-row">
                                        <td colspan="3" class="text-center text-muted small py-2">
                                            Belum ada data komponen HPP master di database.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Manual Fixed HPP Input (hidden when calculated) -->
                <div id="manualHppSection" class="mb-3 {{ old('hpp_method') == 'manual' ? '' : 'd-none' }}">
                    <label for="current_hpp" class="form-label small fw-semibold text-dark">HPP Fixed Manual (Rp)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rp</span>
                        <input type="number" class="form-control font-monospace text-end" id="current_hpp" name="current_hpp" value="{{ old('current_hpp', 0) }}"  min="0" oninput="calculateTotalHpp()">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="selling_price" class="form-label small fw-semibold text-dark">Harga Jual Konsumen (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rp</span>
                        <input type="number" class="form-control font-monospace fw-bold text-end text-dark" id="selling_price" name="selling_price" value="{{ old('selling_price') }}"  min="0" oninput="calculateTotalHpp()" required>
                    </div>
                </div>

                <!-- Live Margin & Profit Display Card -->
                <div class="calc-preview-card text-center">
                    <div class="row g-2 align-items-center">
                        <div class="col-6 border-end">
                            <div class="text-muted small" style="font-size: 0.75rem;">Total HPP Produk</div>
                            <div class="h5 fw-bold font-monospace text-dark mb-0" id="displayTotalHpp">Rp 0</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small" style="font-size: 0.75rem;">Estimasi Margin Bersih</div>
                            <div class="h5 fw-bold text-success mb-0" id="displayMarginPercent">0.0%</div>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top text-muted small" style="font-size: 0.78rem;">
                        Estimasi Laba per Satuan: <span class="fw-bold text-dark font-monospace" id="displayProfitAmount">Rp 0</span>
                    </div>
                </div>

                <!-- Submit Action Card -->
                <div class="d-flex align-items-center justify-content-between mt-5">
                    <a href="{{ route('products.index') }}" class="btn btn-light border rounded-3 px-3">Batal</a>
                    <button type="submit" class="btn btn-success text-white fw-bold px-4 rounded-3 d-inline-flex align-items-center gap-2">
                      Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
    const hppMasterList = @json($hppList);
    let componentRowIndex = document.querySelectorAll('.component-row').length + 10;

    function generateRandomSKU() {
        const rand = Math.floor(1000 + Math.random() * 9000);
        document.getElementById('sku').value = 'PRD-WARJOK-' + rand;
    }

    function toggleHppSection() {
        const method = document.getElementById('hpp_method').value;
        const calcSection = document.getElementById('calculatedHppSection');
        const manualSection = document.getElementById('manualHppSection');

        if (method === 'manual') {
            calcSection.classList.add('d-none');
            manualSection.classList.remove('d-none');
        } else {
            calcSection.classList.remove('d-none');
            manualSection.classList.add('d-none');
        }
        calculateTotalHpp();
    }

    function onComponentSelectChange(selectEl) {
        const selectedOption = selectEl.options[selectEl.selectedIndex];
        const defaultCost = selectedOption.getAttribute('data-cost');
        const row = selectEl.closest('tr');
        if (row && defaultCost !== null) {
            const costInput = row.querySelector('.component-cost');
            if (costInput) {
                costInput.value = Math.round(parseFloat(defaultCost) || 0);
            }
        }
        calculateTotalHpp();
    }

    function addHppComponentRow() {
        const tbody = document.getElementById('hppComponentRows');
        const tr = document.createElement('tr');
        tr.className = 'component-row';

        let optionsHtml = '<option value="">Pilih Komponen</option>';
        if (hppMasterList && hppMasterList.length > 0) {
            hppMasterList.forEach(hpp => {
                optionsHtml += `<option value="${hpp.id}" data-cost="${hpp.unit_cost}">${hpp.name} (Rp ${Number(hpp.unit_cost).toLocaleString('id-ID')})</option>`;
            });
        }

        tr.innerHTML = `
            <td>
                <select name="components[${componentRowIndex}][hpp_id]" class="form-select form-select-sm rounded-2 component-select" onchange="onComponentSelectChange(this)">
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="number" name="components[${componentRowIndex}][cost]" class="form-control form-control-sm font-monospace text-end rounded-2 component-cost" value="0" placeholder="0" min="0" oninput="calculateTotalHpp()">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="removeHppComponentRow(this)" title="Hapus"><i class="bi bi-x-circle-fill"></i></button>
            </td>
        `;

        tbody.appendChild(tr);
        componentRowIndex++;
        calculateTotalHpp();
    }

    function removeHppComponentRow(btn) {
        const row = btn.closest('tr');
        if (row) row.remove();
        calculateTotalHpp();
    }

    function calculateTotalHpp() {
        const method = document.getElementById('hpp_method').value;
        const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
        const sellingPrice = parseFloat(document.getElementById('selling_price').value) || 0;
        
        let totalHpp = 0;

        if (method === 'calculated') {
            let componentsSum = 0;
            const costInputs = document.querySelectorAll('.component-cost');
            costInputs.forEach(input => {
                componentsSum += parseFloat(input.value) || 0;
            });
            totalHpp = unitPrice + componentsSum;
        } else {
            const manualHppInput = document.getElementById('current_hpp');
            totalHpp = manualHppInput ? (parseFloat(manualHppInput.value) || unitPrice) : unitPrice;
        }

        const profit = sellingPrice - totalHpp;
        const marginPercent = sellingPrice > 0 ? ((profit / sellingPrice) * 100).toFixed(1) : 0.0;

        document.getElementById('displayTotalHpp').textContent = 'Rp ' + Math.round(totalHpp).toLocaleString('id-ID');
        document.getElementById('displayMarginPercent').textContent = marginPercent + '%';
        document.getElementById('displayProfitAmount').textContent = 'Rp ' + Math.round(profit).toLocaleString('id-ID');

        // Color coding margin
        const marginEl = document.getElementById('displayMarginPercent');
        if (marginPercent >= 40) {
            marginEl.className = 'h5 fw-bold text-success mb-0';
        } else if (marginPercent >= 20) {
            marginEl.className = 'h5 fw-bold text-warning mb-0';
        } else {
            marginEl.className = 'h5 fw-bold text-danger mb-0';
        }
    }

    function previewThumbnail(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('thumbnailPreview').src = e.target.result;
                document.getElementById('thumbnailFileName').textContent = input.files[0].name;
                document.getElementById('thumbnailPreviewContainer').classList.remove('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        calculateTotalHpp();
    });
</script>
@endpush
