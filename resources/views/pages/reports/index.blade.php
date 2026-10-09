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

{{-- DESKTOP TABLE --}}
<div class="card-box px-3 border rounded-3 overflow-hidden shadow-sm bg-white mb-4 reports-desktop-card">

    <!-- Action & Filter Header -->
    <div class="py-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div class="position-relative reports-search-box">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" id="dtSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2" placeholder="Cari tanggal, produk terjual, dibuat oleh...">
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-2 px-3 position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterReport">
                <i class="bi bi-funnel"></i> Filter
                <span id="activeFilterBadge" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                    <span class="visually-hidden">Filter Aktif</span>
                </span>
            </button>
            {{-- Tombol Tambah selalu tampil — validasi dilakukan via modal date picker --}}
            <button type="button"
                    class="btn btn-sm btn-success rounded-2 text-white fw-semibold d-inline-flex align-items-center gap-2 px-3"
                    onclick="openReportDatePickerModal()">
                <i class="bi bi-plus-lg"></i> Tambah
            </button>
        </div>
    </div>

    <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap" id="reportsDataTable">
        <thead>
            <tr>
                <th style="width:50px;"  class="text-center">No</th>
                <th style="width:150px;" class="text-center">Tanggal</th>
                <th style="width:130px;" class="text-center">Jumlah Produk</th>
                <th style="width:140px;" class="text-center">Produk Terjual</th>
                <th style="width:160px;" class="text-end">Total Penjualan</th>
                <th style="width:150px;" class="text-end">Total HPP</th>
                <th style="width:155px;" class="text-end">Total Margin Kotor</th>
                <th style="width:150px;" class="text-end">Margin Bersih</th>
                <th style="width:140px;" class="text-center">Dibuat Oleh</th>
                <th style="width:110px;" class="text-center no-sort">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($list as $r)
            @php
                $itemCount        = $r->details ? $r->details->count() : 0;
                $totalSales       = (float)($r->total_sales ?? 0);
                $totalHpp         = (float)($r->total_hpp ?? 0);
                $totalMargin      = (float)($r->total_gross_margin ?? ($totalSales - $totalHpp));
                $netMarginDesktop = $totalMargin * 0.5;
                $formattedDate    = $r->report_date ? $r->report_date->format('d M Y') : '-';
                $creatorName      = $r->creator->employee_name ?? '';
            @endphp
            <tr data-date="{{ $r->report_date ? $r->report_date->format('Y-m-d') : '' }}">
                {{-- 0: No --}}
                <td class="text-center text-muted fw-medium small">{{ $loop->iteration }}</td>
                {{-- 1: Tanggal --}}
                <td class="text-center text-dark fw-semibold">{{ $formattedDate }}</td>
                {{-- 2: Jumlah Produk --}}
                <td class="text-center"><span class="text-dark">{{ $itemCount }} Produk</span></td>
                {{-- 3: Produk Terjual --}}
                <td class="text-center font-monospace fw-semibold">{{ (int)($r->total_quantity ?? 0) }} Unit</td>
                {{-- 4: Total Penjualan --}}
                <td class="text-end font-monospace text-dark fw-semibold">Rp {{ number_format($totalSales, 0, ',', '.') }}</td>
                {{-- 5: Total HPP --}}
                <td class="text-end font-monospace text-muted">Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                {{-- 6: Total Margin Kotor --}}
                <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($totalMargin, 0, ',', '.') }}</td>
                {{-- 7: Margin Bersih --}}
                <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($netMarginDesktop, 0, ',', '.') }}</td>
                {{-- 8: Dibuat Oleh --}}
                <td class="text-center text-muted small">{{ $creatorName }}</td>
                {{-- 9: Aksi — sekarang paling kanan --}}
                <td class="text-center">
                    <div class="d-inline-flex align-items-center gap-1">
                        <a href="{{ route('reports.show', $r->id) }}"
                        class="btn btn-sm btn-info text-white px-2 py-1 rounded-2 shadow-none"
                        title="Detail" style="background-color:#0ea5e9;border-color:#0ea5e9;">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('reports.edit', $r->id) }}"
                        class="btn btn-sm btn-warning text-white px-2 py-1 rounded-2 shadow-none"
                        title="Edit Laporan" style="background-color:#f59e0b;border-color:#f59e0b;">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="{{ route('reports.salary_calculator', [
                            'start_date' => $r->report_date->toDateString(),
                            'end_date'   => $r->report_date->toDateString(),
                        ]) }}"
                        class="btn btn-sm btn-primary px-2 py-1 rounded-2 shadow-none"
                        title="Kalkulator Gaji">
                            <i class="bi bi-calculator"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>


