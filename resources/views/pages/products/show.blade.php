@extends('layouts.admin')

@section('title', 'Rincian Produk — Warung Pojok Oremus')
@section('page-title', 'Rincian Produk')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Manajemen Produk</a></li>
        <li class="breadcrumb-item active" aria-current="page">Rincian Produk</li>
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
@endphp

<!-- Header Action Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">{{ $productObj->prod_name }}</h4>
        <span class="badge badge-soft-secondary font-monospace">SKU: {{ $productObj->sku ?? 'PRD-' . $productObj->id }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('products.edit', $productObj->id) }}" class="btn btn-sm btn-success text-white rounded-3 px-3">
            <i class="bi bi-pencil me-1"></i> Edit Produk
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Overview & Product Card -->
    <div class="col-lg-5">
        <div class="card-box text-center">
            @if ($productObj->thumbnail && !in_array($productObj->thumbnail, ['thumbnail/default.png', 'products/default.png']))
                <img src="{{ asset('storage/' . $productObj->thumbnail) }}" alt="{{ $productObj->prod_name }}" class="product-thumb mx-auto mb-3" style="width: 96px; height: 96px; object-fit: cover;">
            @else
                <div class="product-thumb-placeholder mx-auto mb-3" style="width: 96px; height: 96px; font-size: 2.5rem; background: var(--green-50); color: var(--green-600);">
                    <i class="bi bi-box-seam"></i>
                </div>
            @endif

            <h5 class="fw-bold text-dark mb-1">{{ $productObj->prod_name }}</h5>
            <div class="mb-3">
                <span class="badge badge-soft-info px-2 py-1">{{ strtoupper($productObj->satuan ?? 'PORSI') }}</span>
                @if ($productObj->hpp_method === 'calculated')
                    <span class="badge badge-soft-success px-2 py-1">Hitung Komposisi HPP</span>
                @else
                    <span class="badge badge-soft-secondary px-2 py-1">Manual Fixed</span>
                @endif
            </div>

            <div class="calc-preview-card mb-3">
                <div class="row text-center g-2">
                    <div class="col-4 border-end">
                        <div class="text-muted small" style="font-size: 0.72rem;">Harga Jual</div>
                        <div class="fw-bold text-dark fs-6">Rp {{ number_format($sellingVal, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-4 border-end">
                        <div class="text-muted small" style="font-size: 0.72rem;">Total HPP</div>
                        <div class="fw-bold text-secondary fs-6 font-monospace">Rp {{ number_format($currentHppVal, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small" style="font-size: 0.72rem;">Margin</div>
                        <div class="fw-bold text-success fs-6">{{ $marginPercent }}%</div>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-light rounded-3 text-start mb-3">
                <div class="small fw-bold text-dark mb-1">Catatan & Deskripsi:</div>
                <div class="small text-muted">{{ $productObj->description ?: 'Tidak ada catatan atau deskripsi tambahan.' }}</div>
            </div>
        </div>
    </div>

    <!-- Right Column: HPP Breakdown & Inventory Details -->
    <div class="col-lg-7">
        <!-- Card 1: Rincian Komposisi HPP -->
        <div class="card-box mb-4">
            <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-pie-chart text-success"></i> Rincian Komposisi HPP (`product_hpp`)
            </h6>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.78rem;">
                            <th>Komponen</th>
                            <th class="text-end">Biaya Modal (Rp)</th>
                            <th class="text-end">Kontribusi %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">Bahan Utama</div>
                                <div class="small text-muted">Modal pokok dasar</div>
                            </td>
                            <td class="text-end font-monospace">Rp {{ number_format($unitVal, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace">{{ $currentHppVal > 0 ? round(($unitVal / $currentHppVal) * 100, 1) : 0 }}%</td>
                        </tr>
                        @if ($existingComponents->count() > 0)
                            @foreach ($existingComponents as $comp)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $comp->hpp->name ?? 'Komponen #' . $comp->hpp_id }}</div>
                                    </td>
                                    <td class="text-end font-monospace">Rp {{ number_format($comp->cost, 0, ',', '.') }}</td>
                                    <td class="text-end font-monospace">{{ $currentHppVal > 0 ? round(($comp->cost / $currentHppVal) * 100, 1) : 0 }}%</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="text-center text-muted small py-3">
                                    <em>Tidak ada komponen HPP tambahan (Metode manual / modal bahan saja).</em>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>Total HPP Modal Pokok</td>
                            <td class="text-end font-monospace text-success">Rp {{ number_format($currentHppVal, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace">100.0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Card 2: Status Inventori -->
        <div class="card-box">
            <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-boxes text-primary"></i> Status Inventori & Batas Minimal
            </h6>

            <div class="row g-3 text-center">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="small text-muted">Stok Tersedia</div>
                        <div class="h4 fw-bold text-dark mb-0">{{ $productObj->current_stock }} {{ $productObj->satuan }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="small text-muted">Batas Minimal Alert</div>
                        <div class="h4 fw-bold {{ (int)$productObj->current_stock <= (int)$productObj->min_stock ? 'text-danger' : 'text-muted' }} mb-0">
                            {{ $productObj->min_stock }} {{ $productObj->satuan }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
