@extends('layouts.admin')

@section('title', 'Edit Komponen HPP — Warung Pojok Oremus')
@section('page-title', 'Edit Komponen HPP')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('hpp.index') }}">Master Komponen HPP</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Komponen</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $hppObj = $hpp;
@endphp

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Komponen</h4>
    </div>
    <a href="{{ route('hpp.index') }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<!-- Display Validation Errors if Any -->
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 py-2 px-3 small" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Memperbarui Komponen HPP:</div>
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ route('hpp.update', $hppObj->id) }}" method="POST" id="formEditHpp">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card-box">
                <h6 class="card-box-title mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-success"></i> Rincian Komponen HPP
                </h6>

                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold text-dark">Nama Komponen HPP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3" id="name" name="name" value="{{ old('name', $hppObj->name) }}" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="unit" class="form-label small fw-semibold text-dark">Satuan Pemakaian <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" id="unit" name="unit" required>
                            <option value="Porsi" {{ old('unit', $hppObj->unit) == 'Porsi' ? 'selected' : '' }}>Porsi</option>
                            <option value="Pcs" {{ old('unit', $hppObj->unit) == 'Pcs' ? 'selected' : '' }}>Pcs</option>
                            <option value="Gram" {{ old('unit', $hppObj->unit) == 'Gram' ? 'selected' : '' }}>Gram</option>
                            <option value="Sendok" {{ old('unit', $hppObj->unit) == 'Sendok' ? 'selected' : '' }}>Sendok</option>
                            <option value="Lembar" {{ old('unit', $hppObj->unit) == 'Lembar' ? 'selected' : '' }}>Lembar</option>
                            <option value="Botol" {{ old('unit', $hppObj->unit) == 'Botol' ? 'selected' : '' }}>Botol</option>
                            <option value="Sachet" {{ old('unit', $hppObj->unit) == 'Sachet' ? 'selected' : '' }}>Sachet</option>
                            <option value="Liter" {{ old('unit', $hppObj->unit) == 'Liter' ? 'selected' : '' }}>Liter</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="unit_cost" class="form-label small fw-semibold text-dark">Biaya Standar Bawaan (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" class="form-control font-monospace fw-semibold text-end" id="unit_cost" name="unit_cost" value="{{ old('unit_cost', (int)$hppObj->unit_cost) }}" min="0" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Card -->
            <div class="card-box bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <a href="{{ route('hpp.index') }}" class="btn btn-light border rounded-3 px-3">Batal</a>
                    <button type="submit" class="btn btn-success text-white fw-bold px-4 rounded-3 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill"></i> Perbarui
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
