@extends('layouts.admin')

@section('title', 'Ringkasan Penjualan — Warung Pojok Oremus')

@push('styles')
<!-- DataTables BSD & Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/summary.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">management laporan</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">ringkasan penjualan</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $recapData = $recap ?? [];
    $dailyList = $recapData['daily_recap'] ?? [];
    $overall   = $recapData['overall'] ?? [];
    $period    = $recapData['period'] ?? [];

    $overallQty         = (int)($overall['total_quantity'] ?? 0);
    $overallSales       = (float)($overall['total_sales'] ?? 0);
    $overallHpp         = (float)($overall['total_hpp'] ?? 0);
    // Margin Penjualan (Total Penjualan - Total HPP)
    $overallMarginPenjualan = (float)($overall['total_gross_margin'] ?? ($overallSales - $overallHpp));
    $overallGrossMargin     = $overallMarginPenjualan; // Alias untuk tabel dan script JS

    // Gaji Karyawan (Margin Penjualan x 50%)
    $overallGaji        = $overallMarginPenjualan * 0.5;

    // Margin Kotor (Margin Penjualan - Gaji Karyawan)
    $overallMarginKotor = $overallMarginPenjualan - $overallGaji;

    $overallOperational = (float)($overall['total_operational_expense'] ?? 0);

    // Margin Bersih (Margin Kotor - Biaya Operasional)
    $overallNetMargin   = $overallMarginKotor - $overallOperational;

    $marginPercentage   = $overallSales > 0 ? ($overallMarginPenjualan / $overallSales) * 100 : 0;

    $startDateVal = request('start_date', now()->startOfMonth()->toDateString());
    $endDateVal   = request('end_date', now()->toDateString());
@endphp

<input type="hidden" id="exportStartDate" value="{{ $startDateVal }}">
<input type="hidden" id="exportEndDate" value="{{ $endDateVal }}">
<!-- Header Title -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Ringkasan & Margin Penjualan</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        {{-- TOMBOL EXPORT PDF --}}
        <button
            type="button"
            id="btnExportPdf"
            class="btn btn-sm btn-danger rounded-2 px-3 d-inline-flex align-items-center gap-2"
            title="Export laporan ke PDF">
            <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
        </button>
        {{-- TOMBOL EXPORT EXCEL (di sebelah kanan Export PDF) --}}
        <button
            type="button"
            id="btnExportExcel"
            class="btn btn-sm btn-success rounded-2 px-3 d-inline-flex align-items-center gap-2 text-white fw-medium"
            title="Export laporan ke Excel">
            <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
        </button>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-receipt"></i> Kelola Laporan Penjualan
        </a>
    </div>
</div>

