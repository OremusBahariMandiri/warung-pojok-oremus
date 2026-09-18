@extends('layouts.admin')

@section('title', 'Master Komponen HPP — Warung Pojok Oremus')
@section('page-title', 'Master Komponen HPP')

@push('styles')
<!-- DataTables BSD & Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Master Komponen HPP</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $list = $hppList ?? collect();
    $totalCount = $list->count();
    $avgCost = $totalCount > 0 ? round($list->avg('unit_cost'), 0) : 0;
    
    // Find most used component
    $mostUsed = $list->sortByDesc('products_count')->first();
    $unusedCount = $list->filter(fn($item) => (int)($item->products_count ?? 0) === 0)->count();
@endphp

<!-- Session Alert Messages -->
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-4 rounded-3 border-0 shadow-sm" role="alert" style="background-color: var(--green-50); color: var(--green-600);">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('success') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-4 rounded-3 border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('error') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Main Section: Toolbar & DataTables Container -->
<div class="card-box">
    <!-- Action & Filter Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <!-- Left: Search Input -->
        <div class="d-flex flex-wrap align-items-center gap-2 grow">
            <div class="position-relative grow" style="max-width: 320px;">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="dtSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-3" placeholder="Cari nama komponen / satuan..." style="border-color: var(--border);">
            </div>
        </div>

        <!-- Right: Action Buttons -->
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('hpp.create') }}" class="btn btn-sm btn-success rounded-3 text-white fw-semibold d-inline-flex align-items-center gap-2 px-3">
                <i class="bi bi-plus-circle-fill"></i> Tambah
            </a>
        </div>
    </div>

    <!-- DataTables Table -->
    <div class="table-responsive">
        <table class="table table-products align-middle mb-0 w-100" id="hppDataTable">
            <thead>
                <tr>
                    <th style="width: 50px;">
                        No
                    </th>
                    <th>Nama Komponen HPP</th>
                    <th>Satuan Pemakaian</th>
                    <th class="text-end">Biaya Standar Bawaan (Unit Cost)</th>
                    <th class="text-center">Produk Terkait</th>
                    <th class="text-center">Tanggal Dibuat</th>
                    <th class="text-end no-sort" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($list as $item)
                    <tr>
                        <td>
                            {{ $loop->iteration }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="product-thumb-placeholder text-success" style="background: var(--green-50);">
                                    <i class="bi bi-calculator"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">{{ $item->name }}</div>
                                    <div class="text-muted small">ID Master: <span class="font-monospace text-secondary">#HPP-{{ sprintf('%03d', $item->id) }}</span></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-soft-secondary font-monospace px-2.5 py-1">{{ strtoupper($item->unit) }}</span>
                        </td>
                        <td class="text-end fw-bold font-monospace text-dark">
                            Rp {{ number_format($item->unit_cost, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @if ((int)($item->products_count ?? 0) > 0)
                                <span class="badge badge-soft-success px-2.5 py-1 rounded-pill">
                                    <i class="bi bi-check-circle-fill me-1"></i>{{ $item->products_count }} Produk
                                </span>
                            @else
                                <span class="badge badge-soft-secondary px-2.5 py-1 rounded-pill">
                                    0 Produk
                                </span>
                            @endif
                        </td>
                        <td class="text-center text-muted small">
                            {{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}
                        </td>
                        <td class="text-end">
                            <!-- Desktop Action Buttons -->
                            <div class="d-none d-md-inline-flex gap-1">
                                <a href="{{ route('hpp.show', $item->id) }}" class="action-btn action-btn-primary" title="Rincian Komponen">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('hpp.edit', $item->id) }}" class="action-btn" title="Edit Komponen">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="action-btn action-btn-danger" title="Hapus Komponen" onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->name) }}', {{ (int)($item->products_count ?? 0) }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            <!-- Mobile Action Dropdown -->
                            <div class="dropdown d-inline-block d-md-none">
                                <button class="dropdown-action-btn dropdown-toggle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Aksi
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 py-1">
                                    <li>
                                        <a class="dropdown-item py-2 small" href="{{ route('hpp.show', $item->id) }}">
                                            <i class="bi bi-eye text-primary me-2"></i> Rincian Komponen
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 small" href="{{ route('hpp.edit', $item->id) }}">
                                            <i class="bi bi-pencil text-secondary me-2"></i> Edit Komponen
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <button type="button" class="dropdown-item py-2 small text-danger" onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->name) }}', {{ (int)($item->products_count ?? 0) }})">
                                            <i class="bi bi-trash me-2"></i> Hapus Komponen
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CONFIRM DELETE HPP -->
<div class="modal fade" id="modalDeleteHpp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-content-minimal">
            <div class="modal-body modal-body-minimal text-center py-4">
                <div class="avatar-circle mx-auto mb-3 bg-danger bg-opacity-10 text-danger" style="width: 54px; height: 54px; font-size: 1.5rem;">
                    <i class="bi bi-trash"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Hapus Komponen HPP?</h6>
                <p class="text-muted small mb-2">Komponen "<span id="deleteHppName" class="fw-semibold text-dark"></span>" akan dihapus dari master data.</p>
                <div id="deleteWarningProductCount" class="alert alert-warning py-1 px-2 small mb-3 d-none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Digunakan oleh <span id="warningProductCount" class="fw-bold"></span> produk.
                </div>
                
                <form id="deleteHppForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-light border px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger px-3 rounded-3">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- jQuery & DataTables JS BSD CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        // Initialize DataTables
        const dataTable = $('#hppDataTable').DataTable({
            responsive: true,
            columnDefs: [
                { targets: 'no-sort', orderable: false }
            ],
            language: {
                emptyTable: "Belum ada master komponen HPP di database. Klik tombol 'Tambah Komponen HPP' untuk membuat baru.",
                zeroRecords: "Tidak ada komponen HPP yang cocok dengan pencarian",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ komponen",
                infoEmpty: "Menampilkan 0 komponen",
                infoFiltered: "(difilter dari _MAX_ total komponen)",
                paginate: {
                    previous: "Sebelumnya",
                    next: "Selanjutnya"
                }
            },
            dom: 'rt<"d-flex flex-column flex-sm-row align-items-center justify-content-between pt-3 mt-3 border-top border-light-subtle gap-2"ip>',
            pageLength: 10
        });

        // Custom Search Input binding
        $('#dtSearchInput').on('keyup input', function () {
            dataTable.search(this.value).draw();
        });

        // Select All Checkbox Handler
        $('#selectAll').on('change', function () {
            $('.row-checkbox').prop('checked', this.checked);
        });
    });

    function openDeleteModal(id, name, productCount) {
        document.getElementById('deleteHppName').textContent = name;
        document.getElementById('deleteHppForm').action = "/hpp/" + id;
        
        const warningBox = document.getElementById('deleteWarningProductCount');
        const countSpan = document.getElementById('warningProductCount');
        if (productCount > 0) {
            countSpan.textContent = productCount;
            warningBox.classList.remove('d-none');
        } else {
            warningBox.classList.add('d-none');
        }

        const modal = new bootstrap.Modal(document.getElementById('modalDeleteHpp'));
        modal.show();
    }
</script>
@endpush
