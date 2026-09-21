@extends('layouts.admin')

@section('title', 'Laporan Penjualan — Warung Pojok Oremus')

@push('styles')
<!-- DataTables BSD & Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">management laporan</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">laporan penjualan</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $list = $reports ?? collect();
@endphp

<!-- Header Title -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold text-dark mb-1">Laporan Penjualan</h4>
        <p class="text-muted small mb-0">Kelola dan pantau laporan penjualan produk harian.</p>
    </div>
</div>

<!-- Session Alert Messages -->
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-0 shadow-sm" role="alert" style="background-color: var(--green-50); color: var(--green-600);">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('success') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('error') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if (session('warning'))
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('warning') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     DESKTOP LAYOUT (>= 768px) — DataTables Table
     Hanya tampil di md ke atas. Sembunyikan di mobile.
     ═══════════════════════════════════════════════════════════════ --}}
<div class="card-box border rounded-3 overflow-hidden shadow-sm bg-white mb-4 d-none d-md-block">

    <!-- Action & Filter Header -->
    <div class="py-3 d-flex align-items-center justify-content-between gap-3">
        <!-- Left: Search Box -->
        <div class="position-relative reports-search-box">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" id="dtSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2" placeholder="Cari tanggal / petugas...">
        </div>

        <!-- Right: Action & Filter Buttons -->
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-2 px-3 position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterReport">
                <i class="bi bi-funnel"></i> Filter
                <span id="activeFilterBadge" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                    <span class="visually-hidden">Filter Aktif</span>
                </span>
            </button>
            <a href="{{ route('reports.create') }}" class="btn btn-sm btn-success rounded-2 text-white fw-semibold d-inline-flex align-items-center gap-2 px-3">
                <i class="bi bi-plus-lg"></i> Tambah
            </a>
        </div>
    </div>

    {{--
        KUNCI PERBAIKAN DESKTOP:
        - div.reports-dt-scroll  → hanya membungkus <table>, overflow-x: auto di sini
        - Pagination & info      → dirender DataTables di LUAR div ini via custom `dom`
        - div.reports-dt-footer  → tempat DataTables meletakkan info + pagination
    --}}
    <div class="reports-dt-scroll">
        <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap" id="reportsDataTable">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th class="text-center" style="width: 150px;">Tanggal</th>
                    <th class="text-center" style="width: 130px;">Jumlah Produk</th>
                    <th class="text-center" style="width: 140px;">Produk Terjual</th>
                    <th class="text-end" style="width: 160px;">Total Penjualan</th>
                    <th class="text-end" style="width: 150px;">Total HPP</th>
                    <th class="text-end" style="width: 150px;">Total Margin</th>
                    <th class="text-center" style="width: 140px;">Dibuat Oleh</th>
                    <th class="text-center no-sort" style="width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($list as $r)
                    @php
                        $itemCount    = $r->details ? $r->details->count() : 0;
                        $totalSales   = (float)($r->total_sales ?? 0);
                        $totalHpp     = (float)($r->total_hpp ?? 0);
                        $totalMargin  = (float)($r->total_margin ?? ($totalSales - $totalHpp));
                        $formattedDate = $r->report_date ? $r->report_date->format('d M Y') : '-';
                        $creatorName  = $r->creator->employee_name ?? '';
                    @endphp
                    <tr data-date="{{ $r->report_date ? $r->report_date->format('Y-m-d') : '' }}">
                        <td class="text-center text-muted fw-medium small">{{ $loop->iteration }}</td>
                        <td class="text-center text-dark fw-semibold">{{ $formattedDate }}</td>
                        <td class="text-center"><span class="text-dark">{{ $itemCount }} Produk</span></td>
                        <td class="text-center font-monospace fw-semibold">{{ (int)($r->total_quantity ?? 0) }} Unit</td>
                        <td class="text-end font-monospace text-dark fw-semibold">Rp {{ number_format($totalSales, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace text-muted">Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($totalMargin, 0, ',', '.') }}</td>
                        <td class="text-center text-muted small">{{ $creatorName }}</td>
                        <td class="text-center">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('reports.show', $r->id) }}" class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1" title="Detail Laporan">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" title="Hapus Laporan"
                                    onclick="openDeleteModal({{ $r->id }}, '{{ $formattedDate }}', {{ (int)($r->total_quantity ?? 0) }}, {{ $totalSales }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>


{{-- ═══════════════════════════════════════════════════════════════
     MOBILE LAYOUT (< 768px) — Card List
     Hanya tampil di bawah md. Sembunyikan di desktop.
     ═══════════════════════════════════════════════════════════════ --}}
<div class="d-block d-md-none mb-4">
    <!-- Mobile Wrapper Card (Menggabungkan Toolbar & Daftar Card) -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        
        <!-- Header Card: Toolbar Search & Buat -->
        <div class="card-header bg-white my-4 d-flex flex-column gap-3 border-0">
            <!-- Search Bar — full width -->
            <div class="position-relative w-100">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="font-size:.85rem;"></i>
                <input type="text" id="mobileSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2 w-100" placeholder="Cari tanggal / petugas...">
            </div>

            <!-- Filter & Buat — sejajar di bawah search -->
            <div class="d-flex align-items-center gap-2 w-100">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center justify-content-center gap-1 px-3 grow position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterReport">
                    <i class="bi bi-funnel"></i> Filter
                    <span id="activeFilterBadgeMobile" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                        <span class="visually-hidden">Filter Aktif</span>
                    </span>
                </button>
                <a href="{{ route('reports.create') }}" class="btn btn-sm btn-success rounded-2 text-white fw-semibold d-inline-flex align-items-center justify-content-center gap-1 px-3 grow">
                    <i class="bi bi-plus-lg"></i> Tambah
                </a>
            </div>
        </div>

        <!-- Body Card: List Laporan Penjualan -->
        <div class="card-body p-3 pt-0" id="mobileReportCards">
            @forelse ($list as $r)
                @php
                    $itemCount    = $r->details ? $r->details->count() : 0;
                    $totalSales   = (float)($r->total_sales ?? 0);
                    $totalHpp     = (float)($r->total_hpp ?? 0);
                    $totalMargin  = (float)($r->total_margin ?? ($totalSales - $totalHpp));
                    $formattedDate = $r->report_date ? $r->report_date->format('d M Y') : '-';
                    $creatorName  = $r->creator->employee_name ?? 'Administrator';
                @endphp
                
                <div class="mobile-report-card mb-3"
                     data-date="{{ $r->report_date ? $r->report_date->format('Y-m-d') : '' }}"
                     data-search="{{ strtolower($formattedDate . ' ' . $creatorName) }}">

                    <!-- Card Header: Tanggal + Nomor urut -->
                    <div class="mrc-header">
                        <div class="mrc-no">{{ $loop->iteration }}</div>
                        <div class="mrc-date">{{ $formattedDate }}</div>
                        <div class="mrc-creator text-muted small">{{ $creatorName }}</div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="mrc-stats">
                        <div class="mrc-stat">
                            <span class="mrc-stat-label">Jumlah Produk</span>
                            <span class="mrc-stat-value">{{ $itemCount }} Produk</span>
                        </div>
                        <div class="mrc-stat">
                            <span class="mrc-stat-label">Produk Terjual</span>
                            <span class="mrc-stat-value font-monospace fw-semibold">{{ (int)($r->total_quantity ?? 0) }} Unit</span>
                        </div>
                        <div class="mrc-stat">
                            <span class="mrc-stat-label">Total Penjualan</span>
                            <span class="mrc-stat-value font-monospace fw-semibold text-dark">Rp {{ number_format($totalSales, 0, ',', '.') }}</span>
                        </div>
                        <div class="mrc-stat">
                            <span class="mrc-stat-label">Total HPP</span>
                            <span class="mrc-stat-value font-monospace text-muted">Rp {{ number_format($totalHpp, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Margin Bar -->
                    <div class="mrc-margin-bar {{ $totalMargin < 0 ? 'negative' : '' }}">
                        <span class="mrc-margin-label">Total Margin</span>
                        <span class="mrc-margin-value font-monospace fw-bold">Rp {{ number_format($totalMargin, 0, ',', '.') }}</span>
                    </div>

                    <!-- Actions -->
                    <div class="mrc-actions">
                        <a href="{{ route('reports.show', $r->id) }}" class="btn btn-sm btn-outline-secondary rounded-2 flex-grow-1">
                            <i class="bi bi-eye me-1"></i> Detail
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-3"
                            onclick="openDeleteModal({{ $r->id }}, '{{ $formattedDate }}', {{ (int)($r->total_quantity ?? 0) }}, {{ $totalSales }})">
                            <i class="bi bi-trash me-1"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted small">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    Belum ada laporan penjualan. Klik <strong>+ Tambah</strong> untuk membuat laporan baru.
                </div>
            @endforelse

            <!-- Mobile empty state -->
            <div id="mobileNoResult" class="text-center py-4 text-muted small d-none">
                Tidak ada laporan yang cocok dengan pencarian.
            </div>
        </div>

    </div>
</div>


<!-- Modal Filter Laporan Penjualan -->
<div class="modal fade" id="modalFilterReport" tabindex="-1" aria-labelledby="modalFilterReportLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark" id="modalFilterReportLabel">
                    <i class="bi bi-funnel me-1"></i> Filter Laporan Penjualan
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="modalFilterStartDate" class="form-label small fw-semibold text-dark">Dari Tanggal</label>
                    <input type="date" id="modalFilterStartDate" class="form-control rounded-2">
                </div>
                <div class="mb-3">
                    <label for="modalFilterEndDate" class="form-label small fw-semibold text-dark">Sampai Tanggal</label>
                    <input type="date" id="modalFilterEndDate" class="form-control rounded-2">
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-light border rounded-2 px-3" id="btnResetFilter" data-bs-dismiss="modal">
                    Reset
                </button>
                <button type="button" class="btn btn-sm btn-success text-white fw-semibold rounded-2 px-4" id="btnApplyFilter" data-bs-dismiss="modal">
                    Terapkan
                </button>
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
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Batalkan & Hapus Laporan
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
<!-- jQuery & DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="{{ asset('js/reports.js') }}"></script>
@endpush
