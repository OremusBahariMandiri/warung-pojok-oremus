@extends('layouts.admin')

@section('title', 'Detail Laporan Penjualan — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">management laporan</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">detail laporan</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $reportObj = $detailedReport;
    $detailsList = $reportObj->details ?? collect();
    $totalSales = (float)($reportObj->total_sales ?? 0);
    $totalHpp = (float)($reportObj->total_hpp ?? 0);
    $totalMargin = (float)($reportObj->total_margin ?? ($totalSales - $totalHpp));
    $formattedDate = $reportObj->report_date ? $reportObj->report_date->format('d M Y') : '-';
@endphp

<!-- Header Title di atas Card -->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Detail Laporan Penjualan</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <button type="button" class="btn btn-sm btn-danger text-white rounded-2 px-3 d-inline-flex align-items-center gap-2" onclick="openDeleteModal({{ $reportObj->id }}, '{{ $formattedDate }}', {{ (int)($reportObj->total_quantity ?? 0) }}, {{ $totalSales }})">
            <i class="bi bi-trash-fill"></i>Hapus
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Informasi Laporan -->
    <div class="col-lg-4">
        <div class="card-box bg-white border rounded-3 p-4 mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Informasi Laporan</h6>
            <div class="d-flex flex-column gap-3">
                <div>
                    <div class="text-muted small">Tanggal Penjualan</div>
                    <div class="fw-semibold text-dark fs-6">{{ $reportObj->report_date ? $reportObj->report_date->format('d F Y, H:i') : '-' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Petugas / Dicatat Oleh</div>
                    <div class="text-dark fw-medium">{{ $reportObj->creator->employee_name ?? 'Administrator' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Catatan Penjualan</div>
                    <div class="text-dark small">{{ $reportObj->notes ?: '-' }}</div>
                </div>
                <div class="row g-2 pt-2 border-top">
                    <div class="col-6">
                        <div class="text-muted small">Total Jenis Produk</div>
                        <div class="fw-semibold text-dark">{{ $detailsList->count() }} Produk</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Total Kuantitas Terjual</div>
                        <div class="fw-semibold text-dark">{{ (int)($reportObj->total_quantity ?? 0) }} Unit</div>
                    </div>
                </div>
                <div class="pt-2 border-top">
                    <div class="text-muted small">Total Penjualan (Omset)</div>
                    <div class="fw-bold font-monospace text-dark fs-5">
                        Rp {{ number_format($totalSales, 0, ',', '.') }}
                    </div>
                </div>
                <div>
                    <div class="text-muted small">Total HPP (Modal)</div>
                    <div class="fw-semibold font-monospace text-muted fs-6">
                        Rp {{ number_format($totalHpp, 0, ',', '.') }}
                    </div>
                </div>
                <div class="p-3 rounded-2 bg-success bg-opacity-10 border border-success border-opacity-25">
                    <div class="text-success fw-medium small">Total Margin Keuntungan</div>
                    <div class="fw-bold font-monospace text-success fs-4">
                        Rp {{ number_format($totalMargin, 0, ',', '.') }}
                    </div>
                </div>
                <div class="pt-2 border-top">
                    <div class="text-muted small">Waktu Pencatatan</div>
                    <div class="text-dark small">{{ $reportObj->created_at ? $reportObj->created_at->format('d M Y, H:i') : '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Daftar Produk Terjual -->
    <div class="col-lg-8">
        <div class="card-box p-0 bg-white border rounded-3 overflow-hidden shadow-sm mb-4">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0">Rincian Produk Terjual</h6>
                <span class="text-muted small">{{ $detailsList->count() }} Produk Terdaftar</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 w-100" id="reportShowTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="text-center">No</th>
                            <th>Produk</th>
                            <th class="text-center" style="width: 100px;">Kuantitas</th>
                            <th class="text-end" style="width: 120px;">Harga Jual</th>
                            <th class="text-end" style="width: 130px;">Total Penjualan</th>
                            <th class="text-end" style="width: 110px;">HPP / Unit</th>
                            <th class="text-end" style="width: 120px;">Total HPP</th>
                            <th class="text-end" style="width: 120px;">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($detailsList as $detail)
                            @php
                                $p = $detail->product;
                                $unitName = $p && $p->unit ? ($p->unit->short_name ?: $p->unit->unit_name) : 'Pcs';
                                $qty = (int)$detail->quantity;
                                $price = (float)$detail->selling_price;
                                $hpp = (float)$detail->hpp;
                                $subtotalSales = (float)$detail->total_price;
                                $subtotalHpp = (float)$detail->total_hpp;
                                $margin = (float)$detail->margin;
                            @endphp
                            <tr>
                                <td class="text-center text-muted small">{{ $loop->iteration }}</td>
                                <td>
                                    @if ($p)
                                        <div class="fw-semibold text-dark">{{ $p->prod_name }}</div>
                                        <div class="text-muted small font-monospace" style="font-size: 0.72rem;">{{ $p->sku ?: 'PRD-' . $p->id }}</div>
                                    @else
                                        <div class="text-muted fst-italic">Produk telah dihapus (ID: {{ $detail->product_id }})</div>
                                    @endif
                                </td>
                                <td class="text-center font-monospace fw-semibold text-dark">
                                    {{ $qty }} {{ $unitName }}
                                </td>
                                <td class="text-end font-monospace text-muted small">
                                    Rp {{ number_format($price, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace fw-semibold text-dark">
                                    Rp {{ number_format($subtotalSales, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace text-muted small">
                                    Rp {{ number_format($hpp, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace text-muted small">
                                    Rp {{ number_format($subtotalHpp, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace fw-bold {{ $margin < 0 ? 'text-danger' : 'text-success' }}">
                                    Rp {{ number_format($margin, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted small">
                                    Tidak ada rincian item dalam laporan penjualan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold align-middle">
                            <td colspan="2" class="text-end text-dark">Total Keseluruhan:</td>
                            <td class="text-center font-monospace">{{ (int)($reportObj->total_quantity ?? 0) }} Unit</td>
                            <td class="text-end">-</td>
                            <td class="text-end font-monospace text-dark">Rp {{ number_format($totalSales, 0, ',', '.') }}</td>
                            <td class="text-end">-</td>
                            <td class="text-end font-monospace text-muted">Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace text-success">Rp {{ number_format($totalMargin, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus & Rollback Laporan -->
<div class="modal fade" id="modalDeleteReport" tabindex="-1" aria-labelledby="modalDeleteReportLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-danger" id="modalDeleteReportLabel">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Hapus
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-dark mb-2">
                    Apakah Anda yakin ingin menghapus laporan penjualan tanggal <strong id="deleteReportDate" class="text-dark"></strong>?
                </p>
                <div class="alert alert-warning py-2 px-3 small rounded-2 mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>Perhatian:</strong> Menghapus laporan ini akan secara otomatis <strong>mengembalikan stok</strong> seluruh produk yang terjual (<span id="deleteReportQty" class="fw-bold"></span>) ke saldo persediaan inventori.
                </div>
                <div class="bg-light p-2 rounded-2 border text-muted small">
                    Total Nilai Penjualan: <span id="deleteReportSales" class="fw-bold text-dark font-monospace"></span>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-light border rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                <form id="deleteReportForm" action="" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger text-white fw-bold rounded-2 px-3">
                        <i class="bi bi-trash-fill"></i> Ya, Hapus & Rollback Stok
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/reports.js') }}"></script>
@endpush