<!-- Filter Bar & Preset Buttons -->
<div class="summary-filter-card mb-4">
    <form action="{{ route('reports.summary') }}" method="GET" id="formFilterSummary">
        <div class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label for="filterStartDate" class="form-label small fw-semibold text-dark mb-1">Dari Tanggal</label>
                <input type="date" class="form-control rounded-2" id="filterStartDate" name="start_date" value="{{ $startDateVal }}">
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="filterEndDate" class="form-label small fw-semibold text-dark mb-1">Sampai Tanggal</label>
                <input type="date" class="form-control rounded-2" id="filterEndDate" name="end_date" value="{{ $endDateVal }}">
            </div>
            <div class="col-lg-2 col-md-6">
                <button type="submit" class="btn btn-sm btn-success text-white fw-semibold rounded-2 w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-funnel-fill"></i> Terapkan
                </button>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center justify-content-lg-end gap-1 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 py-1 px-2 small" onclick="setFilterPreset('today')">Hari Ini</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 py-1 px-2 small" onclick="setFilterPreset('7days')">7 Hari</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 py-1 px-2 small" onclick="setFilterPreset('thisMonth')">Bulan Ini</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 py-1 px-2 small" onclick="setFilterPreset('lastMonth')">Bulan Lalu</button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- KPI Stat Cards Row -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Penjualan -->
    <div class="col-xl-3 col-md-6">
        <div class="summary-stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="summary-stat-icon summary-stat-icon--blue">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <span class="badge bg-primary bg-opacity-10 text-primary py-1 px-2 rounded-2 small fw-medium">
                    Omset
                </span>
            </div>
            <div class="summary-stat-label">Total Penjualan</div>
            <div class="summary-stat-value text-dark">
                Rp {{ number_format($overallSales, 0, ',', '.') }}
            </div>
            <p class="summary-stat-desc">Pendapatan kotor dari {{ $overallQty }} item terjual</p>
        </div>
    </div>

    <!-- Card 2: Total HPP -->
    <div class="col-xl-3 col-md-6">
        <div class="summary-stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="summary-stat-icon summary-stat-icon--amber">
                    <i class="bi bi-box-seam"></i>
                </div>
                <span class="badge bg-warning bg-opacity-10 text-warning py-1 px-2 rounded-2 small fw-medium">
                    Modal
                </span>
            </div>
            <div class="summary-stat-label">Total Modal HPP</div>
            <div class="summary-stat-value text-muted">
                Rp {{ number_format($overallHpp, 0, ',', '.') }}
            </div>
            <p class="summary-stat-desc">Biaya pokok persediaan produk terjual</p>
        </div>
    </div>

    <!-- Card 3: Margin Penjualan -->
    <div class="col-xl-3 col-md-6">
        <div class="summary-stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="summary-stat-icon summary-stat-icon--emerald">
                    <i class="bi bi-wallet2"></i>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success py-1 px-2 rounded-2 small fw-medium">
                    Margin Penjualan
                </span>
            </div>
            <div class="summary-stat-label">MARGIN PENJUALAN</div>
            <div class="summary-stat-value text-success">
                Rp {{ number_format($overallMarginPenjualan, 0, ',', '.') }}
            </div>
            <p class="summary-stat-desc">Total Penjualan − HPP</p>
        </div>
    </div>

    <!-- Card 4: Persentase Margin -->
    <div class="col-xl-3 col-md-6">
        <div class="summary-stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="summary-stat-icon summary-stat-icon--purple">
                    <i class="bi bi-percent"></i>
                </div>
                <span class="badge bg-info bg-opacity-10 text-info py-1 px-2 rounded-2 small fw-medium">
                    Rasio
                </span>
            </div>
            <div class="summary-stat-label">Rata-rata Margin</div>
            <div class="summary-stat-value text-primary">
                {{ number_format($marginPercentage, 1, ',', '.') }}%
            </div>
            <p class="summary-stat-desc">Rasio margin kotor terhadap omset</p>
        </div>
    </div>
</div>

