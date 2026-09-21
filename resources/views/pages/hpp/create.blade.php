@extends('layouts.admin')

@section('title', 'Tambah Komponen HPP Baru — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/hpp.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item"><a href="{{ route('hpp.index') }}" class="text-decoration-none text-muted">management master hpp</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">tambah komponen</li>
    </ol>
</nav>
@endsection

@section('content')

<!-- Header Back Bar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Tambah Komponen HPP Baru</h4>
    </div>
    <a href="{{ route('hpp.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
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
        <!-- Primary Form Fields -->
        <div class="col-lg-12">
            <div class="card-box bg-white border rounded-3 p-4">

                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold text-dark">Nama Komponen HPP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-2 @error('name') is-invalid @enderror" 
                        id="name" name="name" value="{{ old('name') }}" autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="unit" class="form-label small fw-semibold text-dark">Satuan Pemakaian <span class="text-danger">*</span></label>

                        {{-- Hidden select asli untuk submit form --}}
                        <select name="unit" id="unit" class="d-none @error('unit') is-invalid @enderror">
                            <option value="" {{ old('unit') ? '' : 'selected' }}></option>
                            @if(isset($units) && $units->count() > 0)
                                @foreach($units as $unitItem)
                                    <option value="{{ $unitItem->short_name }}" {{ old('unit') == $unitItem->short_name ? 'selected' : '' }}>
                                        {{ $unitItem->unit_name }} ({{ $unitItem->short_name }})
                                    </option>
                                @endforeach
                            @endif
                        </select>

                        {{-- Custom searchable dropdown --}}
                        <div class="searchable-select-wrapper" id="unitDropdownWrapper">
                            <div class="searchable-select-trigger @error('unit') is-invalid @enderror"
                                id="unitTrigger" onclick="toggleSearchableSelect(this, event)" tabindex="0" role="combobox">
                                @php
                                    $selectedUnit = old('unit');
                                    $selectedLabel = '';
                                    if ($selectedUnit && isset($units) && $units->count() > 0) {
                                        $found = $units->firstWhere('short_name', $selectedUnit);
                                        $selectedLabel = $found ? $found->unit_name . ' (' . $found->short_name . ')' : $selectedUnit;
                                    }
                                @endphp
                                @if($selectedLabel)
                                    <span class="selected-text">{{ $selectedLabel }}</span>
                                @else
                                    <span class="placeholder-text">Pilih Satuan...</span>
                                @endif
                            </div>

                            <div class="searchable-select-dropdown" id="unitDropdown">
                                <div class="searchable-select-search-wrap">
                                    <input type="text" class="searchable-select-search"
                                        id="unitSearch" oninput="filterSearchableOptions(this)" placeholder="Cari satuan..." autocomplete="off">
                                </div>
                                <div class="searchable-select-options" id="unitOptions">
                                    @if(isset($units) && $units->count() > 0)
                                        @foreach($units as $unitItem)
                                            <div class="searchable-select-option {{ old('unit') == $unitItem->short_name ? 'selected' : '' }}"
                                                data-value="{{ $unitItem->short_name }}"
                                                data-label="{{ $unitItem->unit_name }} ({{ $unitItem->short_name }})"
                                                onclick="selectSearchableOption(this)">
                                                {{ $unitItem->unit_name }} ({{ $unitItem->short_name }})
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="searchable-select-no-results text-muted p-2 small text-center">
                                            Belum ada data satuan
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @error('unit')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="unit_cost" class="form-label small fw-semibold text-dark">Biaya Standar Bawaan (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-2">Rp</span>
                            <input type="number" class="form-control font-monospace fw-semibold text-end rounded-end-2 @error('unit_cost') is-invalid @enderror" 
                                id="unit_cost" name="unit_cost" value="{{ old('unit_cost') }}" min="0">
                            @error('unit_cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Submit Card -->
                <div class="d-flex align-items-center justify-content-between pt-3">
                    <a href="{{ route('hpp.index') }}" class="btn btn-sm btn-light border rounded-2 px-3">Batal</a>
                    <button type="submit" class="btn btn-sm btn-success text-white fw-bold px-4 rounded-2 d-inline-flex align-items-center gap-2">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

{{-- Library Select2 --}}
@push('scripts')
<script src="{{ asset('js/hpp.js') }}"></script>
@endpush
