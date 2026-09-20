@extends('layouts.auth')

@section('title', 'Login Admin — Warung Pojok Oremus')

@section('content')
<div class="auth-wrapper container-fluid p-0">
    <div class="row g-0 w-100 min-vh-100">
        <!-- Left Side: Branding & Operational Features -->
        <div class="col-lg-7 col-xl-7 auth-brand-panel d-none d-lg-flex">
            <!-- Brand Header -->
            <div>   
                <h1 class="brand-logo-title mb-2">
                    <span class="brand-logo-icon">
                        <i class="bi bi-shop"></i>
                    </span>
                    WARJOK
                </h1>
                <p class="lead text-white fw-normal mb-5" style="max-width: 540px; font-size: 1.15rem; line-height: 1.6;">
                    Sistem terpadu manajemen inventori stok, kalkulasi HPP presisi, pencatatan restock, dan laporan laba margin operasional warung.
                </p>

                <!-- Feature Highlights Cards -->
                <div class="features-container" style="max-width: 560px;">
                    <div class="feature-card d-flex align-items-center gap-3">
                        <div class="feature-icon-box">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <div>
                            <h6 class="text-white fw-bold mb-1" style="font-size: 0.95rem;">Monitoring Stok & Restock Terintegrasi</h6>
                            <p class="text-white-50 small mb-0" style="font-size: 0.82rem;">Pantau ketersediaan produk dan pencatatan restock otomatis dengan perhitungan harga beli pokok.</p>
                        </div>
                    </div>

                    <div class="feature-card d-flex align-items-center gap-3">
                        <div class="feature-icon-box">
                            <i class="bi bi-calculator"></i>
                        </div>
                        <div>
                            <h6 class="text-white fw-bold mb-1" style="font-size: 0.95rem;">Kalkulasi HPP Presisi & Transparan</h6>
                            <p class="text-white-50 small mb-0" style="font-size: 0.82rem;">Perhitungan otomatis bahan utama + komponen pelengkap (air/es, gula, kemasan, gas) tanpa salah hitung.</p>
                        </div>
                    </div>

                    <div class="feature-card d-flex align-items-center gap-3">
                        <div class="feature-icon-box">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div>
                            <h6 class="text-white fw-bold mb-1" style="font-size: 0.95rem;">Laporan Penjualan & Margin Real-Time</h6>
                            <p class="text-white-50 small mb-0" style="font-size: 0.82rem;">Rekapitulasi omset, beban pokok penjualan, dan margin laba bersih harian maupun rentang periode.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="col-12 col-lg-5 col-xl-5 auth-form-panel">
            <div class="auth-card">
                <!-- Mobile Logo Header (visible on mobile only) -->
                <div class="d-lg-none text-center mb-4">
                    <div class="d-inline-flex align-items-center gap-2 mb-2">
                        <span class="brand-logo-icon" style="width: 38px; height: 38px; font-size: 1.1rem;">
                            <i class="bi bi-shop"></i>
                        </span>
                        <h2 class="h3 fw-bold text-dark mb-0">WARJOK</h2>
                    </div>
                    <p class="text-muted small">Warung Pojok Oremus Admin Portal</p>
                </div>

                <!-- Form Heading -->
                <div class="mb-4">
                    <h2 class="h4 fw-bold text-dark mb-1">Masuk ke Akun</h2>
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
