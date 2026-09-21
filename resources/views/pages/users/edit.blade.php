@extends('layouts.admin')

@section('title', 'Edit Pengguna — ' . ($user->employee_name ?? $user->email) . ' — Warung Pojok Oremus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">warjok</a></li>
        <li class="breadcrumb-item text-muted">pengaturan sistem</li>
        <li class="breadcrumb-item"><a href="{{ route('users.index') }}" class="text-decoration-none text-muted">kelola pengguna</a></li>
        <li class="breadcrumb-item active text-dark" aria-current="page">edit pengguna</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $name = $user->employee_name ?? $user->email;
    $initials = strtoupper(substr(trim($name), 0, 2));
    $isAdmin = (bool)old('is_admin', $user->is_admin);
@endphp

<!-- Header Title -->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Informasi Pengguna</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('users.index')}}" class="btn btn-sm btn-outline-secondary rounded-2 px-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- Session & Validation Alert Messages -->
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-4 rounded-3 border-0 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2 mb-1">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <strong class="fs-sm">Terdapat kesalahan pengisian form:</strong>
    </div>
    <ul class="mb-0 small ps-4">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row g-4 justify-content-center">
    <div class="col-lg-8">
        
        <!-- Form Card -->
        <div class="card-box bg-white border rounded-3 p-4 shadow-sm mb-4">
            
            <div class="d-flex align-items-center gap-3 pb-3 border-bottom mb-4">
                <div class="user-avatar-md {{ $user->is_admin ? 'user-avatar-admin' : 'user-avatar-cashier' }}">
                    {{ $initials }}
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">{{ $name }}</h5>
                    <span class="text-muted small">Terdaftar sejak {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</span>
                </div>
            </div>

            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <!-- NRK -->
                    <div class="col-md-6">
                        <label for="nrk" class="form-label small fw-semibold text-dark">
                            Nomor Registrasi Karyawan (NRK) <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nrk" id="nrk" class="form-control rounded-2 @error('nrk') is-invalid @enderror" value="{{ old('nrk', $user->nrk) }}" placeholder="Contoh: KAS001" required>
                        @error('nrk')
                            <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Employee Name -->
                    <div class="col-md-6">
                        <label for="employee_name" class="form-label small fw-semibold text-dark">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="employee_name" id="employee_name" class="form-control rounded-2 @error('employee_name') is-invalid @enderror" value="{{ old('employee_name', $user->employee_name) }}" placeholder="Nama karyawan" required>
                        @error('employee_name')
                            <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-semibold text-dark">
                            Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email" id="email" class="form-control rounded-2 @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" placeholder="email@domain.com" required>
                        @error('email')
                            <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password (Optional) -->
                    <div class="col-md-6">
                        <label for="password" class="form-label small fw-semibold text-dark">
                            Password Baru <span class="text-muted fw-normal">(Opsional)</span>
                        </label>
                        <input type="password" name="password" id="password" class="form-control rounded-2 @error('password') is-invalid @enderror" placeholder="Kosongkan jika tidak ingin diubah">
                        @error('password')
                            <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Role Switch -->
                    <div class="col-12 mt-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 mb-0">
                                <div>
                                    <label class="form-check-label fw-bold text-dark small" for="is_admin">
                                        Role: Super Administrator
                                    </label>
                                    <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                                        Super Admin memiliki akses tak terbatas ke seluruh modul dan data sistem.
                                    </p>
                                </div>
                                <input class="form-check-input ms-3" type="checkbox" role="switch" name="is_admin" id="is_admin" value="1" {{ $isAdmin ? 'checked' : '' }} style="width: 2.5em; height: 1.25em;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex align-items-center justify-content-between border-top pt-4 mt-4">
                    <a href="{{ route('users.index') }}" class="btn btn-light border px-4 py-2 rounded-2">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-success text-white fw-bold px-4 py-2 rounded-2 d-inline-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-check-circle-fill"></i> Simpan
                    </button>
                </div>

            </form>

        </div>

        <!-- RBAC Shortcut Card -->
        <div class="card-box bg-white border rounded-3 p-4 shadow-sm d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="user-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 42px; height: 42px;">
                    <i class="bi bi-shield-lock-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Konfigurasi Hak Akses Modul</h6>
                    <p class="text-muted small mb-0">Atur izin spesifik (*Lihat, Tambah, Ubah, Detail, Hapus*) per menu sistem.</p>
                </div>
            </div>
            <a href="{{ route('users.access.show', $user->id) }}" class="btn btn-sm btn-outline-primary rounded-2 px-3 d-inline-flex align-items-center gap-1 text-nowrap">
                <i class="bi bi-sliders"></i> Atur Hak Akses
            </a>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/users.js') }}"></script>
@endpush
