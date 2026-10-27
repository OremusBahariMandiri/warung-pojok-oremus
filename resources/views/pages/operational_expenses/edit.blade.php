@extends('layouts.admin')

@section('title', 'Edit Transaksi Operasional — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/operational_expenses.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('operational-expenses.index') }}" class="text-decoration-none text-muted">transaksi operasional</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">edit pengeluaran</li>
    </ol>
</nav>
@endsection

@section('content')

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Transaksi Operasional</h4>
    </div>
    <a href="{{ route('operational-expenses.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<!-- Display Validation Errors if Any -->
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Memperbarui Pengeluaran Operasional:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('operational-expenses.update', $operationalExpense) }}" method="POST" id="formEditExpense">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <!-- Form Section -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4 shadow-sm">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Detail Pengeluaran Operasional</h6>

                <div class="row g-3 mb-3">
                    <!-- Nama Pengeluaran -->
                    <div class="col-md-12">
                        <label for="name" class="form-label small fw-semibold text-dark">Nama Pengeluaran <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control rounded-2 @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $operationalExpense->name) }}"
                               required
                               autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Jumlah / Quantity -->
                    <div class="col-md-6">
                        <label for="quantity" class="form-label small fw-semibold text-dark">Jumlah <span class="text-danger">*</span></label>
                        <input type="number"
                               class="form-control rounded-2 @error('quantity') is-invalid @enderror"
                               id="quantity"
                               name="quantity"
                               min="1"
                               step="1"
                               value="{{ old('quantity', $operationalExpense->quantity) }}"
                               required>
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Harga Satuan -->
                    <div class="col-md-6">
                        <label for="price_display" class="form-label small fw-semibold text-dark">Harga (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="text"
                                   class="form-control rounded-end-2 @error('price') is-invalid @enderror"
                                   id="price_display"
                                   value="{{ old('price', $operationalExpense->price) ? number_format((float)old('price', $operationalExpense->price), 0, ',', '.') : '' }}"
                                   required>
                            <input type="hidden" name="price" id="price" value="{{ old('price', $operationalExpense->price) }}">
                        </div>
                        @error('price')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tanggal -->
                    <div class="col-md-12">
                        <label for="date" class="form-label small fw-semibold text-dark">Tanggal <span class="text-danger">*</span></label>
                        <input type="date"
                               class="form-control rounded-2 @error('date') is-invalid @enderror"
                               id="date"
                               name="date"
                               value="{{ old('date', \Carbon\Carbon::parse($operationalExpense->date)->format('Y-m-d')) }}"
                               required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Total Biaya (Auto Hitung Preview) -->
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold text-muted">Total Biaya Operasional (Otomatis)</label>
                        <div class="p-2 border rounded-2 bg-light fw-bold font-monospace text-danger fs-6" id="totalPreview">
                            Rp 0
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div class="col-md-12">
                        <label for="notes" class="form-label small fw-semibold text-dark">Keterangan</label>
                        <textarea class="form-control rounded-2 @error('notes') is-invalid @enderror"
                                  id="notes"
                                  name="notes"
                                  rows="4">{{ old('notes', $operationalExpense->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-2 pt-3 mt-4">
                    <a href="{{ route('operational-expenses.index') }}" class="btn btn-md btn-outline-secondary rounded-2 px-3">Batal</a>
                    <button type="submit" class="btn btn-md btn-warning rounded-2 text-dark fw-semibold px-4">Perbarui
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qtyInput = document.getElementById('quantity');
    const priceDisplay = document.getElementById('price_display');
    const priceHidden = document.getElementById('price');
    const totalPreview = document.getElementById('totalPreview');

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function calculateTotal() {
        const qty = parseFloat(qtyInput.value) || 0;
        const rawPrice = priceDisplay.value.replace(/[^0-9]/g, '');
        const price = parseFloat(rawPrice) || 0;
        
        priceHidden.value = price;

        const total = qty * price;
        totalPreview.textContent = 'Rp ' + formatNumber(total);
    }

    priceDisplay.addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, '');
        if (value) {
            this.value = formatNumber(parseInt(value, 10));
        } else {
            this.value = '';
        }
        calculateTotal();
    });

    qtyInput.addEventListener('input', calculateTotal);

    // Initial calculation on load
    calculateTotal();
});
</script>
@endpush
