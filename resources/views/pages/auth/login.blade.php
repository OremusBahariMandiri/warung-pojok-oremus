@extends('layouts.auth')

@section('title', 'Login Admin — Warung Pojok Oremus')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="auth-wrapper container-fluid p-0">
    <!-- Mobile Background Icons (hidden on desktop) -->
    <div class="auth-mobile-icons d-lg-none">
        <span><i class="bi bi-cup-hot"></i></span>
        <span><i class="bi bi-basket2"></i></span>
        <span><i class="bi bi-box-seam"></i></span>
        <span><i class="bi bi-graph-up-arrow"></i></span>
        <span><i class="bi bi-receipt"></i></span>
        <span><i class="bi bi-bag-check"></i></span>
        <span><i class="bi bi-calculator"></i></span>
        <span><i class="bi bi-boxes"></i></span>
        <span><i class="bi bi-shop"></i></span>
        <span><i class="bi bi-cash-coin"></i></span>
    </div>
    <div class="row g-0 w-100 min-vh-100">
        <!-- Left Side: Branding & Operational Features -->
        <div class="col-lg-7 col-xl-7 auth-brand-panel d-none d-lg-flex" style="position: relative; overflow: hidden;">
            <div class="brand-content-center">
                <h1 class="brand-heading">
                    <span class="brand-heading-main">Sistem Informasi Manajemen</span>
                    <span class="brand-heading-accent">Warung Pojok Oremus</span>
                </h1>
                <p class="brand-subtitle">
                    Kelola inventori stok, kalkulasi HPP, pencatatan restock, dan pantau laporan laba margin operasional warung secara digital, cepat, dan transparan.
                </p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="col-12 col-lg-5 col-xl-5 auth-form-panel">
            <div class="auth-card">
                <!-- Form Heading -->
                <div class="mb-4">
                    <h2 class="h4 fw-bold text-dark mb-1">Selamat Datang!</h2>
                    <p class="text-muted small mb-0">Gunakan Nomor Registrasi Karyawan (NRK) dan password Anda untuk masuk.</p>
                </div>

                <!-- Session / Validation Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 small mb-4" role="alert">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <div>{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 small mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                        <div>{{ session('error') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-4" role="alert">
                        <div class="d-flex align-items-center gap-2 mb-1 fw-bold">
                            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                            <span>Gagal Masuk</span>
                        </div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Login Form -->
                <form action="{{ route('login.post') }}" method="POST" id="formLogin">
                    @csrf

                    <!-- NRK Field -->
                    <div class="mb-3">
                        <label for="nrk" class="form-label small fw-semibold text-dark mb-1">Nomor Registrasi Karyawan (NRK)</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person-badge"></i>
                            </span>
                            <input 
                                type="text" 
                                class="form-control @error('nrk') is-invalid @enderror" 
                                id="nrk" 
                                name="nrk" 
                                value="{{ old('nrk') }}" 
                                placeholder="Contoh: ADM001 / KAS001" 
                                autocomplete="username"
                                required 
                                autofocus
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold text-dark mb-1">Password Akun</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input 
                                type="password" 
                                class="form-control @error('password') is-invalid @enderror" 
                                id="password" 
                                name="password" 
                                placeholder="Masukkan password Anda" 
                                autocomplete="current-password"
                                required
                            >
                            <button class="btn btn-outline-secondary btn-password-toggle" type="button" id="togglePassword" title="Lihat/Sembunyikan Password">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small text-muted" for="remember">
                                Remember Me
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary-auth w-100 py-2" id="btnSubmit">
                        <i class="bi bi-box-arrow-in-right fs-5" id="btnSubmitIcon"></i>
                        <span id="btnSubmitText">Masuk ke Dashboard</span>
                    </button>
                </form>

                <!-- Footer Copyright -->
                <div class="text-center mt-4 pt-3 border-top border-light-subtle">
                    <p class="text-muted mb-0" style="font-size: 0.78rem;">
                        &copy; {{ date('Y') }} PT Oremus Bahari Mandiri. Seluruh hak cipta dilindungi undang-undang.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/login.js') }}"></script>
@endpush
