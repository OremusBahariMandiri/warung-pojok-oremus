@extends('layouts.admin')

@section('title', 'Tambah Master Satuan Baru — Warung Pojok Oremus')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('units.index') }}" class="text-decoration-none text-muted">management satuan produk</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">tambah satuan</li>
    </ol>
</nav>
@endsection

@section('content')

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Tambah Master Satuan Baru</h4>
        <p class="text-muted small mb-0">Isi formulir di bawah ini untuk menambahkan unit/satuan ukuran produk baru.</p>
    </div>
    <a href="{{ route('units.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<!-- Display Validation Errors if Any -->
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Menyimpan Master Satuan:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('units.store') }}" method="POST" id="formCreateUnit">
    @csrf

    <div class="row g-4">
        <!-- Primary Form Fields -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4">

                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <label for="unit_name" class="form-label small fw-semibold text-dark">Nama Satuan Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-2 @error('unit_name') is-invalid @enderror" 
                            id="unit_name" name="unit_name" value="{{ old('unit_name') }}" autofocus>
                        @error('unit_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="short_name" class="form-label small fw-semibold text-dark">Singkatan Satuan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace rounded-2 @error('short_name') is-invalid @enderror" 
                            id="short_name" name="short_name" value="{{ old('short_name') }}" >
                        @error('short_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label for="type" class="form-label small fw-semibold text-dark">Tipe Satuan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-2 @error('type') is-invalid @enderror" 
                        id="type" name="type" value="{{ old('type') }}" list="typeSuggestions">
                    <datalist id="typeSuggestions">
                        <option value="Kemasan Minuman">
                        <option value="Porsi Makanan">
                        <option value="Satuan Item">
                        <option value="Kemasan Makanan">
                        <option value="Satuan Berat">
                        <option value="Satuan Volume">
                    </datalist>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Submit Card -->
                <div class="d-flex align-items-center justify-content-between pt-3">
                    <a href="{{ route('units.index') }}" class="btn btn-sm btn-light border rounded-2 px-3">Batal</a>
                    <button type="submit" class="btn btn-sm btn-success text-white fw-bold px-4 rounded-2 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
