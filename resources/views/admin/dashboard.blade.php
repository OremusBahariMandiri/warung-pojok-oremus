@extends('layouts.admin')
@section('title', 'Dashboard — Warjok Admin')
@section('page-title', 'Dashboard')
@section('breadcrumb')
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="#">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
    </ol>
  </nav>
@endsection
@section('content')

<!-- Welcome Banner -->
<div class="card-box welcome-banner">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-1">Selamat Pagi, Administrator 👋</h4>
            <p class="text-muted mb-0">Berikut ringkasan operasional warung hari ini.</p>
        </div>
        <div class="text-end text-md-start">
            <span class="badge bg-light text-dark border py-2 px-3 fs-6 fw-medium">
                <i class="bi bi-calendar-event me-2"></i>Rabu, 17 September 2026
            </span>
        </div>
    </div>
</div>

<!-- Stats Cards Row -->
<div class="row">
    <!-- Card 1 -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="stat-card-icon stat-card-icon--emerald">
                    <i class="bi bi-box-seam"></i>
                </div>
                <span class="stat-card-trend stat-card-trend--up">
                    <i class="bi bi-arrow-up-short"></i> +12 bulan ini
                </span>
            </div>
            <h3 class="stat-card-value">156</h3>
            <p class="stat-card-label">Total Produk Aktif</p>
        </div>
    </div>
    <!-- Card 2 -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="stat-card-icon stat-card-icon--amber">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <span class="stat-card-trend stat-card-trend--warn">
                    <i class="bi bi-exclamation-circle-fill me-1"></i> Perlu restock segera
                </span>
            </div>
            <h3 class="stat-card-value">23</h3>
            <p class="stat-card-label">Produk Stok Rendah</p>
        </div>
    </div>
    <!-- Card 3 -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="stat-card-icon stat-card-icon--blue">
                    <i class="bi bi-cart-check"></i>
                </div>
                <span class="stat-card-trend stat-card-trend--up">
                    <i class="bi bi-arrow-up-short"></i> +18.5% vs kemarin
                </span>
            </div>
            <h3 class="stat-card-value">Rp 2.450.000</h3>
            <p class="stat-card-label">Omset Hari Ini</p>
        </div>
    </div>
    <!-- Card 4 -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="stat-card-icon stat-card-icon--rose">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <span class="stat-card-trend stat-card-trend--up">
                    <i class="bi bi-arrow-up-short"></i> +2.1% vs minggu lalu
                </span>
            </div>
            <h3 class="stat-card-value">32.8%</h3>
            <p class="stat-card-label">Margin Laba Bersih</p>
        </div>
    </div>
</div>

<!-- Middle Row -->
<div class="row">
    <!-- Left: Grafik Penjualan Mingguan -->
    <div class="col-lg-8">
        <div class="card-box h-100">
            <div class="card-box-header">
                <h5 class="card-box-title">Penjualan 7 Hari Terakhir</h5>
                <select class="form-select form-select-sm w-auto">
                    <option>Minggu Ini</option>
                    <option>Bulan Ini</option>
                </select>
            </div>
            <div class="chart-placeholder">
                <i class="bi bi-bar-chart-fill fs-1 mb-2 text-muted"></i>
                <p class="mb-0">Area Grafik Penjualan</p>
            </div>
        </div>
    </div>
    
    <!-- Right: Produk Terlaris -->
    <div class="col-lg-4">
        <div class="card-box h-100">
            <div class="card-box-header">
                <h5 class="card-box-title">Produk Terlaris Hari Ini</h5>
            </div>
            <div class="top-products-list mt-3">
                <div class="top-product-item">
                    <div class="top-product-rank">1</div>
                    <div class="top-product-info">
                        <div class="top-product-name">
                            <span>Es Teh Manis</span>
                            <span class="top-product-qty">45 porsi</span>
                        </div>
                        <div class="progress progress-thin">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
                <div class="top-product-item">
                    <div class="top-product-rank">2</div>
                    <div class="top-product-info">
                        <div class="top-product-name">
                            <span>Nasi Goreng Spesial</span>
                            <span class="top-product-qty">38 porsi</span>
                        </div>
                        <div class="progress progress-thin">
                            <div class="progress-bar bg-primary" style="width: 85%"></div>
                        </div>
                    </div>
                </div>
                <div class="top-product-item">
                    <div class="top-product-rank">3</div>
                    <div class="top-product-info">
                        <div class="top-product-name">
                            <span>Mie Ayam Bakso</span>
                            <span class="top-product-qty">32 porsi</span>
                        </div>
                        <div class="progress progress-thin">
                            <div class="progress-bar bg-info" style="width: 70%"></div>
                        </div>
                    </div>
                </div>
                <div class="top-product-item">
                    <div class="top-product-rank">4</div>
                    <div class="top-product-info">
                        <div class="top-product-name">
                            <span>Es Jeruk Segar</span>
                            <span class="top-product-qty">28 porsi</span>
                        </div>
                        <div class="progress progress-thin">
                            <div class="progress-bar bg-warning" style="width: 60%"></div>
                        </div>
                    </div>
                </div>
                <div class="top-product-item">
                    <div class="top-product-rank">5</div>
                    <div class="top-product-info">
                        <div class="top-product-name">
                            <span>Kopi Susu Gula Aren</span>
                            <span class="top-product-qty">25 porsi</span>
                        </div>
                        <div class="progress progress-thin">
                            <div class="progress-bar bg-secondary" style="width: 55%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row -->
