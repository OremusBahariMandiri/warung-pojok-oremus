@extends('layouts.admin')

@section('title', 'Detail Restock — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/restock.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('restock.index') }}" class="text-decoration-none text-muted">management restock</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">detail restock</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $restockObj = $detailedRestock;
    $itemsList  = $restockObj->items ?? collect();

    /**
     * Helper: ambil harga beli per item dengan banyak fallback nama kolom.
     * Menyesuaikan kemungkinan nama field di RestockItem model.
     */
    function resolveItemPrice($item): float {
        $product = $item->product;
        return (float)(
            $item->price_used       ??
            $item->unit_price       ??
            $item->purchase_price   ??
            $item->price_per_unit   ??
            $item->cost             ??
            $item->price            ??
            ($product?->unit_price  ?? 0)
        );
    }

    function resolveItemSubtotal($item, float $price): float {
        return (float)(
            $item->subtotal         ??
            $item->total_price      ??
            $item->line_total       ??
            ($item->quantity * $price)
        );
    }
@endphp

<!-- Header Title di atas Card -->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Detail Transaksi Restock: {{ $restockObj->restock_code }}</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('restock.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Informasi Transaksi Restock -->
    <div class="col-lg-4">
        <div class="card-box bg-white border rounded-3 p-4 mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Informasi Transaksi</h6>
            <div class="d-flex flex-column gap-3">
                <div>
                    <div class="text-muted small">Kode Restock</div>
                    <div class="fw-semibold font-monospace text-dark fs-6">{{ $restockObj->restock_code }}</div>
                </div>
                {{-- Nomor Invoice disembunyikan --}}
                <div>
                    <div class="text-muted small">Status Transaksi</div>
                    <div>
                        @if ($restockObj->status_restock === 'DRAFT')
                            <span class="badge bg-warning text-dark px-2 py-1">DRAFT</span>
                        @else
                            <span class="badge bg-success text-white px-2 py-1">SELESAI</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-muted small">Supplier / Sumber Barang</div>
                    <div class="fw-semibold text-dark">{{ $restockObj->supplier_name }}</div>
                </div>
                <div>
                    <div class="text-muted small">Tanggal Penerimaan</div>
                    <div class="fw-semibold text-dark">{{ $restockObj->restock_date ? $restockObj->restock_date->format('d M Y, H:i') : '-' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Petugas / Dicatat Oleh</div>
                    <div class="text-dark">{{ $restockObj->creator->employee_name ?? 'Admin' }}</div>
                </div>
                <div class="row g-2 pt-2 border-top">
                    <div class="col-6">
                        <div class="text-muted small">Total Jenis Produk</div>
                        <div class="fw-semibold text-dark">{{ $itemsList->count() }} Produk</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Total Kuantitas Masuk</div>
                        <div class="fw-semibold text-dark">{{ (int)($itemsList->sum('quantity')) }} Unit</div>
                    </div>
                </div>
                <div class="pt-2 border-top">
                    <div class="d-flex justify-content-between fw-bold">
                        <span class="text-dark">Total Price:</span>
                        <span class="font-monospace text-success fs-6">
                            Rp {{ number_format($itemsList->sum('total_price'), 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                <div>
                    <div class="text-muted small">Catatan Restock</div>
                    <div class="text-dark small">{{ $restockObj->notes ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Waktu Pencatatan</div>
                    <div class="text-dark small">{{ $restockObj->created_at ? $restockObj->created_at->format('d M Y, H:i') : '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Daftar Produk Masuk -->
    <div class="col-lg-8">
        <div class="card-box p-0 bg-white border rounded-3 overflow-hidden shadow-sm mb-4">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0">Daftar Produk Masuk</h6>
                <span class="text-muted small">{{ $itemsList->count() }} Produk Terdaftar</span>
            </div>

            {{-- ─── Desktop Table (hidden on mobile) ─── --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-bordered table-hover align-middle mb-0 w-100 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Nama Produk</th>
                            <th class="text-center" style="width: 110px;">Satuan</th>
                            <th class="text-center" style="width: 120px;">Metode HPP</th>
                            <th class="text-center" style="width: 120px;">Jumlah Masuk</th>
                            <th class="text-end" style="width: 160px;">Harga Beli</th>
                            <th class="text-end" style="width: 160px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($itemsList as $item)
                            @php
                                $product    = $item->product;
                                $unitName   = $product && $product->unit
                                                ? ($product->unit->short_name ?: $product->unit->unit_name)
                                                : 'Pcs';
                                $priceUsed  = resolveItemPrice($item);
                                $subtotal   = resolveItemSubtotal($item, $priceUsed);
                                $isAuto     = $product && strtolower($product->hpp_method ?? '') === 'calculated';
                            @endphp
                            <tr>
                                <td class="text-center text-muted fw-medium">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $product->prod_name ?? 'Produk Dihapus' }}</div>
                                </td>
                                <td class="text-center">{{ strtoupper($unitName) }}</td>
                                <td class="text-center">
                                    {{ $isAuto ? 'Otomatis' : 'Manual' }}
                                </td>
                                <td class="text-center fw-bold text-dark font-monospace">+{{ $item->quantity }}</td>
                                <td class="text-end font-monospace text-dark">Rp {{ number_format($priceUsed, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace fw-bold text-dark">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted small">
                                    Tidak ada item produk dalam transaksi restock ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">Total Keseluruhan:</td>
                            <td class="text-center font-monospace">{{ (int)($itemsList->sum('quantity')) }} Unit</td>
                            <td></td>
                            <td class="text-end font-monospace text-dark">
                                @php
                                    $calculatedTotal = $itemsList->sum(fn($item) =>
                                        resolveItemSubtotal($item, resolveItemPrice($item))
                                    );
                                @endphp
                                Rp {{ number_format($calculatedTotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- ─── Mobile Card List (visible only on mobile) ─── --}}
            <div class="d-block d-md-none rdm-wrap">
                @forelse ($itemsList as $item)
                    @php
                        $product    = $item->product;
                        $unitName   = $product && $product->unit
                                        ? ($product->unit->short_name ?: $product->unit->unit_name)
                                        : 'Pcs';
                        $priceUsed  = resolveItemPrice($item);
                        $subtotal   = resolveItemSubtotal($item, $priceUsed);
                        $isAuto     = $product && strtolower($product->hpp_method ?? '') === 'calculated';
                    @endphp
                    <div class="rdm-card {{ !$loop->last ? 'rdm-card-border' : '' }}">
                        {{-- Header: no + product name --}}
                        <div class="rdm-card-header">
                            <span class="rdm-no">{{ $loop->iteration }}</span>
                            <div class="rdm-product">
                                <div class="rdm-product-name">{{ $product->prod_name ?? 'Produk Dihapus' }}</div>
                                <div class="rdm-product-sku">SKU: {{ $product->sku ?? '-' }}</div>
                            </div>
                        </div>
                        {{-- Info row: satuan + hpp --}}
                        <div class="rdm-info-row">
                            <div class="rdm-info-item">
                                <span class="rdm-info-label">Satuan</span>
                                <span class="rdm-info-val">{{ strtoupper($unitName) }}</span>
                            </div>
                            <div class="rdm-info-divider"></div>
                            <div class="rdm-info-item">
                                <span class="rdm-info-label">Metode HPP</span>
                                <span class="rdm-info-val {{ $isAuto ? 'rdm-hpp-auto' : '' }}">{{ $isAuto ? 'Otomatis' : 'Manual' }}</span>
                            </div>
                            <div class="rdm-info-divider"></div>
                            <div class="rdm-info-item">
                                <span class="rdm-info-label">Jumlah Masuk</span>
                                <span class="rdm-info-val rdm-qty">+{{ $item->quantity }}</span>
                            </div>
                        </div>
                        {{-- Price + Subtotal --}}
                        <div class="rdm-amounts">
                            <div class="rdm-amount-item">
                                <span class="rdm-amount-label">Harga Modal / Unit</span>
                                <span class="rdm-amount-val">Rp {{ number_format($priceUsed, 0, ',', '.') }}</span>
                            </div>
                            <div class="rdm-amount-item rdm-subtotal-item">
                                <span class="rdm-amount-label">Subtotal Nilai</span>
                                <span class="rdm-amount-val rdm-subtotal-val">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rdm-empty">
                        Tidak ada item produk dalam transaksi restock ini.
                    </div>
                @endforelse

                {{-- Grand Total footer --}}
                @if ($itemsList->count() > 0)
                <div class="rdm-grand-total">
                    <div class="rdm-gt-row">
                        <span class="rdm-gt-label">Total Unit Masuk</span>
                        <span class="rdm-gt-val font-monospace">{{ (int)($itemsList->sum('quantity')) }} Unit</span>
                    </div>
                    <div class="rdm-gt-row">
                        <span class="rdm-gt-label">Grand Total Nilai</span>
                        <span class="rdm-gt-val rdm-gt-money font-monospace">
                            Rp {{ number_format($restockObj->grand_total ?? $restockObj->subtotal ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                @endif
            </div>
            {{-- /Mobile Card List --}}

        </div>
    </div>
</div>

<!-- MODAL CONFIRM DELETE DRAFT RESTOCK -->
<div class="modal fade" id="modalDeleteRestock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-content-minimal">
            <div class="modal-body modal-body-minimal text-center py-4">
                <div class="avatar-circle mx-auto mb-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center rounded-circle" style="width: 54px; height: 54px; font-size: 1.5rem;">
                    <i class="bi bi-trash"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Hapus Draft Restock?</h6>
                <p class="text-muted small mb-2">Draft transaksi "<span id="deleteRestockCode" class="fw-semibold text-dark">{{ $restockObj->restock_code }}</span>" akan dihapus permanen.</p>
                <form id="deleteRestockForm" method="POST" action="{{ route('restock.destroy', $restockObj->id) }}">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-light border px-3 rounded-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger px-3 rounded-2">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/restock.js') }}"></script>
@endpush
