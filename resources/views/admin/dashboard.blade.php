@extends('layouts.admin')

@section('title', 'Dashboard — Warjok Admin')
@section('page-title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Home</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">Dashboard</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $currentUser = auth()->user();
    $userName = $currentUser->employee_name ?? $currentUser->username ?? 'Administrator';
@endphp

<!-- Welcome Banner -->
<div class="card-box welcome-banner mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">{{ $greeting }}, {{ $userName }} 👋</h4>
        </div>
        <div class="text-end text-md-start">
            <span class="badge bg-white text-dark border py-2 px-3 fs-6 fw-semibold shadow-sm rounded-3">
                <i class="bi bi-calendar-event text-success me-2"></i>{{ $formattedToday }}
            </span>
        </div>
    </div>
</div>

<!-- Stats Cards Row (4 KPI Cards) -->
<div class="row g-3 mb-2">
    <!-- Card 1: Total Produk Aktif -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="stat-card-icon stat-card-icon--emerald">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
            <div>
                <h3 class="stat-card-value">{{ number_format($totalProducts, 0, ',', '.') }}</h3>
                <p class="stat-card-label">Total Produk Aktif</p>
            </div>
        </div>
    </div>

    <!-- Card 2: Produk Stok Rendah -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="stat-card-icon stat-card-icon--amber">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
            <div>
                <h3 class="stat-card-value text-{{ $lowStockProductsCount > 0 ? 'danger' : 'dark' }}">
                    {{ number_format($lowStockProductsCount, 0, ',', '.') }}
                </h3>
                <p class="stat-card-label">Produk Stok Rendah</p>
            </div>
        </div>
    </div>

    <!-- Card 3: Omset Hari Ini -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="stat-card-icon stat-card-icon--blue">
                    <i class="bi bi-cart-check"></i>
                </div>
            </div>
            <div>
                <h3 class="stat-card-value font-monospace">Rp {{ number_format($todaySales, 0, ',', '.') }}</h3>
                <p class="stat-card-label">Omset Hari Ini</p>
            </div>
        </div>
    </div>

    <!-- Card 4: Margin Laba Bersih -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="stat-card-icon stat-card-icon--rose">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
            <div>
                <h3 class="stat-card-value text-success">{{ number_format($todayMarginPct, 1, ',', '.') }}%</h3>
                <p class="stat-card-label">Margin Laba Bersih</p>
            </div>
        </div>
    </div>
</div>

<!-- Middle Row: Sales Bar Chart & Top Selling Products -->
<div class="row g-3 mb-4">
    <!-- Left: Grafik Penjualan (Bar Chart) -->
    <div class="col-lg-8">
        <div class="card-box h-100 d-flex flex-column">
            <div class="card-box-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="card-box-title" id="salesChartTitle">Penjualan 7 Hari Terakhir</h5>
                    <span class="text-muted small" style="font-size:0.75rem;">Grafik batang performa omset dan margin keuntungan harian.</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm w-auto rounded-2" id="selectSalesPeriod">
                        <option value="7days" selected>7 Hari Terakhir</option>
                        <option value="30days">30 Hari Terakhir</option>
                    </select>
                </div>
            </div>
            <div class="sales-chart-wrapper flex-grow-1">
                <canvas id="salesBarChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Right: Produk Terlaris -->
    <div class="col-lg-4">
        <div class="card-box h-100 d-flex flex-column">
            <div class="card-box-header border-bottom pb-2 mb-2">
                <h5 class="card-box-title">
                    {{ $isTodayBestSeller ? 'Produk Terlaris Hari Ini' : 'Produk Terlaris Terakhir' }}
                </h5>
            </div>
            <div class="top-products-list flex-grow-1 d-flex flex-column justify-content-around">
                @php
                    $rankColors = ['bg-success', 'bg-primary', 'bg-info', 'bg-warning', 'bg-secondary'];
                @endphp
                @forelse ($topProducts as $item)
                    @php
                        $prod = $item->product;
                        $unit = $prod && $prod->unit ? ($prod->unit->short_name ?? $prod->unit->unit_name) : 'porsi';
                        $pct = $maxQty > 0 ? round(($item->total_qty / $maxQty) * 100) : 0;
                        $rankClass = $loop->iteration <= 3 ? 'rank-' . $loop->iteration : '';
                        $barColor = $rankColors[$loop->index % count($rankColors)];
                    @endphp
                    <div class="top-product-item">
                        <div class="top-product-rank {{ $rankClass }}">{{ $loop->iteration }}</div>
                        <div class="top-product-info">
                            <div class="top-product-name">
                                <span class="text-truncate" style="max-width: 160px;" title="{{ $prod->prod_name ?? 'Produk' }}">
                                    {{ $prod->prod_name ?? 'Produk #' . $item->product_id }}
                                </span>
                                <span class="top-product-qty">{{ (int)$item->total_qty }} {{ $unit }}</span>
                            </div>
                            <div class="progress progress-thin">
                                <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-basket fs-3 d-block mb-1 text-secondary"></i>
                        Belum ada data penjualan tercatat.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row: Recent Restocks & Critical Stock Table -->
<div class="row g-3">
    <!-- Left: Aktivitas Restock Terakhir -->
    <div class="col-lg-5">
        <div class="card-box h-100">
            <div class="card-box-header border-bottom pb-2 mb-3 d-flex align-items-center justify-content-between">
                <h5 class="card-box-title">Restock Terakhir</h5>
                <a href="{{ route('restock.index') }}" class="text-decoration-none small fw-medium text-success">
                    Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="timeline mt-3">
                @forelse ($recentRestocks as $r)
                    @php
                        $firstItem = $r->items->first();
                        $itemCount = $r->items->count();
                        $mainTitle = $firstItem && $firstItem->product 
                            ? $firstItem->product->prod_name . ' (' . (int)$firstItem->quantity . ' ' . ($firstItem->product->unit->short_name ?? 'Pcs') . ')'
                            : ($r->supplier_name ?: 'Restock Barang');
                        if ($itemCount > 1) {
                            $mainTitle .= ' +' . ($itemCount - 1) . ' item lain';
                        }
                        $cost = (float)($r->total_value ?? 0);
                    @endphp
                    <div class="timeline-item">
                        <div class="timeline-title text-truncate" title="{{ $mainTitle }}">{{ $mainTitle }}</div>
                        <div class="timeline-meta">
                            <span><i class="bi bi-calendar-event me-1"></i>{{ $r->restock_date ? $r->restock_date->format('d M Y') : '-' }}</span>
                            <span class="font-monospace text-dark fw-semibold">Rp {{ number_format($cost, 0, ',', '.') }}</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Selesai</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-box-arrow-in-down fs-3 d-block mb-1 text-secondary"></i>
                        Belum ada riwayat restock barang.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    
    <!-- Right: Tabel Stok Rendah / Kritis -->
    <div class="col-lg-7">
        <div class="card-box h-100">
            <div class="card-box-header border-bottom pb-2 mb-3 d-flex align-items-center justify-content-between">
                <h5 class="card-box-title">Produk Stok Kritis</h5>
                <a href="{{ route('products.index') }}" class="text-decoration-none small fw-medium text-success">
                    Lihat Semua Produk <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-critical-stock">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-center" style="width: 110px;">Stok Saat Ini</th>
                            <th class="text-center" style="width: 100px;">Min. Stok</th>
                            <th class="text-center" style="width: 90px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($criticalStockProducts as $p)
                            @php
                                $unitName = $p->unit ? ($p->unit->short_name ?? $p->unit->unit_name) : 'Pcs';
                                $curr = (int)$p->current_stock;
                                $min = (int)$p->min_stock;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $p->prod_name }}</div>
                                    <div class="small text-muted font-monospace" style="font-size:0.72rem;">{{ $p->sku ?: 'PRD-' . $p->id }}</div>
                                </td>
                                <td class="text-center fw-bold font-monospace {{ $curr <= $min ? 'text-danger' : 'text-dark' }}">
                                    {{ $curr }} {{ $unitName }}
                                </td>
                                <td class="text-center font-monospace text-muted">
                                    {{ $min }} {{ $unitName }}
                                </td>
                                <td class="text-center">
                                    @if ($curr <= 0)
                                        <span class="badge bg-danger text-white rounded-pill px-2 py-1">Habis</span>
                                    @elseif ($curr <= $min)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">Kritis</span>
                                    @elseif ($curr <= $min * 1.5)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">Rendah</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">Aman</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted small">
                                    Seluruh stok produk berada dalam kondisi aman.
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

@push('scripts')
<!-- Chart.js & Dashboard Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.dashboardChartData = {
        last7Days: @json($last7Days),
        last30Days: @json($last30Days)
    };
</script>
<script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
