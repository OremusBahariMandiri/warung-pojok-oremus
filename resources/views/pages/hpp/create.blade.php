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
    </div>
    <a href="{{ route('hpp.index') }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali
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
                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold text-dark">Nama Komponen HPP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3" id="name" name="name" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="unit" class="form-label small fw-semibold text-dark">Satuan Pemakaian <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3 @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required>
                            <option value="" disabled {{ old('unit_id') ? '' : 'selected' }}>Pilih Satuan...</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->unit_name }} ({{ $unit->short_name }})
                                </option>
                            @endforeach
                        </select>
                        @error('unit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="unit_cost" class="form-label small fw-semibold text-dark">Biaya Standar Bawaan (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" class="form-control font-monospace fw-semibold text-end" id="unit_cost" name="unit_cost" value="{{ old('unit_cost') }}" min="0" required>
                        </div>
                    </div>
                </div>
                <!-- Submit Card -->

                <div class="d-flex align-items-center justify-content-between">
                    <a href="{{ route('hpp.index') }}" class="btn btn-light border rounded-3 px-3">Batal</a>
                    <button type="submit" class="btn btn-success text-white fw-bold px-4 rounded-3 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill"></i> Simpan
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>

@endsection
