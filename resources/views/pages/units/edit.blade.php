@extends('layouts.admin')

@section('title', 'Edit Master Satuan — Warung Pojok Oremus')
@section('page-title', 'Edit Master Satuan')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('units.index') }}">Master Satuan Produk</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Satuan</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $unitObj = $unit;
@endphp

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Satuan</h4>
    </div>
    <a href="{{ route('units.index') }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<!-- Display Validation Errors if Any -->
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Memperbarui Master Satuan:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('units.update', $unitObj->id) }}" method="POST" id="formEditUnit">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <!-- Left Column: Primary Form Fields -->
        <div class="col-lg-12">
            <div class="card-box">

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label for="unit_name" class="form-label small fw-semibold text-dark">Nama Satuan Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3 @error('unit_name') is-invalid @enderror" 
                            id="unit_name" name="unit_name" value="{{ old('unit_name', $unitObj->unit_name) }}" required autofocus>
                        @error('unit_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="short_name" class="form-label small fw-semibold text-dark">Singkatan Satuan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace rounded-3 @error('short_name') is-invalid @enderror" 
                            id="short_name" name="short_name" value="{{ old('short_name', $unitObj->short_name) }}" required>
                        @error('short_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label small fw-semibold text-dark">Kategori / Tipe Satuan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3 @error('type') is-invalid @enderror" 
                        id="type" name="type" value="{{ old('type', $unitObj->type) }}" list="typeSuggestions" required>
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
                <div class="d-flex align-items-center justify-content-between">
                    <a href="{{ route('units.index') }}" class="btn btn-light border rounded-3 px-3">Batal</a>
                    <button type="submit" class="btn btn-success text-white fw-bold px-4 rounded-3 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill"></i> Perbarui
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