<div class="row">
    <!-- Left: Aktivitas Restock Terakhir -->
    <div class="col-lg-5">
        <div class="card-box h-100">
            <div class="card-box-header">
                <h5 class="card-box-title">Restock Terakhir</h5>
            </div>
            <div class="timeline mt-3">
                <div class="timeline-item status-done">
                    <div class="timeline-title">Gula Pasir 50kg</div>
                    <div class="timeline-meta">
                        <span><i class="bi bi-calendar-event"></i> 16 Sep 2026</span>
                        <span><i class="bi bi-tag"></i> Rp 750.000</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Selesai</span>
                    </div>
                </div>
                <div class="timeline-item status-done">
                    <div class="timeline-title">Beras Premium 100kg</div>
                    <div class="timeline-meta">
                        <span><i class="bi bi-calendar-event"></i> 15 Sep 2026</span>
                        <span><i class="bi bi-tag"></i> Rp 1.400.000</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Selesai</span>
                    </div>
                </div>
                <div class="timeline-item status-pending">
                    <div class="timeline-title">Minyak Goreng 20L</div>
                    <div class="timeline-meta">
                        <span><i class="bi bi-calendar-event"></i> 15 Sep 2026</span>
                        <span><i class="bi bi-tag"></i> Rp 380.000</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Dikirim</span>
                    </div>
                </div>
                <div class="timeline-item status-pending">
                    <div class="timeline-title">Kopi Bubuk 10kg</div>
                    <div class="timeline-meta">
                        <span><i class="bi bi-calendar-event"></i> 14 Sep 2026</span>
                        <span><i class="bi bi-tag"></i> Rp 950.000</span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Pending</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right: Tabel Stok Rendah -->
    <div class="col-lg-7">
        <div class="card-box h-100">
            <div class="card-box-header">
                <h5 class="card-box-title">Produk Stok Kritis</h5>
                <a href="#" class="text-decoration-none small fw-medium">Lihat Semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th class="text-center">Stok Saat Ini</th>
                            <th class="text-center">Min. Stok</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="fw-medium">Kecap Manis 600ml</div>
                                <div class="small text-muted">Bumbu Dapur</div>
                            </td>
                            <td class="text-center fw-bold text-danger">2</td>
                            <td class="text-center">10</td>
                            <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Kritis</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="fw-medium">Telur Ayam</div>
                                <div class="small text-muted">Bahan Makanan</div>
                            </td>
                            <td class="text-center fw-bold text-danger">15</td>
                            <td class="text-center">50</td>
                            <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Kritis</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="fw-medium">Garam Dapur 500g</div>
                                <div class="small text-muted">Bumbu Dapur</div>
                            </td>
                            <td class="text-center fw-bold text-warning">8</td>
                            <td class="text-center">15</td>
                            <td><span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Rendah</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="fw-medium">Saus Sambal 1kg</div>
                                <div class="small text-muted">Bumbu Dapur</div>
                            </td>
                            <td class="text-center fw-bold text-warning">5</td>
                            <td class="text-center">10</td>
                            <td><span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Rendah</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="fw-medium">Tepung Terigu 1kg</div>
                                <div class="small text-muted">Bahan Makanan</div>
                            </td>
                            <td class="text-center fw-bold text-success">22</td>
                            <td class="text-center">20</td>
                            <td><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Aman</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
