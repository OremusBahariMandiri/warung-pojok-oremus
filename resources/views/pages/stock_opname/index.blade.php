@extends('layouts.admin')

@section('title', 'Pemeriksaan Stok Opname — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/stock_opname.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('stock-opname.index') }}" class="text-decoration-none text-muted">inventori</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">stock opname</li>
    </ol>
</nav>
@endsection

@section('content')

<!-- Header Section -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Pemeriksaan Stok Opname</h4>
    </div>
    <a href="{{ route('stock-opname.create') }}" class="btn btn-primary rounded-2 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-plus-lg"></i> Catat Stock Opname
    </a>
</div>

<!-- Flash Message -->
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>{{ session('success') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>{{ session('error') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Main Card -->
<div class="card-box bg-white border rounded-3 p-4">

    <!-- Toolbar: Search + Filter Button -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1">
            <!-- Search (hanya desktop, DataTables handle di desktop) -->
            <div class="input-group input-group-sm opname-search-box d-none d-md-flex">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="opnameSearchInput" class="form-control border-start-0" placeholder="Cari kode opname atau catatan...">
            </div>

            <!-- Active Filter Badges -->
            @if(request()->hasAny(['search', 'status_opname', 'start_date']))
                <div class="d-flex flex-wrap gap-1 align-items-center">
                    @if(request('status_opname'))
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                            Status: {{ request('status_opname') }}
                            <a href="{{ route('stock-opname.index', array_merge(request()->except('status_opname'), [])) }}" class="ms-1 text-primary text-decoration-none">&times;</a>
                        </span>
                    @endif
                    @if(request('start_date'))
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                            Dari: {{ request('start_date') }}
                            <a href="{{ route('stock-opname.index', array_merge(request()->except('start_date'), [])) }}" class="ms-1 text-primary text-decoration-none">&times;</a>
                        </span>
                    @endif
                    <a href="{{ route('stock-opname.index') }}" class="btn btn-sm btn-link text-danger p-0 small">Reset semua</a>
                </div>
            @endif
        </div>

        <div class="d-flex gap-2">
            <!-- Mobile Search -->
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-flex d-md-none align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#opnameMobileSearchModal">
                <i class="bi bi-search"></i>
            </button>
            <!-- Filter Button -->
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#opnameFilterModal">
                <i class="bi bi-funnel"></i>
                <span>Filter</span>
                @if(request()->hasAny(['status_opname', 'start_date']))
                    <span class="badge bg-primary rounded-pill ms-1" style="font-size:0.65rem;">
                        {{ collect(['status_opname', 'start_date'])->filter(fn($k) => request($k))->count() }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════ -->
    <!-- DESKTOP TABLE (d-none d-md-block)               -->
    <!-- ════════════════════════════════════════════════ -->
    <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered table-hover align-middle mb-0" id="opnameDataTable">
            <thead>
                <tr>
                    <th class="text-center" style="width:5%;">No</th>
                    <th style="width:18%;">Kode Opname</th>
                    <th style="width:18%;">Tanggal Pemeriksaan</th>
                    <th style="width:18%;">Petugas</th>
                    <th class="text-center" style="width:13%;">Total Produk</th>
                    <th class="text-center" style="width:13%;">Status</th>
                    <th class="text-center" style="width:15%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($opnames as $opname)
                    @php $status = strtoupper($opname->status_opname); @endphp
                    <tr>
                        <td class="text-center fw-semibold text-secondary">{{ $loop->iteration }}</td>
                        <td>
                            <span class="font-monospace fw-bold text-navy">{{ $opname->opname_code }}</span>
                            @if ($opname->notes)
                                <div class="text-muted small text-truncate" style="max-width:200px;" title="{{ $opname->notes }}">{{ $opname->notes }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="text-dark">{{ $opname->opname_date ? $opname->opname_date->format('d M Y') : '-' }}</span>
                            <div class="text-muted small">{{ $opname->opname_date ? $opname->opname_date->format('H:i') : '' }} WIB</div>
                        </td>
                        <td>
                            <span class="fw-semibold text-dark">{{ $opname->creator->employee_name ?? 'Administrator' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1">{{ $opname->items->count() }} Produk</span>
                        </td>
                        <td class="text-center">
                            @if ($status === 'COMPLETED' || $status === 'CONFIRMED')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-check-circle-fill me-1"></i> Selesai
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                    <i class="bi bi-clock-fill me-1"></i> Draft
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('stock-opname.show', $opname->id) }}" class="btn btn-sm btn-outline-info rounded-2 py-1 px-2" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if ($status === 'DRAFT')
                                    <a href="{{ route('stock-opname.edit', $opname->id) }}" class="btn btn-sm btn-outline-warning rounded-2 py-1 px-2 text-warning-emphasis" title="Edit Opname">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-2 py-1 px-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteOpnameModal{{ $opname->id }}"
                                    title="Hapus Transaksi">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            Belum ada riwayat transaksi Stock Opname.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ════════════════════════════════════════════════ -->
    <!-- MOBILE CARDS (d-block d-md-none)                -->
    <!-- ════════════════════════════════════════════════ -->
    <div class="d-block d-md-none" id="opnameMobileCards">

        <!-- Mobile Search Bar -->
        <div class="input-group input-group-sm mb-3">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" id="opnameMobileSearch" class="form-control border-start-0" placeholder="Cari kode opname atau catatan...">
        </div>

        @forelse ($opnames as $opname)
            @php $status = strtoupper($opname->status_opname); @endphp
            <div class="opname-mobile-card mb-3"
                data-search="{{ strtolower($opname->opname_code . ' ' . $opname->notes . ' ' . ($opname->creator->employee_name ?? 'Administrator')) }}"
                data-status="{{ $status }}">
                <!-- Card Header -->
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div>
                        <span class="font-monospace fw-bold text-dark">{{ $opname->opname_code }}</span>
                        <div class="text-muted small mt-1">
                            <i class="bi bi-person-fill me-1"></i>{{ $opname->creator->employee_name ?? 'Administrator' }}
                        </div>
                    </div>
                    @if ($status === 'COMPLETED' || $status === 'CONFIRMED')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 flex-shrink-0">
                            <i class="bi bi-check-circle-fill me-1"></i> Selesai
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 flex-shrink-0">
                            <i class="bi bi-clock-fill me-1"></i> Draft
                        </span>
                    @endif
                </div>

                <!-- Card Info Row -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <div class="opname-mobile-info-chip">
                        <i class="bi bi-calendar3 text-muted me-1"></i>
                        <span>{{ $opname->opname_date ? $opname->opname_date->format('d M Y') : '-' }}</span>
                    </div>
                    <div class="opname-mobile-info-chip">
                        <i class="bi bi-box-seam text-muted me-1"></i>
                        <span>{{ $opname->items->count() }} Produk</span>
                    </div>
                    @if($opname->notes)
                        <div class="opname-mobile-info-chip w-100 text-truncate" title="{{ $opname->notes }}">
                            <i class="bi bi-chat-left-text text-muted me-1"></i>
                            <span>{{ $opname->notes }}</span>
                        </div>
                    @endif
                </div>

                <!-- Card Actions -->
                <div class="d-flex gap-2">
                    <a href="{{ route('stock-opname.show', $opname->id) }}" class="btn btn-sm btn-outline-info rounded-2 flex-fill">
                        <i class="bi bi-eye me-1"></i> Detail
                    </a>
                    @if ($status === 'DRAFT')
                        <a href="{{ route('stock-opname.edit', $opname->id) }}" class="btn btn-sm btn-outline-warning rounded-2 flex-fill text-warning-emphasis">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-2 flex-fill"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteOpnameModal{{ $opname->id }}">
                        <i class="bi bi-trash3 me-1"></i> Hapus
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-5 text-muted" id="opnameMobileEmpty">
                <i class="bi bi-clipboard2-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                Belum ada riwayat transaksi Stock Opname.
            </div>
        @endforelse

        <!-- No result for search -->
        <div class="text-center py-4 text-muted d-none" id="opnameMobileNoResult">
            <i class="bi bi-search fs-2 d-block mb-2 text-secondary opacity-50"></i>
            Tidak ada hasil yang ditemukan.
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- DELETE CONFIRMATION MODALS                                  -->
<!-- ════════════════════════════════════════════════════════════ -->
@foreach ($opnames as $opname)
<div class="modal fade" id="deleteOpnameModal{{ $opname->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i> Konfirmasi Hapus Opname
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start py-4">
                <p class="text-secondary mb-2">
                    Apakah Anda yakin ingin menghapus catatan Stock Opname <strong>{{ $opname->opname_code }}</strong>?
                </p>
                <p class="text-muted small mb-0">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('stock-opname.destroy', $opname->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded-2 px-4">
                        <i class="bi bi-trash3 me-1"></i> Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- ════════════════════════════════════════════════════════════ -->
<!-- FILTER MODAL                                                -->
<!-- ════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="opnameFilterModal" tabindex="-1" aria-labelledby="opnameFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="opnameFilterModalLabel">
                    <i class="bi bi-funnel-fill text-primary fs-5"></i> Filter Stock Opname
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('stock-opname.index') }}" method="GET">
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <!-- Status -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary text-uppercase" style="letter-spacing:.04em;">Status Opname</label>
                            <select name="status_opname" class="form-select form-select-sm rounded-2">
                                <option value="">-- Semua Status --</option>
                                <option value="DRAFT" {{ request('status_opname') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                                <option value="COMPLETED" {{ request('status_opname') === 'COMPLETED' ? 'selected' : '' }}>Selesai (Completed)</option>
                            </select>
                        </div>
                        <!-- Dari Tanggal -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary text-uppercase" style="letter-spacing:.04em;">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control form-control-sm rounded-2" value="{{ request('start_date') }}">
                        </div>
                        <!-- Sampai Tanggal -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary text-uppercase" style="letter-spacing:.04em;">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control form-control-sm rounded-2" value="{{ request('end_date') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 justify-content-between">
                    @if(request()->hasAny(['status_opname', 'start_date', 'end_date']))
                        <a href="{{ route('stock-opname.index') }}" class="btn btn-sm btn-link text-danger text-decoration-none px-0">
                            <i class="bi bi-x-circle me-1"></i>Reset Filter
                        </a>
                    @else
                        <span></span>
                    @endif
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-2 px-4">
                            <i class="bi bi-funnel me-1"></i> Terapkan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="{{ asset('js/stock_opname.js') }}"></script>
@endpush