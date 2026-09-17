@extends('layouts.admin')

@section('title', 'Rincian Komponen HPP — Warung Pojok Oremus')
@section('page-title', 'Rincian Komponen HPP')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('hpp.index') }}">Master Komponen HPP</a></li>
        <li class="breadcrumb-item active" aria-current="page">Rincian Komponen</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $hppObj = $hpp;
    $connectedProducts = $hppObj->products ?? collect();
@endphp

<!-- Header Action Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">{{ $hppObj->name }}</h4>
        <span class="badge badge-soft-secondary font-monospace">ID Master: #HPP-{{ sprintf('%03d', $hppObj->id) }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('hpp.index') }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('hpp.edit', $hppObj->id) }}" class="btn btn-sm btn-success text-white rounded-3 px-3">
            <i class="bi bi-pencil me-1"></i> Edit Komponen
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Overview Card -->
    <div class="col-lg-4">
        <div class="card-box text-center">
            <div class="product-thumb-placeholder mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2.2rem; background: var(--green-50); color: var(--green-600);">
                <i class="bi bi-calculator"></i>
            </div>

            <h5 class="fw-bold text-dark mb-1">{{ $hppObj->name }}</h5>
            <div class="mb-3">
                <span class="badge badge-soft-info px-2.5 py-1">{{ strtoupper($hppObj->unit) }}</span>
            </div>

            <div class="calc-preview-card mb-3 text-center">
                <div class="text-muted small" style="font-size: 0.75rem;">Biaya Standar Bawaan (Unit Cost)</div>
                <div class="h4 fw-bold font-monospace text-success mb-0">
                    Rp {{ number_format($hppObj->unit_cost, 0, ',', '.') }}
                </div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">per {{ strtolower($hppObj->unit) }}</div>
            </div>

            <div class="p-3 bg-light rounded-3 text-start small text-muted">
                <div class="d-flex justify-content-between mb-1">
                    <span>Terdaftar Pada:</span>
                    <span class="text-dark fw-medium">{{ $hppObj->created_at ? $hppObj->created_at->format('d M Y, H:i') : '-' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Terakhir Diperbarui:</span>
                    <span class="text-dark fw-medium">{{ $hppObj->updated_at ? $hppObj->updated_at->format('d M Y, H:i') : '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Connected Products Table -->
    <div class="col-lg-8">
        <div class="card-box">
            <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
                <span><i class="bi bi-box-seam text-success me-2"></i> Daftar Produk Pengguna Komponen Ini (`product_hpp`)</span>
                <span class="badge badge-soft-success rounded-pill">{{ $connectedProducts->count() }} Produk</span>
            </h6>

            @if ($connectedProducts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr style="font-size: 0.78rem;">
                                <th>Produk</th>
                                <th class="text-center">Satuan</th>
                                <th class="text-end">Harga Jual</th>
                                <th class="text-end">Biaya Komponen Ini (`product_hpp.cost`)</th>
                                <th class="text-end" style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($connectedProducts as $prod)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $prod->prod_name }}</div>
                                        <div class="small text-muted font-monospace">{{ $prod->sku }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-soft-secondary font-monospace">{{ strtoupper($prod->satuan) }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-dark font-monospace">
                                        Rp {{ number_format($prod->selling_price, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace text-success fw-semibold">
                                        Rp {{ number_format($prod->pivot->cost ?? $hppObj->unit_cost, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('products.show', $prod->id) }}" class="action-btn action-btn-primary" title="Lihat Produk">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <div class="product-thumb-placeholder mx-auto mb-2 text-muted" style="background: var(--gray-100); width: 60px; height: 60px;">
                        <i class="bi bi-inbox fs-3"></i>
                    </div>
                    <div class="fw-medium">Belum Ada Produk yang Terhubung</div>
                    <div class="small text-muted">Komponen ini belum dimasukkan ke dalam resep atau kalkulasi produk manapun.</div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
