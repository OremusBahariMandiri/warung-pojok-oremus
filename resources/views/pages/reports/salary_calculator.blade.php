@extends('layouts.admin')

@section('title', 'Kalkulator Gaji Pekerja — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">management laporan</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">kalkulator gaji</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $totalGrossMargin = (float)($summary['total_gross_margin'] ?? 0);
    $totalSales       = (float)($summary['total_sales'] ?? 0);
    $totalHpp         = (float)($summary['total_hpp'] ?? 0);
    $totalQty         = (int)($summary['total_quantity'] ?? 0);
    $totalReports     = (int)($summary['total_reports'] ?? 0);

    // Nilai tersimpan sebelumnya (untuk pre-fill form jika sudah pernah disimpan)
    $savedCommissionType  = $summary['saved_commission_type']  ?? null; // 'persentase' atau 'nominal'
    $savedCommissionValue = $summary['saved_commission_value'] ?? null;
    $savedNotes           = $summary['saved_notes']            ?? null;
@endphp

{{-- Jembatan data PHP → JS --}}
<div id="scData"
     data-base-margin="{{ $totalGrossMargin }}"
     data-total-reports="{{ $totalReports }}"
     data-start-date="{{ $startDate }}"
     data-end-date="{{ $endDate }}"
     data-report-id="{{ $reportId ?? '' }}"
     data-save-url="{{ route('reports.salary_calculator.store') }}"
     data-csrf="{{ csrf_token() }}"
     data-saved-commission-type="{{ $savedCommissionType }}"
     data-saved-commission-value="{{ $savedCommissionValue ?? '' }}"
     data-saved-notes="{{ $savedNotes ?? '' }}"
     hidden></div>

{{-- HEADER --}}
<div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold text-dark mb-1">Kalkulator Gaji</h4>
    </div>
    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2 flex-shrink-0">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

{{-- ════════════ KARTU 1 — RINGKASAN LAPORAN ════════════ --}}
<div class="card border rounded-3 shadow-sm mb-3">
    <div class="card-body p-3 p-md-4">

        <div class="sc-card-label mb-3">
            <i class="bi bi-bar-chart-line text-primary"></i>
            <span>Ringkasan Laporan</span>
            <span class="badge bg-light text-muted border fw-normal ms-auto" style="font-size:.72rem;">
                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                @if($startDate !== $endDate)
                    – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                @endif
            </span>
        </div>

        @if ($totalReports === 0)
            <div class="alert alert-warning d-flex align-items-center gap-2 rounded-3 py-2 px-3 mb-0">
                <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
                <span class="small">Tidak ada data penjualan pada periode ini.</span>
            </div>
        @else
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center bg-light h-100">
                        <div class="text-muted mb-1" style="font-size:.7rem;">Total Laporan</div>
                        <div class="fw-bold fs-5 text-dark">{{ $totalReports }}</div>
                        <div class="text-muted" style="font-size:.7rem;">laporan</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center bg-light h-100">
                        <div class="text-muted mb-1" style="font-size:.7rem;">Produk Terjual</div>
                        <div class="fw-bold fs-5 text-dark">{{ number_format($totalQty) }}</div>
                        <div class="text-muted" style="font-size:.7rem;">unit</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center bg-light h-100">
                        <div class="text-muted mb-1" style="font-size:.7rem;">Total Penjualan</div>
                        <div class="fw-bold fs-5 text-dark font-monospace">Rp {{ number_format($totalSales, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center bg-light h-100">
                        <div class="text-muted mb-1" style="font-size:.7rem;">Total HPP</div>
                        <div class="fw-bold fs-5 text-muted font-monospace">Rp {{ number_format($totalHpp, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="sc-margin-bar d-flex align-items-center justify-content-between rounded-3 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="sc-margin-icon d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <div class="sc-margin-label">TOTAL MARGIN KOTOR (Basis Kalkulasi)</div>
                        <div class="sc-margin-value fw-bold font-monospace">
                            Rp {{ number_format($totalGrossMargin, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <i class="bi bi-currency-dollar sc-margin-deco d-none d-md-block"></i>
            </div>
        @endif

    </div>
</div>

{{-- ════════════ KARTU 2 — KALKULATOR GAJI PEKERJA ════════════ --}}
@if ($totalReports > 0)
<div class="card border rounded-3 shadow-sm mb-3">
    <div class="card-body p-3 p-md-4">

        <div class="sc-card-label mb-3">
            <i class="bi bi-calculator text-primary"></i>
            <span>Kalkulator Gaji Pekerja</span>
            @if($savedCommissionType)
                <span class="badge bg-success-subtle text-success border border-success-subtle fw-normal ms-auto" style="font-size:.72rem;">
                    <i class="bi bi-check-circle me-1"></i>Sudah disimpan
                </span>
            @endif
        </div>

        <div class="row g-3 mb-3">

            {{-- Opsi Komisi — col-12 --}}
            <div class="col-12">
                <label for="komisiOpsi" class="form-label small fw-semibold text-dark mb-1">
                    Opsi Komisi <span class="text-danger">*</span>
                </label>
                <select id="komisiOpsi" class="form-select form-select-sm rounded-2">
                    <option value="persentase" {{ $savedCommissionType === 'persentase' ? 'selected' : '' }}>Persentase (%)</option>
                    <option value="nominal" {{ $savedCommissionType === 'nominal' ? 'selected' : '' }}>Nominal (Rp)</option>
                </select>
            </div>

            {{-- Nilai Komisi — col-12 --}}
            <div class="col-12">
                <label for="komisiNilai" class="form-label small fw-semibold text-dark mb-1">
                    Nilai Komisi <span class="text-danger">*</span>
                    <span id="labelSatuan" class="fw-normal text-muted">(%)</span>
                </label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted rounded-start-2" id="prefixLabel">%</span>
                    <input type="number" id="komisiNilai"
                           class="form-control rounded-end-2"
                           min="0" step="0.01"
                           value="{{ $savedCommissionValue ?? '' }}">
                </div>
            </div>

            {{-- Catatan — col-12 --}}
            <div class="col-12">
                <label for="catatanKomisi" class="form-label small fw-semibold text-dark mb-1">
                    Catatan <span class="fw-normal text-muted">(opsional)</span>
                </label>
                <textarea id="catatanKomisi"
                        class="form-control form-control-sm rounded-2"
                        rows="5"
                        style="resize: vertical;">{{ $savedNotes ?? '' }}</textarea>
            </div>

            {{-- Display: Total Margin Kotor + Rumus Kalkulasi --}}
            <div class="col-6">
                <label class="form-label small fw-semibold text-dark mb-1">Total Margin Kotor (Basis)</label>
                <input type="text" class="form-control form-control-sm rounded-2 bg-light text-dark font-monospace"
                       value="Rp {{ number_format($totalGrossMargin, 0, ',', '.') }}" readonly>
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold text-dark mb-1">Rumus Kalkulasi</label>
                <div class="form-control form-control-sm rounded-2 bg-light text-muted font-monospace small d-flex align-items-center"
                     id="rumusDisplay" style="min-height:31px; overflow:auto; white-space:nowrap;">—</div>
            </div>

        </div>

        {{-- Hasil: GAJI PEKERJA (centered box) --}}
        <div class="sc-result-gaji text-center rounded-3 p-4 mb-3">
            <div class="sc-result-gaji-label mb-2">
                <i class="bi bi-person-heart"></i> GAJI PEKERJA
            </div>
            <div class="sc-result-gaji-val font-monospace fw-bold mb-1" id="hasilGaji">Rp 0</div>
            <div class="sc-result-gaji-sub small" id="hasilGajiSub"></div>
        </div>

        {{-- Margin Bersih --}}
        <div class="d-flex align-items-center justify-content-between border rounded-3 p-3 bg-light mb-3">
            <span class="small fw-semibold text-muted">Margin Bersih</span>
            <span class="font-monospace fw-bold text-dark" id="hasilPemilik">Rp 0</span>
        </div>

        {{-- Footer tombol --}}
        <div class="d-flex align-items-center justify-content-between gap-2 pt-3">
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light border rounded-2 px-3">Batal</a>
            <button type="button" id="btnSimpan"
                    class="btn btn-sm btn-success text-white fw-semibold rounded-2 d-inline-flex align-items-center gap-2 px-3">
                Simpan
            </button>
        </div>

    </div>
</div>
@endif

{{-- MODAL KONFIRMASI SIMPAN --}}
<div class="modal fade" id="modalSimpanKonfirmasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-floppy text-success"></i> Simpan Hasil Kalkulasi Gaji
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3 px-4">
                <p class="text-muted small mb-3">Berikut ringkasan hasil perhitungan yang akan disimpan:</p>
                <div class="border rounded-3 p-3 bg-light" id="simpanSummary"></div>
            </div>
            <div class="modal-footer border-top py-2 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-light border rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-success text-white fw-semibold rounded-2 px-4" id="btnSimpanKonfirm">
                    <i class="bi bi-check-lg me-1"></i> Ya, Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<div id="printArea" class="sc-print-area"></div>

{{-- Toast notifikasi simpan --}}
<div style="position:fixed; top:60px; right:12px; z-index:99999;">
    <div id="scToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="scToastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="{{ asset('js/reports.js') }}"></script>
@endpush