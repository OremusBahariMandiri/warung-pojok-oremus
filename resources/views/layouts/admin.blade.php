<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - WARJOK</title>
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body>

    {{-- Fetch data user yang sedang login saat ini --}}
    @php
        $currentUser = auth()->user();

        $employeeName = $currentUser->employee_name ?? 'Administrator';
        $userRole = $currentUser->is_admin ? 'Super Admin' : 'Pengguna';
        $userEmail = $currentUser->email ?? '';
        $initials = strtoupper(substr(trim($employeeName), 0, 2));
    @endphp

    <!-- Mobile Overlay -->
    <div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ Route::has('admin.dashboard') ? route('admin.dashboard') : '#' }}" class="brand-link text-decoration-none">
                <i class="bi bi-shop text-success fs-4 brand-icon"></i>
                <div class="brand-text">
                    <h5 class="mb-0 fw-bold text-white">WARJOK</h5>
                    <span class="subtitle">Admin Panel</span>
                </div>
            </a>
            <button class="btn btn-close-sidebar d-lg-none p-0 border-0 shadow-none text-white-50" id="closeSidebarBtn">
                <i class="bi bi-x-lg fs-5"></i>
            </button>
        </div>

        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <!-- ── 1. MAIN MENU ── -->
                <li class="nav-item nav-category">
                    <span class="nav-text">Main Menu</span>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('admin.dashboard') ? route('admin.dashboard') : '#' }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2 menu-icon"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>

                <!-- ── 2. MASTER DATA ── -->
                @php
                    $isMasterDataActive = request()->routeIs('products.*') || request()->routeIs('units.*') || request()->routeIs('hpp.*');
                @endphp
                <li class="nav-item nav-category">
                    <span class="nav-text">Master Data</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-toggle {{ $isMasterDataActive ? 'active' : 'collapsed' }}" 
                       data-bs-toggle="collapse" 
                       href="#menuMasterData" 
                       role="button" 
                       aria-expanded="{{ $isMasterDataActive ? 'true' : 'false' }}" 
                       aria-controls="menuMasterData">
                        <i class="bi bi-box-seam menu-icon"></i>
                        <span class="nav-text">Master Data</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse {{ $isMasterDataActive ? 'show' : '' }}" id="menuMasterData">
                        <ul class="submenu-list">
                            <li class="submenu-item">
                                <a href="{{ Route::has('products.index') ? route('products.index') : '#' }}" class="submenu-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                                    <span class="submenu-dot"></span>
                                    <span>Manajemen Produk</span>
                                </a>
                            </li>
                            <li class="submenu-item">
                                <a href="{{ Route::has('units.index') ? route('units.index') : '#' }}" class="submenu-link {{ request()->routeIs('units.*') ? 'active' : '' }}">
                                    <span class="submenu-dot"></span>
                                    <span>Manajemen Satuan</span>
                                </a>
                            </li>
                            <li class="submenu-item">
                                <a href="{{ Route::has('hpp.index') ? route('hpp.index') : '#' }}" class="submenu-link {{ request()->routeIs('hpp.*') ? 'active' : '' }}">
                                    <span class="submenu-dot"></span>
                                    <span>Manajemen HPP</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- ── 3. INVENTORI / RESTOCK ── -->
                <li class="nav-item nav-category">
                    <span class="nav-text">Inventori</span>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('restock.index') ? route('restock.index') : '#' }}" class="nav-link {{ request()->routeIs('restock.*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-repeat menu-icon"></i>
                        <span class="nav-text">Restock Barang</span>
                    </a>
                </li>

                <!-- ── 4. LAPORAN ── -->
                @php
                    $isReportsActive = request()->routeIs('reports.*');
                @endphp
                <li class="nav-item nav-category">
                    <span class="nav-text">Laporan</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-toggle {{ $isReportsActive ? 'active' : 'collapsed' }}" 
                       data-bs-toggle="collapse" 
                       href="#menuReports" 
                       role="button" 
                       aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}" 
                       aria-controls="menuReports">
                        <i class="bi bi-bar-chart-line menu-icon"></i>
                        <span class="nav-text">Laporan & Analitik</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse {{ $isReportsActive ? 'show' : '' }}" id="menuReports">
                        <ul class="submenu-list">
                            <li class="submenu-item">
                                <a href="{{ Route::has('reports.summary') ? route('reports.summary') : '#' }}" class="submenu-link {{ request()->routeIs('reports.summary') ? 'active' : '' }}">
                                    <span class="submenu-dot"></span>
                                    <span>Ringkasan & Margin</span>
                                </a>
                            </li>
                            <li class="submenu-item">
                                <a href="{{ Route::has('reports.index') ? route('reports.index') : '#' }}" class="submenu-link {{ (request()->routeIs('reports.index') || (request()->routeIs('reports.*') && !request()->routeIs('reports.summary'))) ? 'active' : '' }}">
                                    <span class="submenu-dot"></span>
                                    <span>Laporan Penjualan</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- ── 5. PENGATURAN ── -->
                @php
                    $isSettingsActive = request()->routeIs('users.*') || request()->routeIs('activity_logs.*');
                @endphp
                <li class="nav-item nav-category">
                    <span class="nav-text">Pengaturan</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-toggle {{ $isSettingsActive ? 'active' : 'collapsed' }}" 
                       data-bs-toggle="collapse" 
                       href="#menuSettings" 
                       role="button" 
                       aria-expanded="{{ $isSettingsActive ? 'true' : 'false' }}" 
                       aria-controls="menuSettings">
                        <i class="bi bi-gear menu-icon"></i>
                        <span class="nav-text">Pengaturan Sistem</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse {{ $isSettingsActive ? 'show' : '' }}" id="menuSettings">
                        <ul class="submenu-list">
                            <li class="submenu-item">
                                <a href="{{ Route::has('users.show') ? route('users.show', auth()->id()) : '#' }}" 
                                class="submenu-link {{ request()->routeIs('users.show') && request()->route('user')?->id == auth()->id() ? 'active' : '' }}">
                                    <span class="submenu-dot"></span>
                                    <span>Profile</span>
                                </a>
                            </li>
                            <li class="submenu-item">
                                <a href="{{ Route::has('users.index') ? route('users.index') : '#' }}" 
                                class="submenu-link {{ request()->routeIs('users.index', 'users.create', 'users.edit') || (request()->routeIs('users.show') && request()->route('user')?->id != auth()->id()) ? 'active' : '' }}">
                                    <span class="submenu-dot"></span>
                                    <span>Kelola Pengguna</span>
                                </a>
                            </li>
                            <li class="submenu-item">
                                <a href="{{ Route::has('activity_logs.index') ? route('activity_logs.index') : '#' }}" class="submenu-link {{ request()->routeIs('activity_logs.*') ? 'active' : '' }}">
                                    <span class="submenu-dot"></span>
                                    <span>Log Aktivitas</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="avatar-circle">{{ $initials }}</div>
                <div class="user-details ms-2">
                    <h6 class="mb-0 text-white fs-sm text-truncate" style="max-width: 125px;">{{ $employeeName }}</h6>
                    <span class="badge role-badge">{{ ucfirst($userRole) }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="ms-auto d-inline" id="sidebarLogoutForm">
                    @csrf
                    <button type="submit" class="btn btn-logout border-0 p-1 shadow-none" title="Logout">
                        <i class="bi bi-box-arrow-right fs-5"></i>
                    </button>
                </form>
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
                        <div class="avatar-circle-sm text-white d-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:34px; height:34px; font-size: 0.8rem; background: #1e293b;">{{ $initials }}</div>
                        <div class="d-none d-md-flex flex-column text-start">
                            <span class="text-dark fw-semibold" style="font-size: 0.82rem; line-height: 1.15;">{{ $employeeName }}</span>
                            <span class="text-muted" style="font-size: 0.7rem;">{{ $userRole }}</span>
                        </div>
                        <i class="bi bi-chevron-down text-muted small d-none d-md-block ms-1" style="font-size: 0.72rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2 py-2" style="border-radius: 12px; min-width: 210px; border-color: var(--border);">
                        <li class="px-3 py-2 border-bottom mb-1 bg-light bg-opacity-50">
                            <div class="fw-bold text-dark small">{{ $employeeName }}</div>
                            <div class="text-muted" style="font-size: 0.72rem;">{{ $userEmail }}</div>
                        </li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 text-secondary" href="{{ route('users.show', auth()->id()) }}"><i class="bi bi-person"></i> Profil Saya</a></li></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" id="logout-dropdown-head" style="display:none">
                                @csrf
                            </form>
                                <a class="dropdown-item text-danger py-2 px-3 small d-flex align-items-center gap-2" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-dropdown-head').submit();"><i class="bi bi-box-arrow-right"></i> Logout</a>
                            </li>
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
