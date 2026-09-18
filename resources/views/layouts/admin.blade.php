<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - WARJOK</title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body>

    <!-- Mobile Overlay -->
    <div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="#" class="brand-link text-decoration-none">
                <i class="bi bi-shop brand-icon"></i>
                <div class="brand-text">
                    <h3 class="mb-0 fw-bold">WARJOK</h3>
                    <span class="subtitle">Admin Panel</span>
                </div>
            </a>
            <button class="btn btn-close-sidebar d-lg-none p-0 border-0 shadow-none text-white" id="closeSidebarBtn">
                <i class="bi bi-x-lg fs-5"></i>
            </button>
        </div>

        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="{{ Route::has('admin.dashboard') ? route('admin.dashboard') : '#' }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('products.index') ? route('products.index') : '#' }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <i class="bi bi-box-seam"></i>
                        <span class="nav-text">Manajemen Produk</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i class="bi bi-boxes"></i>
                        <span class="nav-text">Stok & Inventori</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('restock.index') ? route('restock.index') : '#' }}" class="nav-link {{ request()->routeIs('restock.*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-repeat"></i>
                        <span class="nav-text">Restock Barang</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('units.index') ? route('units.index') : '#' }}" class="nav-link {{ request()->routeIs('units.*') ? 'active' : '' }}">
                        <i class="bi bi-tags"></i>
                        <span class="nav-text">Management Satuan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('hpp.index') ? route('hpp.index') : '#' }}" class="nav-link {{ request()->routeIs('hpp.*') ? 'active' : '' }}">
                        <i class="bi bi-calculator"></i>
                        <span class="nav-text">Management HPP</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link">
                        <i class="bi bi-cart3"></i>
                        <span class="nav-text">Penjualan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('reports.index') ? route('reports.index') : '#' }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span class="nav-text">Laporan & Analitik</span>
                    </a>
                </li>

                <li class="nav-item nav-category mt-3 mb-1 px-3">
                    <span class="nav-text text-uppercase text-muted small fw-bold">Pengaturan</span>
                </li>

                <li class="nav-item">
                    <a href="{{ Route::has('users.index') ? route('users.index') : '#' }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>
                        <span class="nav-text">Kelola Pengguna</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link">
                        <i class="bi bi-gear"></i>
                        <span class="nav-text">Pengaturan Sistem</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="avatar-circle">AD</div>
                <div class="user-details ms-2">
                    <h6 class="mb-0 text-white fs-sm">Administrator</h6>
                    <span class="badge bg-emerald-transparent text-emerald role-badge">Super Admin</span>
                </div>
                <button class="btn btn-logout ms-auto border-0 p-1 shadow-none text-white-50" title="Logout">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <main class="main-wrapper" id="mainWrapper">
        <!-- Top Navbar -->
        <header class="topbar d-flex align-items-center justify-content-between bg-white px-4">
            <div class="d-flex align-items-center">
                <!-- Mobile Toggle -->
                <button class="btn btn-toggle d-lg-none me-3 p-0 border-0 shadow-none text-slate" id="mobileToggleBtn">
                    <i class="bi bi-list fs-4"></i>
                </button>
                
                <!-- Desktop Minimize Toggle -->
                <button class="btn btn-toggle d-none d-lg-block me-3 p-0 border-0 shadow-none text-slate" id="desktopToggleBtn">
                    <i class="bi bi-layout-sidebar fs-5"></i>
                </button>

                <div class="page-header d-none d-sm-block">
                    @hasSection('page-title')
                        <h5 class="page-title mb-0 fw-bold text-navy">@yield('page-title')</h5>
                    @endif
                    <div class="breadcrumb-container small text-muted">
                        @yield('breadcrumb')
                    </div>
                </div>
            </div>

            <div class="topbar-right d-flex align-items-center">
                <!-- Profile Dropdown (Sleek & Elegant) -->
                <div class="dropdown">
                    <button class="btn btn-user border-0 p-1 pe-2 shadow-none d-flex align-items-center gap-2 rounded-pill hover-bg-slate-50" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-circle-sm text-white d-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:34px; height:34px; font-size: 0.8rem; background: #1e293b;">AD</div>
                        <div class="d-none d-md-flex flex-column text-start">
                            <span class="text-dark fw-semibold" style="font-size: 0.82rem; line-height: 1.15;">Administrator</span>
                            <span class="text-muted" style="font-size: 0.7rem;">Super Admin</span>
                        </div>
                        <i class="bi bi-chevron-down text-muted small d-none d-md-block ms-1" style="font-size: 0.72rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2 py-2" style="border-radius: 12px; min-width: 210px; border-color: var(--border);">
                        <li class="px-3 py-2 border-bottom mb-1 bg-light bg-opacity-50">
                            <div class="fw-bold text-dark small">Administrator</div>
                            <div class="text-muted" style="font-size: 0.72rem;">admin@warjok.com</div>
                        </li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 text-secondary" href="#"><i class="bi bi-person"></i> Profil Saya</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 text-secondary" href="#"><i class="bi bi-gear"></i> Pengaturan Akun</a></li>
                        <li><hr class="dropdown-divider my-1 border-slate-200"></li>
                        <li><a class="dropdown-item text-danger py-2 px-3 small d-flex align-items-center gap-2" href="#"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area p-4">
            @yield('content')
        </div>
    </main>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="{{ asset('js/admin.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
