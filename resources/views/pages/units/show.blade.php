@extends('layouts.admin')

@section('title', 'Rincian Master Satuan — Warung Pojok Oremus')
@section('page-title', 'Rincian Master Satuan')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('units.index') }}">Master Satuan Produk</a></li>
        <li class="breadcrumb-item active" aria-current="page">Rincian Satuan</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $unitObj = $unit;
    $connectedProducts = $unitObj->products ?? collect();
@endphp

<!-- Header Action Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">{{ $unitObj->unit_name }}</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('units.index') }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3 d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('units.edit', $unitObj->id) }}" class="btn btn-sm btn-success text-white rounded-3 px-3 d-inline-flex align-items-center gap-1">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Overview Card -->
    <div class="col-lg-4">
        <div class="card-box text-center">

            <h5 class="fw-bold text-dark mb-1">{{ $unitObj->unit_name }}</h5>
            <div class="mb-3">
                {{ $unitObj->short_name }}
            </div>

            <div class="calc-preview-card mb-3 text-center">
                <div class="text-muted small" style="font-size: 0.75rem;">Kategori / Tipe Klasifikasi</div>
                <div class="h5 fw-bold text-dark mb-0 mt-1">
                    {{ $unitObj->type }}
                </div>
            </div>

            <div class="p-3 bg-light rounded-3 text-start small text-muted">
                <div class="d-flex justify-content-between mb-2">
                    <span>Terhubung ke Produk:</span>
                    <strong class="text-dark">{{ $connectedProducts->count() }} Produk</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Dibuat pada:</span>
                    <strong class="text-dark">{{ $unitObj->created_at ? $unitObj->created_at->format('d M Y, H:i') : '-' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Connected Products Table -->
    <div class="col-lg-8">
        <div class="card-box">
            <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam text-success"></i> Produk yang Menggunakan Satuan Ini
                </span>
                <span class="badge badge-soft-success rounded-pill px-2.5 py-1">{{ $connectedProducts->count() }} Produk</span>
            </h6>

            <div class="table-responsive">
                <table class="table table-products align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th>SKU</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-end">HPP Total</th>
                            <th class="text-center">Stok</th>
                            <th class="text-end no-sort">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($connectedProducts as $product)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $product->prod_name }}</div>
                                </td>
                                <td>
                                    <span class="font-monospace text-secondary small">{{ $product->sku }}</span>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace text-secondary">
                                    Rp {{ number_format($product->current_hpp, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-soft-success px-2.5 py-1 rounded-pill">
                                        {{ $product->current_stock }} {{ $unitObj->short_name }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('products.show', $product->id) }}" class="action-btn action-btn-primary" title="Lihat Produk">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    <i class="bi bi-inbox fs-4 d-block mb-1 text-secondary"></i>
                                    Belum ada produk yang menggunakan satuan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