{{-- ════════════ KARTU BREAKDOWN GAJI, MARGIN KOTOR, OPERASIONAL & MARGIN BERSIH ════════════ --}}
<div class="card border rounded-3 shadow-sm mb-4">
    <div class="card-body p-3 p-md-4">
        <div class="row g-3">
            <!-- 1. Gaji Karyawan -->
            <div class="col-lg-3 col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-light">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning bg-opacity-10" style="width:32px;height:32px;flex-shrink:0;">
                            <i class="bi bi-person-heart text-warning" style="font-size:.85rem;"></i>
                        </div>
                        <span class="small fw-semibold text-muted">Gaji Karyawan</span>
                    </div>
                    <div class="fw-bold font-monospace fs-5 text-warning">
                        Rp {{ number_format($overallGaji, 0, ',', '.') }}
                    </div>
                    <div class="text-muted mt-1" style="font-size:.72rem;">Margin penjualan × 50%</div>
                </div>
            </div>
            <!-- 2. Margin Kotor -->
            <div class="col-lg-3 col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-light">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-success bg-opacity-10" style="width:32px;height:32px;flex-shrink:0;">
                            <i class="bi bi-graph-up text-success" style="font-size:.85rem;"></i>
                        </div>
                        <span class="small fw-semibold text-muted">MARGIN KOTOR</span>
                    </div>
                    <div class="fw-bold font-monospace fs-5 text-success">
                        Rp {{ number_format($overallMarginKotor, 0, ',', '.') }}
                    </div>
                    <div class="text-muted mt-1" style="font-size:.72rem;">Margin penjualan − gaji karyawan</div>
                </div>
            </div>
            <!-- 3. Biaya Operasional -->
            <div class="col-lg-3 col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-light">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger bg-opacity-10" style="width:32px;height:32px;flex-shrink:0;">
                            <i class="bi bi-wallet2 text-danger" style="font-size:.85rem;"></i>
                        </div>
                        <span class="small fw-semibold text-muted">BIAYA OPERASIONAL</span>
                    </div>
                    <div class="fw-bold font-monospace fs-5 text-danger">
                        Rp {{ number_format($overallOperational, 0, ',', '.') }}
                    </div>
                    <div class="text-muted mt-1" style="font-size:.72rem;">Total Biaya Operasional</div>
                </div>
            </div>
            <!-- 4. Margin Bersih -->
            <div class="col-lg-3 col-md-6">
                <div class="border rounded-3 p-3 h-100 {{ $overallNetMargin < 0 ? 'bg-danger bg-opacity-10' : 'bg-light' }}">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center {{ $overallNetMargin < 0 ? 'bg-danger bg-opacity-10' : 'bg-primary bg-opacity-10' }}" style="width:32px;height:32px;flex-shrink:0;">
                            <i class="bi bi-wallet {{ $overallNetMargin < 0 ? 'text-danger' : 'text-primary' }}" style="font-size:.85rem;"></i>
                        </div>
                        <span class="small fw-semibold text-muted">MARGIN BERSIH</span>
                    </div>
                    <div class="fw-bold font-monospace fs-5 {{ $overallNetMargin < 0 ? 'text-danger' : 'text-primary' }}">
                        Rp {{ number_format($overallNetMargin, 0, ',', '.') }}
                    </div>
                    <div class="text-muted mt-1" style="font-size:.72rem;">Margin kotor − biaya ops</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <!-- Chart 1: Line/Bar Trend -->
    <div class="col-lg-8">
        <div class="summary-chart-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div>
                    <h6 class="fw-bold text-dark mb-0">Tren Penjualan, HPP & Margin Harian</h6>
                    <span class="text-muted small">Perbandingan omset penjualan, modal HPP, dan margin keuntungan harian.</span>
                </div>
            </div>
            <div class="summary-chart-wrapper">
                <canvas id="marginTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Komposisi Finansial Doughnut -->
    <div class="col-lg-4">
        <div class="summary-chart-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div>
                    <h6 class="fw-bold text-dark mb-0">Komposisi Finansial</h6>
                    <span class="text-muted small">Rasio HPP vs Margin Keuntungan.</span>
                </div>
            </div>
            <div class="summary-chart-wrapper d-flex align-items-center justify-content-center">
                <canvas id="marginDoughnutChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- DataTables Rekapitulasi Harian -->
