@extends('layouts.admin')

@section('title', 'Transaksi Operasional — Warung Pojok Oremus')

@push('styles')
<!-- DataTables BSD & Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/operational_expenses.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item text-muted">transaksi</li>
        <li class="breadcrumb-item active text-dark" aria-current="page">transaksi operasional</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $list = $expenses ?? collect();
@endphp

<!-- Header Title -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold text-dark mb-1">Transaksi Operasional</h4>
    </div>
</div>

<!-- Session Alert Messages -->
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-0 shadow-sm" role="alert" style="background-color: var(--green-50, #f0fdf4); color: var(--green-600, #16a34a);">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div class="fw-medium fs-sm">{{ session('success') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-3 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Terjadi Kesalahan:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Main Table Card -->
<div class="card-box px-3 border rounded-3 overflow-hidden shadow-sm bg-white mb-4">
    <!-- Filter & Search Bar -->
    <div class="py-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div class="position-relative" style="max-width: 350px; width: 100%;">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" id="dtSearchInput" class="form-control form-control-sm ps-5 pe-3 rounded-2" placeholder="Cari nama pengeluaran, keterangan...">
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-2 px-3 position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterExpense">
                <i class="bi bi-funnel"></i> Filter
                <span id="activeFilterBadge" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle d-none">
                    <span class="visually-hidden">Filter Aktif</span>
                </span>
            </button>
            <a href="{{ route('operational-expenses.create') }}" class="btn btn-sm btn-success rounded-2 text-white fw-semibold d-inline-flex align-items-center gap-2 px-3">
                <i class="bi bi-plus-lg"></i> Tambah
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 w-100" id="operationalExpensesTable">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;" class="text-center">NO</th>
                    <th class="text-center" style="width: 140px;">TANGGAL</th>
                    <th>NAMA PENGELUARAN</th>
                    <th class="text-center" style="width: 110px;">JUMLAH</th>
                    <th class="text-end" style="width: 150px;">HARGA SATUAN</th>
                    <th class="text-end" style="width: 150px;">TOTAL BIAYA</th>
                    <th>KETERANGAN</th>
                    <th class="text-center no-sort" style="width: 110px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                @php
                    $totalItemPrice = $item->total_price ?? ($item->quantity * $item->price);
                    $rowDate = $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '';
                @endphp
                <tr data-date="{{ $rowDate }}">
                    <td data-label="NO" class="text-center text-muted fw-medium small">{{ $loop->iteration }}</td>
                    <td data-label="TANGGAL" class="text-center text-dark fw-semibold font-monospace small">
                        {{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}
                    </td>
                    <td data-label="NAMA PENGELUARAN" class="text-dark fw-bold">
                        {{ $item->name }}
                    </td>
                    <td data-label="JUMLAH" class="text-center font-monospace fw-semibold">
                        {{ number_format($item->quantity, 0, ',', '.') }}
                    </td>
                    <td data-label="HARGA SATUAN" class="text-end font-monospace text-muted">
                        Rp {{ number_format($item->price, 0, ',', '.') }}
                    </td>
                    <td data-label="TOTAL BIAYA" class="text-end font-monospace text-danger fw-bold">
                        Rp {{ number_format($totalItemPrice, 0, ',', '.') }}
                    </td>
                    <td data-label="KETERANGAN" class="text-muted small">
                        {{ $item->notes ?: '—' }}
                    </td>
                    <td data-label="AKSI" class="text-center">
                        <div class="d-inline-flex align-items-center gap-1">
                            <a href="{{ route('operational-expenses.edit', $item) }}" class="btn btn-sm btn-outline-warning rounded-2 px-2 py-1" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" 
                                    title="Hapus"
                                    onclick="openDeleteExpenseModal({{ $item->id }}, '{{ $item->name }}', '{{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}', 'Rp {{ number_format($totalItemPrice, 0, ',', '.') }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                {{-- DataTables empty handling --}}
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Filter -->
<div class="modal fade" id="modalFilterExpense" tabindex="-1" aria-labelledby="modalFilterExpenseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark" id="modalFilterExpenseLabel">
                    <i class="bi bi-funnel me-1"></i> Filter Transaksi Operasional
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

<!-- Modal Konfirmasi Hapus Pengeluaran -->
<div class="modal fade" id="modalDeleteExpense" tabindex="-1" aria-labelledby="modalDeleteExpenseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-danger" id="modalDeleteExpenseLabel">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Hapus Transaksi Operasional
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-dark mb-2">
                    Apakah Anda yakin ingin menghapus data pengeluaran <strong id="deleteExpenseName" class="text-dark"></strong> pada tanggal <strong id="deleteExpenseDate" class="text-dark"></strong>?
                </p>
                <div class="bg-light p-2 rounded-2 border text-muted small">
                    Total Biaya: <span id="deleteExpenseTotal" class="fw-bold text-danger font-monospace"></span>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-light border rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                <form id="deleteExpenseForm" action="" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger text-white fw-bold rounded-2 px-3">
                        <i class="bi bi-trash-fill"></i> Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- jQuery, DataTables & Responsive JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
function openDeleteExpenseModal(id, name, date, total) {
    document.getElementById("deleteExpenseName").textContent = name;
    document.getElementById("deleteExpenseDate").textContent = date;
    document.getElementById("deleteExpenseTotal").textContent = total;
    const form = document.getElementById("deleteExpenseForm");
    if (form) form.action = "/operational-expenses/" + id;
    const modalEl = document.getElementById("modalDeleteExpense");
    if (modalEl && typeof bootstrap !== "undefined") {
        new bootstrap.Modal(modalEl).show();
    }
}

$(document).ready(function() {
    const table = $('#operationalExpensesTable').DataTable({
        responsive: true,
        order: [[1, 'desc']],
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Cari data...",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Tidak ada data pengeluaran operasional yang cocok dengan pencarian",
            emptyTable: "Belum ada transaksi operasional yang tercatat.",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "«",
                previous: "‹",
                next: "›",
                last: "»"
            }
        },
        pagingType: "full_numbers",
        columnDefs: [
            { orderable: false, targets: 'no-sort' }
        ],
        dom: '<"table-responsive-wrapper"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>'
    });

    // Custom Search Integration
    $('#dtSearchInput').on('keyup input', function() {
        table.search(this.value).draw();
    });

    // Modal Filter Integration (sama seperti Laporan Penjualan)
    const btnApplyFilter = document.getElementById("btnApplyFilter");
    const btnResetFilter = document.getElementById("btnResetFilter");

    btnApplyFilter?.addEventListener("click", function() {
        const start = document.getElementById("modalFilterStartDate")?.value || "";
        const end   = document.getElementById("modalFilterEndDate")?.value || "";

        const hasFilter = start || end;
        document.getElementById("activeFilterBadge")?.classList.toggle("d-none", !hasFilter);

        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                if (settings.nTable.id !== "operationalExpensesTable") return true;
                const row = $(settings.nTable).find("tbody tr").eq(dataIndex);
                const rowDate = row.data("date") || "";
                if (start && rowDate < start) return false;
                if (end && rowDate > end) return false;
                return true;
            }
        );
        table.draw();
        $.fn.dataTable.ext.search.pop();
    });

    btnResetFilter?.addEventListener("click", function() {
        const si = document.getElementById("modalFilterStartDate");
        const ei = document.getElementById("modalFilterEndDate");
        if (si) si.value = "";
        if (ei) ei.value = "";
        document.getElementById("activeFilterBadge")?.classList.add("d-none");
        table.draw();
    });
});
</script>
@endpush
