@extends('layouts.admin')

@section('title', 'Detail Stock Opname ' . $detailedOpname->opname_code . ' — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/stock_opname.css') }}">
<style>
@media print {
    .sidebar, .topbar, .btn, .breadcrumb-container, .no-print {
        display: none !important;
    }
    .main-wrapper {
        margin: 0 !important;
        padding: 0 !important;
    }
    .card-box {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('stock-opname.index') }}" class="text-decoration-none text-muted">stock opname</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">detail</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $status = strtoupper($detailedOpname->status_opname);
    $items = $detailedOpname->items;

    $totalChecked = $items->count();
    $totalMatch = $items->where('difference', 0)->count();
    $totalSurplus = $items->where('difference', '>', 0)->count();
    $totalDeficit = $items->where('difference', '<', 0)->count();
@endphp

<!-- Header Actions Bar -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">Detail Stock Opname</h4>
            <span class="badge font-monospace fs-6 bg-light text-navy border">{{ $detailedOpname->opname_code }}</span>
            @if ($status === 'COMPLETED' || $status === 'CONFIRMED')
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                    <i class="bi bi-check-circle-fill me-1"></i> Selesai (Completed)
                </span>
            @else
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                    <i class="bi bi-clock-fill me-1"></i> Draft
                </span>
            @endif
        </div>
        <p class="text-muted small mb-0">Rincian perbandingan stok sistem dengan stok riil hasil opname.</p>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2" onclick="window.print()">
            <i class="bi bi-printer"></i> Cetak
        </button>

        @if ($status === 'DRAFT')
            <a href="{{ route('stock-opname.edit', $detailedOpname->id) }}" class="btn btn-sm btn-outline-warning rounded-2 px-3 text-warning-emphasis d-inline-flex align-items-center gap-2">
                <i class="bi bi-pencil"></i> Edit Draft
            </a>

            <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalCompleteOpname">
                <i class="bi bi-check2-circle"></i> Selesaikan Sekarang
            </button>
        @endif

        <a href="{{ route('stock-opname.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- Flash Message -->
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small d-flex align-items-center gap-2 no-print" role="alert">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>{{ session('success') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row g-4">
    <!-- Header Details Card -->
    <div class="col-lg-12">
        <div class="card-box bg-white border rounded-3 p-4">
            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-text text-primary"></i> Informasi Transaksi
            </h6>
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <span class="text-muted small d-block mb-1">Kode Transaksi</span>
                    <strong class="font-monospace text-navy fs-6">{{ $detailedOpname->opname_code }}</strong>
                </div>

                <div class="col-md-3 col-6">
                    <span class="text-muted small d-block mb-1">Tanggal Pemeriksaan</span>
                    <strong class="text-dark">{{ $detailedOpname->opname_date ? $detailedOpname->opname_date->format('d F Y H:i') : '-' }} WIB</strong>
                </div>

                <div class="col-md-3 col-6">
                    <span class="text-muted small d-block mb-1">Petugas Pemeriksa</span>
                    <strong class="text-dark">{{ $detailedOpname->creator->employee_name ?? 'Administrator' }}</strong>
                </div>

                <div class="col-md-3 col-6">
                    <span class="text-muted small d-block mb-1">Status Opname</span>
                    @if ($status === 'COMPLETED' || $status === 'CONFIRMED')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i> COMPLETED
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                            <i class="bi bi-clock-fill me-1"></i> DRAFT
                        </span>
                    @endif
                </div>

                @if ($detailedOpname->notes)
                    <div class="col-12 mt-2">
                        <span class="text-muted small d-block mb-1">Catatan Opname:</span>
                        <div class="p-2 bg-light rounded-2 text-secondary small border">
                            {{ $detailedOpname->notes }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Summary Statistics Grid -->
    <div class="col-lg-12">
        <div class="row g-3">
            <div class="col-md-3 col-6">
                <div class="opname-stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Diperiksa</div>
                        <div class="stat-value">{{ $totalChecked }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="opname-stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-check-all"></i>
                    </div>
                    <div>
                        <div class="stat-label">Stok Sesuai (0)</div>
                        <div class="stat-value text-secondary">{{ $totalMatch }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="opname-stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-arrow-up-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Surplus (+ Lebih)</div>
                        <div class="stat-value text-success">{{ $totalSurplus }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="opname-stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Defisit (- Kurang)</div>
                        <div class="stat-value text-danger">{{ $totalDeficit }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Table Card -->
    <div class="col-lg-12">
        <div class="card-box bg-white border rounded-3 p-4">
            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-list-check text-primary"></i> Rincian Perbandingan Produk
            </h6>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" id="opnameShowTable">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%;">No</th>
                            <th style="width: 15%;">Kode Produk</th>
                            <th style="width: 30%;">Nama Produk</th>
                            <th class="text-center" style="width: 10%;">Satuan</th>
                            <th class="text-center" style="width: 12%;">Stok Sistem</th>
                            <th class="text-center" style="width: 12%;">Stok Fisik</th>
                            <th class="text-center" style="width: 16%;">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            @php
                                $diff = (int) $item->difference;
                                $prod = $item->product;
                            @endphp
                            <tr>
                                <td class="text-center fw-semibold text-secondary">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="font-monospace fw-semibold text-muted">{{ $prod->prod_code ?? '-' }}</span>
                                </td>
                                <td>
                                    <strong class="text-dark">{{ $prod->prod_name ?? 'Produk Dihapus' }}</strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border">
                                        {{ $prod->unit->unit_name ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-center fw-bold text-navy">
                                    {{ $item->system_stock }}
                                </td>
                                <td class="text-center fw-bold text-primary">
                                    {{ $item->physical_stock }}
                                </td>
                                <td class="text-center">
                                    @if ($diff === 0)
                                        <span class="diff-badge zero">
                                            <i class="bi bi-check-circle-fill"></i> 0 (Sesuai)
                                        </span>
                                    @elseif ($diff > 0)
                                        <span class="diff-badge surplus">
                                            <i class="bi bi-arrow-up-circle-fill"></i> +{{ $diff }} (Lebih)
                                        </span>
                                    @else
                                        <span class="diff-badge deficit">
                                            <i class="bi bi-arrow-down-circle-fill"></i> {{ $diff }} (Kurang)
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Tidak ada produk yang tercatat dalam transaksi opname ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Selesaikan Opname Sekarang (From Show View) -->
@if ($status === 'DRAFT')
<div class="modal fade" id="modalCompleteOpname" tabindex="-1" aria-labelledby="modalCompleteOpnameLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalCompleteOpnameLabel">
                    <i class="bi bi-check2-circle text-primary fs-5"></i> Selesaikan Transaksi Stock Opname
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-secondary mb-3">
                    Apakah Anda ingin menyelesaikan dan menerapkan hasil pemeriksaan stok ini ke <strong>master data stok</strong>?
                </p>
                <div class="alert alert-warning border-warning border-opacity-25 rounded-2 p-3 mb-0 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Stok produk saat ini akan otomatis digantikan oleh angka stok fisik yang tercatat.
                </div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('stock-opname.update_status', $detailedOpname->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status_opname" value="COMPLETED">
                    <button type="submit" class="btn btn-primary rounded-2 px-4">
                        <i class="bi bi-check2-all me-1"></i> Ya, Terapkan & Selesaikan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
