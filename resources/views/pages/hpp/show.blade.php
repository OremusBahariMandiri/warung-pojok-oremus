@extends('layouts.admin')

@section('title', 'Detail Komponen HPP — Warung Pojok Oremus')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('hpp.index') }}" class="text-decoration-none text-muted">management master hpp</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">detail komponen</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $hppObj = $hpp;
    $connectedProducts = $hppObj->products ?? collect();
@endphp

<!-- Header Title di atas Card -->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Detail Master Komponen HPP</h4>
        <p class="text-muted small mb-0">Rincian informasi komponen HPP dan daftar produk yang terhubung.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('hpp.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('hpp.edit', $hppObj->id) }}" class="btn btn-sm btn-warning text-white rounded-2 px-3 d-inline-flex align-items-center gap-2" style="background-color: #f59e0b; border-color: #f59e0b;">
            <i class="bi bi-pencil"></i> Edit Komponen
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: HPP Details Card -->
    <div class="col-lg-4">
        <div class="card-box bg-white border rounded-3 p-4 mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Informasi Komponen</h6>
            <div class="d-flex flex-column gap-3">
                <div>
                    <div class="text-muted small">Nama Komponen HPP</div>
                    <div class="fw-semibold text-dark fs-6">{{ $hppObj->name }}</div>
                </div>
                <div>
                    <div class="text-muted small">Satuan Pemakaian</div>
                    <div class="fw-semibold text-dark">{{ strtoupper($hppObj->unit) }}</div>
                </div>
                <div>
                    <div class="text-muted small">Biaya Standar Bawaan (Unit Cost)</div>
                    <div class="fw-semibold text-dark">Rp {{ number_format($hppObj->unit_cost, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-muted small">Produk Terkait</div>
                    <div class="fw-semibold text-dark">{{ $connectedProducts->count() }} Produk</div>
                </div>
                <div>
                    <div class="text-muted small">Tanggal Dibuat</div>
                    <div class="text-dark">{{ $hppObj->created_at ? $hppObj->created_at->format('d M Y, H:i') : '-' }}</div>
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
                            <th class="text-end">Biaya Komponen</th>
                            <th class="text-center no-sort" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($connectedProducts as $prod)
                            <tr>
                                <td class="text-center text-muted fw-medium">{{ $loop->iteration }}</td>
                                <td class="fw-semibold text-dark">{{ $prod->prod_name }}</td>
                                <td class="font-monospace text-secondary small">{{ $prod->sku }}</td>
                                <td class="text-end fw-bold text-dark">
                                    Rp {{ number_format($prod->selling_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace text-secondary">
                                    Rp {{ number_format($prod->pivot->cost ?? $hppObj->unit_cost, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('products.show', $prod->id) }}" class="btn btn-sm btn-info text-white px-2 py-1 rounded-2 shadow-none" title="Lihat Produk" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                                       <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    Belum ada produk yang menggunakan komponen HPP ini.
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
