@extends('layouts.admin')

@section('title', 'Detail Produk — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/products.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}" class="text-decoration-none text-muted">management produk</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">detail produk</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $productObj = $product;
    $sellingVal = (float)($productObj->selling_price ?? 0);
    $unitVal = (float)($productObj->unit_price ?? 0);
    $currentHppVal = (float)($productObj->current_hpp ?? $unitVal);
    $existingComponents = $productObj->productHpps ?? collect();
    $profit = $sellingVal - $currentHppVal;
    $marginPercent = $sellingVal > 0 ? round(($profit / $sellingVal) * 100, 1) : 0;
    $unitShort = $productObj->unit->short_name ?? $productObj->unit->unit_name ?? 'PORSI';
@endphp

<!-- Header Title di atas Card -->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Detail Produk & Komposisi HPP</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('products.edit', $productObj->id) }}" class="btn btn-sm btn-warning text-white rounded-2 px-3 d-inline-flex align-items-center gap-2" style="background-color: #f59e0b; border-color: #f59e0b;">
            <i class="bi bi-pencil"></i> Edit
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Product Information Card -->
    <div class="col-lg-5">
        <div class="card-box bg-white border rounded-3 p-4 mb-4 position-relative">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Informasi Produk</h6>
            <div class="d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="text-muted small">Nama Produk</div>
                    <div class="fw-semibold text-dark fs-6">{{ $productObj->prod_name }}</div>
                </div>

                <!-- Thumbnail Produk -->
                <div class="product-thumbnail-wrapper shrink-0">
                    <div class="text-muted small mb-1 product-thumbnail-label">Gambar Produk</div>
                    @if ($productObj->thumbnail)
                        <img src="{{ asset('storage/' . $productObj->thumbnail) }}" 
                            alt="{{ $productObj->prod_name }}" 
                            class="product-thumbnail-img">
                    @else
                        <div class="product-thumbnail-placeholder">
                            <i class="bi bi-image fs-5 text-secondary"></i>
                        </div>
                    @endif
                </div>
            </div>
                <div>
                    <div class="text-muted small">Kode SKU</div>
                    <div class="fw-semibold text-dark font-monospace">{{ $productObj->sku ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Satuan Jual</div>
                    <div class="fw-semibold text-dark">{{ strtoupper($unitShort) }}</div>
                </div>
                <div>
                    <div class="text-muted small">Metode Perhitungan HPP</div>
                    <div class="fw-semibold text-dark">{{ $productObj->hpp_method === 'calculated' ? 'Otomatis (Komposisi)' : 'Manual Fixed' }}</div>
                </div>
                <div class="row g-2 pt-2 border-top">
                    <div class="col-6">
                        <div class="text-muted small">Harga Jual</div>
                        <div class="fw-bold text-dark fs-6">Rp {{ number_format($sellingVal, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Total HPP</div>
                        <div class="fw-bold text-dark fs-6 font-monospace">Rp {{ number_format($currentHppVal, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="text-muted small">Estimasi Laba per Satuan</div>
                        <div class="fw-semibold text-dark">Rp {{ number_format($profit, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Margin Laba</div>
                        <div class="fw-bold {{ $marginPercent >= 30 ? 'text-success' : ($marginPercent >= 15 ? 'text-warning' : 'text-danger') }}">{{ $marginPercent }}%</div>
                    </div>
                </div>
                <div class="row g-2 pt-2 border-top">
                    <div class="col-6">
                        <div class="text-muted small">Stok Tersedia</div>
                        <div class="fw-semibold {{ (int)$productObj->current_stock <= (int)$productObj->min_stock ? 'text-danger fw-bold' : 'text-dark' }}">
                            {{ $productObj->current_stock }} {{ $unitShort }}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Batas Minimal Stok</div>
                        <div class="text-dark">{{ $productObj->min_stock }} {{ $unitShort }}</div>
                    </div>
                </div>
                <div>
                    <div class="text-muted small">Deskripsi Produk</div>
                    <div class="text-dark small">{{ $productObj->description ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Tanggal Dibuat</div>
                    <div class="text-dark small">{{ $productObj->created_at ? $productObj->created_at->format('d M Y, H:i') : '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: HPP Breakdown Table Card -->
    <div class="col-lg-7">
        <div class="card-box p-0 bg-white border rounded-3 overflow-hidden shadow-sm mb-4">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0">Komposisi HPP Produk</h6>
                <span class="text-muted small">{{ $existingComponents->count() }} Komponen Tambahan</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Komponen HPP</th>
                            <th class="text-center" style="width: 120px;">Satuan</th>
                            <th class="text-end" style="width: 160px;">Biaya (Rp)</th>
                            <th class="text-end" style="width: 120px;">Kontribusi %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center text-muted fw-medium">1</td>
                            <td>
                                <div class="fw-semibold text-dark">Bahan Utama</div>
                                <div class="small text-muted">Modal pokok dasar</div>
                            </td>
                            <td class="text-center">{{ strtoupper($unitShort) }}</td>
                            <td class="text-end font-monospace text-dark">Rp {{ number_format($unitVal, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace text-dark">{{ $currentHppVal > 0 ? round(($unitVal / $currentHppVal) * 100, 1) : 0 }}%</td>
                        </tr>
                        @if ($existingComponents->count() > 0)
                            @foreach ($existingComponents as $comp)
                                <tr>
                                    <td class="text-center text-muted fw-medium">{{ $loop->iteration + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $comp->hpp->name ?? 'Komponen #' . $comp->hpp_id }}</div>
                                    </td>
                                    <td class="text-center">{{ strtoupper($comp->hpp->unit ?? 'PCS') }}</td>
                                    <td class="text-end font-monospace text-dark">Rp {{ number_format($comp->cost, 0, ',', '.') }}</td>
                                    <td class="text-end font-monospace text-dark">{{ $currentHppVal > 0 ? round(($comp->cost / $currentHppVal) * 100, 1) : 0 }}%</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end">Total HPP Modal Pokok:</td>
                            <td class="text-end font-monospace text-dark">Rp {{ number_format($currentHppVal, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace text-dark">100.0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
