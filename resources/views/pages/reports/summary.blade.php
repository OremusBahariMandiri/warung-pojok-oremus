@extends('layouts.admin')

@section('title', 'Ringkasan & Margin — Warung Pojok Oremus')

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
        <li class="breadcrumb-item active text-dark" aria-current="page">ringkasan & margin</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $recapData = $recap ?? [];
    $dailyList = $recapData['daily_recap'] ?? [];
    $overall = $recapData['overall'] ?? [];
    $period = $recapData['period'] ?? [];

    $overallQty = (int)($overall['total_quantity'] ?? 0);
    $overallSales = (float)($overall['total_sales'] ?? 0);
    $overallHpp = (float)($overall['total_hpp'] ?? 0);
    $overallMargin = (float)($overall['total_margin'] ?? ($overallSales - $overallHpp));
    $marginPercentage = $overallSales > 0 ? ($overallMargin / $overallSales) * 100 : 0;

    $startDateVal = request('start_date', now()->startOfMonth()->toDateString());
    $endDateVal = request('end_date', now()->toDateString());
@endphp

<!-- Header Title -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Ringkasan & Margin Penjualan</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
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

    <!-- Card 3: Total Margin -->
    <div class="col-xl-3 col-md-6">
        <div class="summary-stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="summary-stat-icon summary-stat-icon--emerald">
                    <i class="bi bi-wallet2"></i>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success py-1 px-2 rounded-2 small fw-medium">
                    Laba Bersih
                </span>
            </div>
            <div class="summary-stat-label">Margin Keuntungan</div>
            <div class="summary-stat-value text-success">
                Rp {{ number_format($overallMargin, 0, ',', '.') }}
            </div>
            <p class="summary-stat-desc">Laba kotor bersih (Penjualan - HPP)</p>
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
            <p class="summary-stat-desc">Rasio margin laba kotor terhadap omset</p>
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
                    <th class="text-center" style="width: 150px;">Tanggal</th>
                    <th class="text-center" style="width: 140px;">Total Terjual</th>
                    <th class="text-end" style="width: 170px;">Total Penjualan</th>
                    <th class="text-end" style="width: 160px;">Total Modal HPP</th>
                    <th class="text-end" style="width: 160px;">Total Margin</th>
                    <th class="text-center" style="width: 120px;">% Margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dailyList as $item)
                    @php
                        $daySales = (float)($item['total_sales'] ?? 0);
                        $dayHpp = (float)($item['total_hpp'] ?? 0);
                        $dayMargin = (float)($item['total_margin'] ?? ($daySales - $dayHpp));
                        $dayMarginPct = $daySales > 0 ? ($dayMargin / $daySales) * 100 : 0;
                        $formattedDate = \Carbon\Carbon::parse($item['date'])->format('d M Y');
                    @endphp
                    <tr>
                        <td class="text-center text-muted fw-medium small" data-label="No">{{ $loop->iteration }}</td>
                        <td class="text-center text-dark fw-semibold" data-label="Tanggal">
                            {{ $formattedDate }}
                        </td>
                        <td class="text-center font-monospace fw-semibold" data-label="Total Terjual">
                            {{ (int)($item['total_quantity'] ?? 0) }} Unit
                        </td>
                        <td class="text-end font-monospace text-dark fw-semibold" data-label="Total Penjualan">
                            Rp {{ number_format($daySales, 0, ',', '.') }}
                        </td>
                        <td class="text-end font-monospace text-muted" data-label="Total Modal HPP">
                            Rp {{ number_format($dayHpp, 0, ',', '.') }}
                        </td>
                        <td class="text-end font-monospace fw-bold text-success" data-label="Total Margin">
                            Rp {{ number_format($dayMargin, 0, ',', '.') }}
                        </td>
                        <td class="text-center font-monospace" data-label="% Margin">
                            <span class="badge bg-success bg-opacity-10 text-success py-1 px-2 rounded-2">
                                {{ number_format($dayMarginPct, 1, ',', '.') }}%
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold align-middle">
                    <td colspan="2" class="text-end text-dark">Total Keseluruhan:</td>
                    <td class="text-center font-monospace">{{ $overallQty }} Unit</td>
                    <td class="text-end font-monospace text-dark">Rp {{ number_format($overallSales, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-muted">Rp {{ number_format($overallHpp, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-success">Rp {{ number_format($overallMargin, 0, ',', '.') }}</td>
                    <td class="text-center font-monospace text-primary">{{ number_format($marginPercentage, 1, ',', '.') }}%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<!-- jQuery, DataTables & Chart.js -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.summaryRecapData = @json($recapData);
</script>
<script src="{{ asset('js/summary.js') }}"></script>
@endpush
