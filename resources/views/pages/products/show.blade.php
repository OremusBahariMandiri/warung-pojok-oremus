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
    $unitShort  = $productObj->unit->short_name ?? $productObj->unit->unit_name ?? 'PCS';
    $allHpps    = $productObj->productHpps;
@endphp

<!-- Header Title di atas Card -->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Detail Produk: {{ $productObj->prod_name }}</h4>
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
                        <div class="text-muted small mb-1 product-thumbnail-label">Foto Produk</div>
                        @if ($productObj->thumbnail && !in_array($productObj->thumbnail, ['thumbnail/default.png', 'products/default.png']))
                            <img src="{{ asset('storage/' . $productObj->thumbnail) }}"
                                alt="{{ $productObj->prod_name }}"
                                class="product-thumbnail-img rounded-2 border">
                        @else
                            <div class="product-thumbnail-placeholder rounded-2">
                                <i class="bi bi-image fs-5 text-secondary"></i>
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="text-muted small">Kode Produk</div>
                    <div class="fw-semibold text-dark font-monospace">{{ $productObj->prod_code ?? $productObj->sku ?? '-' }}</div>
                </div>

                <div class="row g-2 pt-3">
                    <div class="col-4">
                        <div class="text-muted small">Stok Awal</div>
                        <div class="fw-semibold text-dark">{{ $productObj->initial_stock }} {{ $unitShort }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Stok Saat Ini</div>
                        <div class="fw-semibold {{ (int)$productObj->current_stock <= (int)$productObj->min_stock ? 'text-danger fw-bold' : 'text-dark' }}">
                            {{ $productObj->current_stock }} {{ $unitShort }}
                        </div>
                    </div>
                    <div class="col-4">
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

    <!-- Right Column: Semua Konfigurasi Satuan Jual -->
    <div class="col-lg-7">

        @if ($allHpps->isEmpty())
            <div class="card-box bg-white border rounded-3 p-4 text-center text-muted">
                <i class="bi bi-info-circle fs-4 mb-2 d-block"></i>
                Belum ada konfigurasi satuan jual untuk produk ini.
            </div>
        @else
            @foreach ($allHpps as $hppConfig)
                @php
                    $unitVal        = (float)($productObj->unit_price ?? 0);
                    $sellingVal     = (float)($hppConfig->selling_price ?? 0);
                    $currentHppVal  = (float)($hppConfig->current_hpp ?? $unitVal);
                    $existingDetails = $hppConfig->details ?? collect();
                    $profit         = $sellingVal - $currentHppVal;
                    $marginPercent  = $sellingVal > 0 ? round(($profit / $sellingVal) * 100, 1) : 0;
                    $hppMethod      = $hppConfig->hpp_method ?? 'MANUAL';
                    $satuanJual     = $hppConfig->sellingUnit->unit_name ?? '-';
                    $satuanJualShort = $hppConfig->sellingUnit->short_name ?? strtoupper($unitShort);
                @endphp

                <div class="card-box p-0 bg-white border rounded-3 overflow-hidden shadow-sm mb-4">
                    <!-- Header kartu: nama satuan jual -->
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light">
                        <div>
                            <h6 class="fw-bold text-dark mb-0">
                                Konfigurasi Satuan Jual: {{ $satuanJual }}
                                <span class="badge bg-secondary ms-1 fw-normal" style="font-size: 0.7rem;">{{ strtoupper($satuanJualShort) }}</span>
                            </h6>
                            <div class="text-muted small mt-1">
                                Metode HPP: <span class="fw-semibold text-dark">{{ strtoupper($hppMethod) === 'CALCULATED' ? 'Otomatis (Komposisi Resep)' : 'Manual (Fixed Cost)' }}</span>
                            </div>
                        </div>
                        <span class="badge bg-white text-dark border small">{{ $existingDetails->count() }} Komponen Tambahan</span>
                    </div>

                    <!-- Summary HPP & Harga Jual -->
                    <div class="px-3 pt-3 pb-2">
                        <div class="row g-2">
                            <div class="col-6 col-sm-3">
                                <div class="text-muted small">Harga Bahan Utama</div>
                                <div class="fw-bold font-monospace text-dark">Rp {{ number_format($unitVal, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-muted small">Total HPP Produk</div>
                                <div class="fw-bold font-monospace text-dark">Rp {{ number_format($currentHppVal, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-muted small">Harga Jual Konsumen</div>
                                <div class="fw-bold font-monospace text-dark">Rp {{ number_format($sellingVal, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-muted small">Margin Laba Bersih</div>
                                <div class="fw-bold {{ $marginPercent >= 30 ? 'text-success' : ($marginPercent >= 15 ? 'text-warning' : 'text-danger') }}">
                                    {{ $marginPercent }}%
                                    <span class="fw-normal font-monospace" style="font-size: 0.78rem;">(Rp {{ number_format($profit, 0, ',', '.') }})</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Komposisi HPP -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">No</th>
                                    <th>Komponen / Bahan</th>
                                    <th class="text-center" style="width: 120px;">Satuan</th>
                                    <th class="text-end" style="width: 150px;">Biaya (Rp)</th>
                                    <th class="text-end" style="width: 120px;">Kontribusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Row 1: Bahan Utama -->
                                <tr>
                                    <td class="text-center text-muted fw-medium">1</td>
                                    <td>
                                        <div class="fw-semibold text-dark">Bahan Utama Produk</div>
                                        <div class="text-muted small">Komponen utama / serbuk bahan</div>
                                    </td>
                                    <td class="text-center text-muted">{{ strtoupper($satuanJualShort) }}</td>
                                    <td class="text-end font-monospace fw-semibold text-dark">
                                        Rp {{ number_format($unitVal, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end text-muted">
                                        {{ $currentHppVal > 0 ? round(($unitVal / $currentHppVal) * 100, 1) : 0 }}%
                                    </td>
                                </tr>

                                <!-- Rows: Komponen Tambahan (ProductHppDetail) -->
                                @forelse ($existingDetails as $idx => $detail)
                                    @php
                                        $cost    = (float)($detail->hpp?->unit_cost ?? 0);
                                        $percent = $currentHppVal > 0 ? round(($cost / $currentHppVal) * 100, 1) : 0;
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted fw-medium">{{ $idx + 2 }}</td>
                                        <td>
                                            <div class="fw-medium text-dark">{{ $detail->hpp?->name ?? 'Komponen HPP #' . $detail->hpp_id }}</div>
                                        </td>
                                        <td class="text-center text-muted">{{ $detail->hpp?->unit ?? '-' }}</td>
                                        <td class="text-end font-monospace text-dark">
                                            Rp {{ number_format($cost, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end text-muted">{{ $percent }}%</td>
                                    </tr>
                                @empty
                                    @if (strtoupper($hppMethod) === 'CALCULATED')
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3 small">
                                                Tidak ada komponen HPP tambahan yang ditambahkan.
                                            </td>
                                        </tr>
                                    @endif
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end fw-bold text-dark">Total HPP Produk:</th>
                                    <th class="text-end fw-bold font-monospace text-dark">Rp {{ number_format($currentHppVal, 0, ',', '.') }}</th>
                                    <th class="text-end text-muted">100%</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif

    </div>
</div>

@endsection
