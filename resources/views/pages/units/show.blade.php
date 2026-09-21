@extends('layouts.admin')

@section('title', 'Detail Master Satuan — Warung Pojok Oremus')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('units.index') }}" class="text-decoration-none text-muted">management satuan produk</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">detail satuan</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $unitObj = $unit;
    $connectedProducts = $unitObj->products ?? collect();
@endphp

<!-- Header Title di atas Card -->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Detail Master Satuan</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('units.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('units.edit', $unitObj->id) }}" class="btn btn-sm btn-warning text-white rounded-2 px-3 d-inline-flex align-items-center gap-2" style="background-color: #f59e0b; border-color: #f59e0b;">
            <i class="bi bi-pencil"></i> Edit
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Unit Details Card -->
    <div class="col-lg-4">
        <div class="card-box bg-white border rounded-3 p-4 mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Informasi Satuan</h6>
            <div class="d-flex flex-column gap-3">
                <div>
                    <div class="text-muted small">Nama Satuan Lengkap</div>
                    <div class="fw-semibold text-dark fs-6">{{ $unitObj->unit_name }}</div>
                </div>
                <div>
                    <div class="text-muted small">Singkatan Satuan</div>
                    <div class="fw-semibold text-dark">{{ $unitObj->short_name }}</div>
                </div>
                <div>
                    <div class="text-muted small">Kategori / Tipe</div>
                    <div class="fw-semibold text-dark">{{ $unitObj->type ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Produk Terkait</div>
                    <div class="fw-semibold text-dark">{{ $connectedProducts->count() }} Produk</div>
                </div>
                <div>
                    <div class="text-muted small">Tanggal Dibuat</div>
                    <div class="text-dark">{{ $unitObj->created_at ? $unitObj->created_at->format('d M Y, H:i') : '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Connected Products Table -->
    <div class="col-lg-8">
        <div class="card-box p-0 bg-white border rounded-3 overflow-hidden shadow-sm mb-4">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0">Daftar Produk Terhubung</h6>
                <span class="text-muted small">{{ $connectedProducts->count() }} Produk Terdaftar</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Nama Produk</th>
                            <th>SKU</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-end">HPP Total</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center no-sort" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($connectedProducts as $product)
                            <tr>
                                <td class="text-center text-muted fw-medium">{{ $loop->iteration }}</td>
                                <td class="fw-semibold text-dark">{{ $product->prod_name }}</td>
                                <td class="font-monospace text-secondary small">{{ $product->sku }}</td>
                                <td class="text-end fw-bold text-dark">
                                    Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace text-secondary">
                                    Rp {{ number_format($product->current_hpp, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    {{ $product->current_stock }} {{ $unitObj->short_name }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-info text-white px-2 py-1 rounded-2 shadow-none" title="Lihat Produk" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted small">
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

