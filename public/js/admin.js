/**
 * WARJOK Admin Dashboard — JavaScript
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */
document.addEventListener('DOMContentLoaded', function () {

    // ── Element References ──────────────────────
    const sidebar         = document.getElementById('sidebar');
    const mainWrapper     = document.getElementById('mainWrapper');
    const desktopToggle   = document.getElementById('desktopToggleBtn');
    const mobileToggle    = document.getElementById('mobileToggleBtn');
    const closeSidebar    = document.getElementById('closeSidebarBtn');
    const overlay         = document.getElementById('sidebarOverlay');
    const navLinks        = document.querySelectorAll('.sidebar .nav-link:not(.nav-category .nav-link)');

    const STORAGE_KEY     = 'warjok_sidebar_minimized';
    const BREAKPOINT      = 992; // lg breakpoint

    // ── 1. Desktop: Minimize / Expand ───────────
    function applyDesktopState(minimized) {
        if (!sidebar) return;
        if (minimized) {
            sidebar.classList.add('minimized');
        } else {
            sidebar.classList.remove('minimized');
        }
        // Update toggle icon
        if (desktopToggle) {
            const icon = desktopToggle.querySelector('i');
            if (icon) {
                icon.className = minimized
                    ? 'bi bi-layout-sidebar-inset-reverse fs-4'
                    : 'bi bi-layout-sidebar-inset fs-4';
            }
        }
    }

    // Restore from localStorage on load (desktop only)
    if (window.innerWidth >= BREAKPOINT) {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved === 'true') {
            applyDesktopState(true);
        }
    }

    if (desktopToggle) {
        desktopToggle.addEventListener('click', function () {
            const isMinimized = sidebar.classList.contains('minimized');
            applyDesktopState(!isMinimized);
            localStorage.setItem(STORAGE_KEY, (!isMinimized).toString());
        });
    }

    // ── 2. Mobile: Off-canvas Sidebar ───────────
    function openMobileSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', openMobileSidebar);
    }
    if (closeSidebar) {
        closeSidebar.addEventListener('click', closeMobileSidebar);
    }
    if (overlay) {
        overlay.addEventListener('click', closeMobileSidebar);
    }

    // Close mobile sidebar on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
            closeMobileSidebar();
        }
    });

    // ── 3. Nav Item Active State ────────────────
    navLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            // Remove active from all
            navLinks.forEach(function (l) { l.classList.remove('active'); });
            // Set active on clicked
            this.classList.add('active');

            // On mobile, close sidebar after selection
            if (window.innerWidth < BREAKPOINT) {
                closeMobileSidebar();
            }
        });
    });

    // ── 4. Window Resize Handler ────────────────
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (window.innerWidth >= BREAKPOINT) {
                // Switched to desktop: close mobile sidebar state
                closeMobileSidebar();
                // Re-apply desktop minimized state from storage
                const saved = localStorage.getItem(STORAGE_KEY);
                applyDesktopState(saved === 'true');
            } else {
                // Switched to mobile: remove desktop minimized class
                if (sidebar) sidebar.classList.remove('minimized');
            }
        }, 150);
    });

});
