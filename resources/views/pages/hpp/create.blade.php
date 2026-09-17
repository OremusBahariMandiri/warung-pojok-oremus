@extends('layouts.admin')

@section('title', 'Tambah Komponen HPP Baru — Warung Pojok Oremus')
@section('page-title', 'Tambah Komponen HPP')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('hpp.index') }}">Master Komponen HPP</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tambah Komponen</li>
    </ol>
</nav>
@endsection

@section('content')

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Tambah Komponen HPP Baru</h4>
        <p class="text-muted small mb-0">Daftarkan komponen master HPP seperti air, es, gula, kemasan, atau gas yang akan digunakan pada kalkulasi produk.</p>
    </div>
    <a href="{{ route('hpp.index') }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
    </a>
</div>

<!-- Display Validation Errors if Any -->
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Menyimpan Komponen HPP:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('hpp.store') }}" method="POST" id="formCreateHpp">
    @csrf

    <div class="row g-4">
        <!-- Left Column: Primary Form Fields -->
        <div class="col-lg-12">
            <div class="card-box">
                <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-success"></i> Rincian Komponen HPP
                </h6>

                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold text-dark">Nama Komponen HPP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3" id="name" name="name" value="{{ old('name') }}" required autofocus>
                    <div class="form-text text-muted small">Nama bahan pembantu atau komponen operasional pelengkap menu.</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="unit" class="form-label small fw-semibold text-dark">Satuan Pemakaian <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" id="unit" name="unit" required>
                            <option value="Porsi" {{ old('unit', 'Porsi') == 'Porsi' ? 'selected' : '' }}>Porsi</option>
                            <option value="Pcs" {{ old('unit') == 'Pcs' ? 'selected' : '' }}>Pcs</option>
                            <option value="Gram" {{ old('unit') == 'Gram' ? 'selected' : '' }}>Gram</option>
                            <option value="Sendok" {{ old('unit') == 'Sendok' ? 'selected' : '' }}>Sendok</option>
                            <option value="Lembar" {{ old('unit') == 'Lembar' ? 'selected' : '' }}>Lembar</option>
                            <option value="Botol" {{ old('unit') == 'Botol' ? 'selected' : '' }}>Botol</option>
                            <option value="Sachet" {{ old('unit') == 'Sachet' ? 'selected' : '' }}>Sachet</option>
                            <option value="Liter" {{ old('unit') == 'Liter' ? 'selected' : '' }}>Liter</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="unit_cost" class="form-label small fw-semibold text-dark">Biaya Standar Bawaan (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" class="form-control font-monospace fw-semibold text-end" id="unit_cost" name="unit_cost" value="{{ old('unit_cost', 0) }}" placeholder="0" min="0" required>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded-3 bg-light border border-light-subtle">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-lightbulb text-warning fs-5 shrink-0 mt-0.5"></i>
                        <div class="small text-muted">
                            <span class="fw-bold text-dark">Tips Penggunaan:</span> Biaya standar bawaan ini akan menjadi nominal acuan otomatis saat komponen dipilih pada form resep produk (<span class="font-monospace text-dark">product_hpp</span>), namun biayanya tetap dapat disesuaikan per masing-masing menu.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Card -->
            <div class="card-box bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <a href="{{ route('hpp.index') }}" class="btn btn-light border rounded-3 px-3">Batal</a>
                    <button type="submit" class="btn btn-success text-white fw-bold px-4 rounded-3 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill"></i> Simpan Komponen HPP
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>

@endsection