{{-- MOBILE CARD LIST --}}
<div class="reports-mobile-wrapper mb-4">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">

        <div class="card-header bg-white my-4 d-flex flex-column gap-3 border-0">
            <div class="position-relative w-100">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="font-size:.85rem;"></i>
                <input type="text" id="mobileSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2 w-100" placeholder="Cari tanggal, produk terjual, dibuat oleh...">
            </div>
            <div class="d-flex align-items-center gap-2 w-100">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center justify-content-center gap-1 px-3 position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterReport">
                    <i class="bi bi-funnel"></i> Filter
                    <span id="activeFilterBadgeMobile" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                        <span class="visually-hidden">Filter Aktif</span>
                    </span>
                </button>
                {{-- Tombol Tambah mobile — selalu tampil --}}
                <button type="button"
                        class="btn btn-sm btn-success rounded-2 text-white fw-semibold d-inline-flex align-items-center justify-content-center gap-1 px-3"
                        onclick="openReportDatePickerModal()">
                    <i class="bi bi-plus-lg"></i> Tambah
                </button>
            </div>
        </div>

        <div class="card-body p-3 pt-0" id="mobileReportCards">
            @forelse ($list as $r)
                @php
                    $itemCount     = $r->details ? $r->details->count() : 0;
                    $totalSales    = (float)($r->total_sales ?? 0);
                    $totalHpp      = (float)($r->total_hpp ?? 0);
                    $totalMargin   = (float)($r->total_gross_margin ?? ($totalSales - $totalHpp));
                    $formattedDate = $r->report_date ? $r->report_date->format('d M Y') : '-';
                    $creatorName   = $r->creator->employee_name ?? 'Administrator';
                @endphp

                <div class="mobile-report-card mb-3"
                     data-date="{{ $r->report_date ? $r->report_date->format('Y-m-d') : '' }}"
                     data-search="{{ strtolower($formattedDate . ' ' . $creatorName) }}">

                    <div class="mrc-header">
                        <div class="mrc-no">{{ $loop->iteration }}</div>
                        <div class="mrc-date">{{ $formattedDate }}</div>
                        <div class="mrc-creator text-muted small">{{ $creatorName }}</div>
                    </div>

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

                    {{-- margin kotor + bersih di mobile card --}}
                    <div class="mrc-margin-bar {{ $totalMargin < 0 ? 'negative' : '' }}">
                        <span class="mrc-margin-label">Margin Kotor</span>
                        <span class="mrc-margin-value font-monospace fw-bold">Rp {{ number_format($totalMargin, 0, ',', '.') }}</span>
                    </div>
                    @php $netMobile = $totalMargin * 0.5; @endphp
                    <div class="mrc-margin-bar" style="background:var(--bs-primary-bg-subtle,#cfe2ff); color:var(--bs-primary,#0d6efd);">
                        <span class="mrc-margin-label">Margin Bersih</span>
                        <span class="mrc-margin-value font-monospace fw-bold">Rp {{ number_format($netMobile, 0, ',', '.') }}</span>
                    </div>

                    <div class="mrc-actions">
                        <a href="{{ route('reports.show', $r->id) }}" class="btn btn-sm btn-outline-secondary rounded-2 flex-grow-1">
                            <i class="bi bi-eye me-1"></i> Detail
                        </a>
                        <a href="{{ route('reports.edit', $r->id) }}" class="btn btn-sm btn-warning text-white rounded-2 px-3">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="{{ route('reports.salary_calculator', [
                               'start_date' => $r->report_date->toDateString(),
                               'end_date'   => $r->report_date->toDateString(),
                           ]) }}"
                           class="btn btn-sm btn-outline-primary rounded-2 px-3"
                           title="Kalkulator Gaji">
                            <i class="bi bi-calculator"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted small">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    Belum ada laporan penjualan. Klik <strong>+ Tambah</strong> untuk membuat laporan baru.
                </div>
            @endforelse

            <div id="mobileNoResult" class="text-center py-4 text-muted small d-none">
                Tidak ada laporan yang cocok dengan pencarian.
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════════
     MODAL: Pilih Tanggal Laporan (validasi sebelum masuk create)
     ══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalDatePickerReport" tabindex="-1" aria-labelledby="modalDatePickerReportLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalDatePickerReportLabel">
                    <i class="bi bi-calendar-plus fs-5 text-success"></i> Pilih Tanggal Laporan
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    Pilih tanggal penjualan yang ingin dibuat laporan. Sistem akan memeriksa apakah laporan untuk tanggal tersebut sudah ada.
                </p>
                <div class="mb-3">
                    <label for="reportDatePickerInput" class="form-label small fw-semibold text-dark">
                        Tanggal Penjualan <span class="text-danger">*</span>
                    </label>
                    <input type="datetime-local"
                        id="reportDatePickerInput"
                        class="form-control rounded-2"
                        value="{{ now()->format('Y-m-d\TH:i') }}"
                        max="{{ now()->format('Y-m-d\T23:59') }}">
                </div>

                {{-- Alert: tanggal sudah ada --}}
                <div id="datePickerAlertExists" class="alert alert-danger border-danger border-opacity-25 rounded-2 p-3 mb-0 d-none" role="alert">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-octagon-fill text-danger mt-1 flex-shrink-0"></i>
                        <div>
                            <div class="fw-bold mb-1">Laporan sudah dibuat!</div>
                            <div class="small" id="datePickerAlertExistsMsg">
                                Laporan penjualan untuk tanggal ini sudah ada. Satu hari hanya bisa satu laporan.
                                Gunakan tombol <strong>Edit ✏️</strong> untuk mengubah laporan tersebut.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alert: error jaringan / server --}}
                <div id="datePickerAlertError" class="alert alert-warning border-warning border-opacity-25 rounded-2 p-3 mb-0 d-none" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-wifi-off text-warning flex-shrink-0"></i>
                        <div class="small">Gagal memeriksa tanggal. Periksa koneksi internet dan coba lagi.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-light border rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button"
                        class="btn btn-sm btn-success text-white fw-semibold rounded-2 px-4 d-inline-flex align-items-center gap-2"
                        id="btnDatePickerLanjutkan"
                        onclick="validateAndProceedToCreate()">
                    <span id="btnDatePickerSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    <span id="btnDatePickerText"><i class="bi bi-arrow-right me-1"></i> Lanjutkan</span>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Modal Filter -->
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
                <button type="button" class="btn btn-sm btn-light border rounded-2 px-3" id="btnResetFilter" data-bs-dismiss="modal">Reset</button>
                <button type="button" class="btn btn-sm btn-success text-white fw-semibold rounded-2 px-4" id="btnApplyFilter" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
    /* Oper data dari Blade ke JS — HARUS sebelum reports.js dimuat */
    window._reportCheckDateUrl = '{{ route("reports.check_date") }}';
    window._reportCreateUrl    = '{{ route("reports.create") }}';
    window._todayDate          = '{{ now()->format("Y-m-d\TH:i") }}';
    window._todayMax           = '{{ now()->format("Y-m-d\T23:59") }}';
</script>
<script src="{{ asset('js/reports.js') }}"></script>
@endpush