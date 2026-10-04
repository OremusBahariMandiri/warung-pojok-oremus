@extends('layouts.admin')

@section('title', 'Riwayat Pembelian — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/restock.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">management pembelian</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $list = $restocks ?? collect();
@endphp

<!-- Header Title -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold text-dark mb-1">Riwayat Transaksi Pembelian</h4>
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

<!-- Main Section: Toolbar & DataTables Container -->
<div class="card-box px-3 border rounded-3 overflow-hidden shadow-sm bg-white mb-4">

    <!-- Action & Filter Header -->
    <div class="py-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <!-- Left: Search Box -->
        <div class="position-relative restock-search-box">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" id="dtSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2" placeholder="Cari kode restock, supplier, tanggal...">
        </div>

        <!-- Right: Action & Filter Buttons -->
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-2 px-3 position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterRestock">
                <i class="bi bi-funnel"></i> Filter
                <span id="activeFilterBadge" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                    <span class="visually-hidden">Filter Aktif</span>
                </span>
            </button>
            <a href="{{ route('restock.create') }}" class="btn btn-sm btn-success rounded-2 text-white fw-semibold d-inline-flex align-items-center gap-2 px-3">
                <i class="bi bi-plus-lg"></i> Tambah
            </a>
        </div>
    </div>

    {{-- ═══ DESKTOP: DataTables Table ═══ --}}
    {{--
        Struktur kolom (9 kolom, index 0–8):
         0 = No            → no-sort, responsivePriority 2
         1 = Kode Restock
         2 = Tanggal       → default sort desc
         3 = Supplier      → filter kolom (column(3))
         4 = Status
         5 = Total Item
         6 = Harga Beli    (purchase_price)
         7 = Total Price   (total_price)   → className:'none' → child row
         8 = Dibuat Oleh                  → className:'none' → child row
         9 = Aksi          → no-sort, responsivePriority 1
    --}}
    <div class="d-none d-md-block">
        <table class="table table-bordered table-hover align-middle mb-0 w-100" id="restockDataTable">
            <thead>
                <tr>
                    <th class="text-center" style="width: 46px;">No</th>
                    <th style="width: 155px;">Kode Restock</th>
                    <th class="text-center" style="width: 150px;">Tanggal</th>
                    <th>Supplier</th>
                    <th class="text-center" style="width: 110px;">Status</th>
                    <th class="text-center" style="width: 130px;">Total Item</th>
                    <th class="text-end" style="width: 150px;">Harga Beli</th>
                    <th class="text-end" style="width: 150px;">Harga Total</th>
                    <th class="text-center" style="width: 130px;">Dibuat Oleh</th>
                    <th class="text-center no-sort" style="width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($list as $item)
                    @php
                        $rawDate      = $item->restock_date ? $item->restock_date->format('Y-m-d') : '';
                        $isDraft      = $item->status_restock === 'DRAFT';
                        $totalQty     = $item->items->sum('quantity');
                        $hargaBeli    = (float) $item->items->sum('purchase_price');
                        $totalPrice   = (float) $item->items->sum('total_price');
                    @endphp
                    <tr data-date="{{ $rawDate }}">
                        <td class="text-center text-muted">{{ $loop->iteration }}</td>
                        <td class="font-monospace text-dark">{{ $item->restock_code }}</td>
                        <td class="text-center text-secondary small">
                            {{ $item->restock_date ? $item->restock_date->format('d M Y, H:i') : '-' }}
                        </td>
                        <td>
                            <div class="text-dark">{{ $item->supplier_name }}</div>
                            @if ($item->notes)
                                <div class="text-muted small text-truncate" style="max-width: 220px;">{{ $item->notes }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($isDraft)
                                <span class="badge bg-warning text-dark px-2 py-1">DRAFT</span>
                            @else
                                <span class="badge bg-success text-white px-2 py-1">SELESAI</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="text-dark">{{ $item->items->count() }} Produk</span>
                            <span class="text-muted small d-block">({{ $totalQty }} Unit)</span>
                        </td>
                        <td class="text-end font-monospace text-dark">
                            Rp {{ number_format($hargaBeli, 0, ',', '.') }}
                        </td>
                        <td class="text-end font-monospace fw-semibold text-dark">
                            Rp {{ number_format($totalPrice, 0, ',', '.') }}
                        </td>
                        <td class="text-center text-secondary small">
                            {{ $item->creator->employee_name ?? 'Admin' }}
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1 justify-content-center">
                                <a href="{{ route('restock.show', $item->id) }}" class="btn btn-sm btn-info text-white px-2 py-1 rounded-2 shadow-none" title="Detail" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if ($isDraft)
                                    <a href="{{ route('restock.edit', $item->id) }}" class="btn btn-sm btn-warning text-white px-2 py-1 rounded-2 shadow-none" title="Edit Draft" style="background-color: #f59e0b; border-color: #f59e0b;">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    {{-- <button type="button" class="btn btn-sm btn-danger text-white px-2 py-1 rounded-2 shadow-none" title="Hapus Draft"
                                        onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->restock_code) }}', {{ $totalQty }}, {{ $totalPrice }})">
                                        <i class="bi bi-trash"></i>
                                    </button> --}}
                                @else
                                    <a href="{{ route('restock.edit', $item->id) }}" class="btn btn-sm btn-warning text-white px-2 py-1 rounded-2 shadow-none" title="Edit Transaksi" style="background-color: #f59e0b; border-color: #f59e0b;">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ═══ MOBILE: Card List ═══ --}}
    <div id="restockMobileCards" class="d-md-none px-1 pt-1">
        @forelse ($list as $item)
            @php
                $isDraft    = $item->status_restock === 'DRAFT';
                $totalQty   = $item->items->sum('quantity');
                $hargaBeli  = (float) $item->items->sum('purchase_price');
                $totalPrice = (float) $item->items->sum('total_price');
            @endphp
            <div class="restock-list-card">
                {{-- Header: kode + tanggal --}}
                <div class="rlc-header">
                    <span class="rlc-code">{{ $item->restock_code }}</span>
                    <span class="rlc-date">
                        <i class="bi bi-calendar3 me-1"></i>
                        {{ $item->restock_date ? $item->restock_date->format('d M Y') : '-' }}
                    </span>
                </div>

                {{-- Info bar: supplier | petugas --}}
                <div class="rlc-info-bar">
                    <span class="rlc-supplier">
                        <i class="bi bi-truck me-1 text-muted" style="font-size:0.72rem;"></i>{{ $item->supplier_name }}
                    </span>
                    <span class="rlc-divider"></span>
                    <span class="rlc-officer">
                        <i class="bi bi-person me-1"></i>{{ $item->creator->employee_name ?? 'Admin' }}
                    </span>
                </div>

                {{-- Stats: item + harga beli + total price --}}
                <div class="rlc-stats">
                    <div class="rlc-stat">
                        <div class="rlc-stat-label">Total Item</div>
                        <div class="rlc-stat-value">
                            {{ $item->items->count() }} Produk
                            <span class="rlc-stat-sub">{{ $totalQty }} Unit</span>
                        </div>
                    </div>
                    <div class="rlc-stat">
                        <div class="rlc-stat-label">Harga Beli</div>
                        <div class="rlc-stat-value money">
                            Rp {{ number_format($hargaBeli, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rlc-stat">
                        <div class="rlc-stat-label">Harga Total</div>
                        <div class="rlc-stat-value money">
                            Rp {{ number_format($totalPrice, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="rlc-actions d-flex gap-2">
                    <a href="{{ route('restock.show', $item->id) }}" class="rlc-btn-detail flex-fill text-center">
                        <i class="bi bi-eye"></i> Detail
                    </a>
                    @if ($isDraft)
                        <a href="{{ route('restock.edit', $item->id) }}" class="btn btn-sm btn-warning text-white rounded-2 px-3 d-inline-flex align-items-center gap-1" style="background-color: #f59e0b; border-color: #f59e0b;">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        {{-- <button type="button" class="rlc-btn-delete"
                            onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->restock_code) }}', {{ $totalQty }}, {{ $totalPrice }})"
                            title="Hapus Draft">
                            <i class="bi bi-trash"></i>
                        </button> --}}
                    @else
                        <a href="{{ route('restock.edit', $item->id) }}" class="btn btn-sm btn-warning text-white rounded-2 px-3 d-inline-flex align-items-center gap-1" style="background-color: #f59e0b; border-color: #f59e0b;">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="restock-mobile-list-empty">
                <i class="bi bi-inbox fs-3 d-block mb-2 opacity-40"></i>
                Belum ada riwayat restock. Klik "+ Tambah" untuk mencatat transaksi baru.
            </div>
        @endforelse
    </div>

</div>

<!-- MODAL FILTER RESTOCK -->
<div class="modal fade" id="modalFilterRestock" tabindex="-1" aria-labelledby="modalFilterRestockLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-content-minimal">
            <div class="modal-header modal-header-minimal d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-2"
                        style="width:30px;height:30px;background-color:var(--accent-light,#f0fdf4);flex-shrink:0;">
                        <i class="bi bi-funnel text-success" style="font-size:14px;"></i>
                    </div>
                    <div class="fw-semibold text-dark" style="font-size:0.85rem;" id="modalFilterRestockLabel">Filter Riwayat Restock</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-body-minimal d-flex flex-column gap-3">
                <div>
                    <label for="modalFilterSupplier" class="form-label small fw-semibold text-muted mb-1">Supplier</label>
                    <select id="modalFilterSupplier" class="form-select form-select-sm rounded-2">
                        <option value="">Semua Supplier</option>
                        @foreach ($list->pluck('supplier_name')->unique()->filter() as $supplier)
                            <option value="{{ $supplier }}">{{ $supplier }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="modalFilterStartDate" class="form-label small fw-semibold text-muted mb-1">Tanggal Mulai</label>
                    <input type="date" id="modalFilterStartDate" class="form-control form-control-sm rounded-2">
                </div>
                <div>
                    <label for="modalFilterEndDate" class="form-label small fw-semibold text-muted mb-1">Tanggal Akhir</label>
                    <input type="date" id="modalFilterEndDate" class="form-control form-control-sm rounded-2">
                </div>
            </div>
            <div class="modal-footer border-0 modal-footer-minimal d-flex justify-content-between">
                <button type="button" id="btnResetFilter" class="btn btn-sm btn-light border px-3 rounded-2">Reset</button>
                <button type="button" id="btnApplyFilter" class="btn btn-sm btn-success px-3 rounded-2 text-white" data-bs-dismiss="modal">Terapkan Filter</button>
            </div>
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
                <p class="text-muted small mb-2">Draft transaksi "<span id="deleteRestockCode" class="fw-semibold text-dark"></span>" akan dihapus permanen.</p>
                <form id="deleteRestockForm" method="POST" action="">
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="{{ asset('js/restock.js') }}"></script>
@endpush