<!-- DataTables Rekapitulasi Harian -->
<div class="card-box px-3 border rounded-3 overflow-hidden shadow-sm bg-white mb-4">
    <div class="py-3 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <h6 class="fw-bold text-dark mb-0">Tabel Rekapitulasi Harian</h6>
            <span class="text-muted small">Rincian agregasi penjualan, kuantitas produk, modal HPP, dan margin per tanggal.</span>
        </div>
        <span class="badge bg-light text-dark border py-2 px-3 fw-medium">
            <i class="bi bi-calendar3 me-1"></i> {{ count($dailyList) }} Hari Transaksi
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap" id="summaryRecapTable">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th class="text-center" style="width: 130px;">Tanggal</th>
                    <th class="text-center" style="width: 110px;">Total Terjual</th>
                    <th class="text-center" style="width: 110px;">Sisa Stok</th>
                    <th class="text-end" style="width: 140px;">Total Penjualan</th>
                    <th class="text-end" style="width: 130px;">Total HPP</th>
                    <th class="text-end" style="width: 130px;">Margin Kotor</th>
                    <th class="text-end" style="width: 130px;">Gaji Karyawan</th>
                    <th class="text-end" style="width: 130px;">Operasional</th>
                    <th class="text-end" style="width: 140px;">Margin Bersih</th>
                    <th class="text-center no-sort" style="width: 80px;">Detail</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dailyList as $item)
                    @php
                        $daySales       = (float)($item['total_sales'] ?? 0);
                        $dayHpp         = (float)($item['total_hpp'] ?? 0);
                        $dayGross       = (float)($item['total_gross_margin'] ?? ($daySales - $dayHpp));
                        $dayGaji        = $dayGross * 0.5;
                        $dayOperational = (float)($item['total_operational_expense'] ?? 0);
                        $dayNet         = ($dayGross - $dayGaji) - $dayOperational;
                        $formattedDate  = \Carbon\Carbon::parse($item['date'])->format('d M Y');
                        $dayProducts    = $item['products'] ?? [];
                        $totalSisaStok  = collect($dayProducts)->sum(fn($p) => (int)($p['stock_final'] ?? 0));
                        $minSisaStok    = collect($dayProducts)->min(fn($p) => (int)($p['stock_final'] ?? 0));
                    @endphp

                    <tr>
                        <td data-label="No" class="text-center text-muted fw-medium small">{{ $loop->iteration }}</td>
                        <td data-label="Tanggal" class="text-center text-dark fw-semibold">{{ $formattedDate }}</td>
                        <td data-label="Total Terjual" class="text-center font-monospace fw-semibold">{{ (int)($item['total_quantity'] ?? 0) }} Unit</td>
                        <td data-label="Sisa Stok" class="text-center font-monospace fw-semibold">
                            @if(count($dayProducts) > 0)
                                @if($minSisaStok <= 0)
                                    <span class="text-danger fw-bold">{{ $minSisaStok }} Unit</span>
                                @elseif($minSisaStok <= 5)
                                    <span class="text-warning fw-bold">{{ $minSisaStok }} Unit</span>
                                @else
                                    <span class="text-success fw-semibold">{{ $minSisaStok }} Unit</span>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td data-label="Total Penjualan" class="text-end font-monospace text-dark fw-semibold">Rp {{ number_format($daySales, 0, ',', '.') }}</td>
                        <td data-label="Total HPP" class="text-end font-monospace text-muted">Rp {{ number_format($dayHpp, 0, ',', '.') }}</td>
                        <td data-label="Margin Kotor" class="text-end font-monospace fw-bold text-success">Rp {{ number_format($dayGross, 0, ',', '.') }}</td>
                        <td data-label="Est. Gaji" class="text-end font-monospace text-warning fw-semibold">Rp {{ number_format($dayGaji, 0, ',', '.') }}</td>
                        <td data-label="Operasional" class="text-end font-monospace fw-semibold {{ $dayOperational > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $dayOperational > 0 ? 'Rp ' . number_format($dayOperational, 0, ',', '.') : 'Rp 0' }}
                        </td>
                        <td data-label="Margin Bersih" class="text-end font-monospace fw-bold {{ $dayNet < 0 ? 'text-danger' : 'text-primary' }}">
                            Rp {{ number_format($dayNet, 0, ',', '.') }}
                        </td>
                        <td data-label="Detail" class="text-center">
                            @if(count($dayProducts) > 0)
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1 btn-detail-produk"
                                    style="font-size:.75rem;"
                                    data-date="{{ $formattedDate }}"
                                    data-products="{{ json_encode($dayProducts) }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalDetailProduk"
                                    title="Lihat detail produk terjual pada {{ $formattedDate }}">
                                    <i class="bi bi-eye"></i>
                                </button>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold align-middle">
                    <td colspan="2" class="text-end text-dark">Total Keseluruhan:</td>
                    <td class="text-center font-monospace">{{ $overallQty }} Unit</td>
                    <td class="text-center font-monospace text-muted">—</td>
                    <td class="text-end font-monospace text-dark">Rp {{ number_format($overallSales, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-muted">Rp {{ number_format($overallHpp, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-success">Rp {{ number_format($overallGrossMargin, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-warning">Rp {{ number_format($overallGaji, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-danger">Rp {{ number_format($overallOperational, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace {{ $overallNetMargin < 0 ? 'text-danger' : 'text-primary' }}">Rp {{ number_format($overallNetMargin, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- ════════════ MODAL DETAIL PRODUK ════════════ --}}
<div class="modal fade" id="modalDetailProduk" tabindex="-1" aria-labelledby="modalDetailProdukLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            {{-- Header --}}
            <div class="modal-header px-4 py-3 border-0" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-white bg-opacity-10"
                         style="width:38px;height:38px;">
                        <i class="bi bi-box-seam text-white" style="font-size:.95rem;"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="modalDetailProdukLabel">Detail Produk Terjual</h6>
                        <span class="text-white opacity-60 small" id="modalDetailProdukDate" style="font-size:.78rem;">—</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Search + Info Bar --}}
            <div class="px-4 py-3 bg-white d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div class="position-relative" style="width:220px;">
                    <i class="bi bi-search position-absolute text-muted" style="left:10px;top:50%;transform:translateY(-50%);font-size:.8rem;pointer-events:none;"></i>
                    <input
                        type="text"
                        id="modalProductSearch"
                        class="form-control form-control-sm rounded-2 ps-4"
                        placeholder="Cari produk..."
                        style="font-size:.82rem;border:1.5px solid #cbd5e1;background:#f8fafc;">
                </div>
                <span class="text-muted" id="modalDetailProdukCount" style="font-size:.78rem;"></span>
            </div>

            {{-- Body: Table --}}
            <div class="modal-body p-0">
                <div class="px-3 pt-3 pb-0">
                    <table class="table table-hover table-bordered align-middle mb-0 w-100" style="font-size:.83rem;" id="modalDetailProdukTable">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th class="text-center text-uppercase px-3 py-2"
                                    style="width:46px;font-size:.72rem;letter-spacing:.04em;font-weight:600;color:#64748b;">No</th>
                                <th class="text-uppercase px-3 py-2"
                                    style="width:130px;font-size:.72rem;letter-spacing:.04em;font-weight:600;color:#64748b;">Kode</th>
                                <th class="text-uppercase px-3 py-2"
                                    style="font-size:.72rem;letter-spacing:.04em;font-weight:600;color:#64748b;">Nama Produk</th>
                                <th class="text-center text-uppercase px-3 py-2"
                                    style="width:120px;font-size:.72rem;letter-spacing:.04em;font-weight:600;color:#64748b;">Qty Terjual</th>
                                <th class="text-center text-uppercase px-3 py-2"
                                    style="width:120px;font-size:.72rem;letter-spacing:.04em;font-weight:600;color:#64748b;">Sisa Stok</th>
                            </tr>
                        </thead>
                        <tbody id="modalDetailProdukBody">
                            {{-- Diisi via JS --}}
                        </tbody>
                    </table>
                </div>

                {{-- DataTables footer (info + pagination) dirender di sini oleh JS --}}
                <div id="modalDtFooter" class="d-flex flex-column flex-sm-row align-items-center justify-content-between px-3 py-2 gap-2 bg-white border-top mt-0" style="font-size:.78rem;">
                    <span class="text-muted" id="modalDtInfo"></span>
                    <div class="d-flex align-items-center gap-1" id="modalDtPaginate"></div>
                </div>

                {{-- Empty state --}}
                <div id="modalEmptyState" class="d-none text-center py-5 px-3">
                    <i class="bi bi-inbox text-muted" style="font-size:2rem;opacity:.4;"></i>
                    <p class="text-muted mt-2 mb-0 small">Tidak ada produk ditemukan.</p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer bg-light border-0 px-4 py-2 justify-content-end">
                <button type="button" class="btn btn-sm btn-secondary rounded-2" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- jQuery, DataTables & Chart.js -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- jsPDF + AutoTable untuk Export PDF --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

{{-- SheetJS Style untuk Export Excel ber-border & ber-warna (.xlsx) --}}
<script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>

<script>
    window.summaryRecapData = @json($recapData);
    window.summaryOverall = {
        qty:             {{ $overallQty }},
        sales:           {{ $overallSales }},
        hpp:             {{ $overallHpp }},
        marginPenjualan: {{ $overallMarginPenjualan }},
        grossMargin:     {{ $overallGrossMargin }},
        marginKotor:     {{ $overallMarginKotor }},
        gaji:            {{ $overallGaji }},
        operational:     {{ $overallOperational }},
        netMargin:       {{ $overallNetMargin }},
        marginPct:       {{ $marginPercentage }},
    };
    window.summaryPeriod = {
        startDate: document.getElementById('exportStartDate')?.value || '',
        endDate:   document.getElementById('exportEndDate')?.value || '',
    };
</script>
<script src="{{ asset('js/summary.js') }}"></script>
@endpush