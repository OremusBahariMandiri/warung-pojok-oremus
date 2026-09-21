/**
 * WARJOK — Manajemen Pengguna & SIPAS-Style RBAC Matrix JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

// ── Delete Confirmation Modal Handler ── //
function openDeleteUserModal(id, name, email) {
    const nameEl = document.getElementById("deleteUserName");
    const emailEl = document.getElementById("deleteUserEmail");
    const formEl = document.getElementById("deleteUserForm");

    if (nameEl) nameEl.textContent = name;
    if (emailEl) emailEl.textContent = email;
    if (formEl) formEl.action = "/users/" + id;

    const modalEl = document.getElementById("modalDeleteUser");
    if (modalEl && typeof bootstrap !== "undefined") {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

// ══════════════════════════════════════════════════════════════
// SIPAS RBAC MATRIX LOGIC & BULK TOGGLES
// ══════════════════════════════════════════════════════════════

/**
 * Centang Semua Hak Akses
 */
function checkAllAccess() {
    const allCheckboxes = document.querySelectorAll(
        ".rbac-perm-checkbox, .rbac-row-master",
    );
    allCheckboxes.forEach((cb) => {
        cb.checked = true;
    });
}

/**
 * Hapus / Kosongkan Semua Hak Akses
 */
function uncheckAllAccess() {
    const allCheckboxes = document.querySelectorAll(
        ".rbac-perm-checkbox, .rbac-row-master",
    );
    allCheckboxes.forEach((cb) => {
        cb.checked = false;
    });
}

/**
 * Toggle Semua Hak Akses pada Satu Baris Modul (Row Toggle)
 */
function toggleRowAccess(rowCheckbox) {
    const row = rowCheckbox.closest("tr");
    if (!row) return;

    const isChecked = rowCheckbox.checked;
    const permCheckboxes = row.querySelectorAll(".rbac-perm-checkbox");
    permCheckboxes.forEach((cb) => {
        cb.checked = isChecked;
    });
}

/**
 * Toggle Seluruh Checkbox pada Satu Kolom Aksi (Column Toggle)
 * @param {string} actionKey - 'index', 'create', 'edit', 'show', 'delete'
 */
function toggleColumnAccess(actionKey) {
    const colCheckboxes = document.querySelectorAll(
        `.rbac-perm-checkbox[data-action="${actionKey}"]`,
    );
    if (colCheckboxes.length === 0) return;

    // Check if currently all checked
    let allChecked = true;
    colCheckboxes.forEach((cb) => {
        if (!cb.checked) allChecked = false;
    });

    const targetState = !allChecked;
    colCheckboxes.forEach((cb) => {
        cb.checked = targetState;
        updateRowMasterState(cb);
    });
}

/**
 * Sinkronisasi Checkbox Master Baris saat individual permission diubah
 */
function updateRowMasterState(permCheckbox) {
    const row = permCheckbox.closest("tr");
    if (!row) return;

    const masterCheckbox = row.querySelector(".rbac-row-master");
    if (!masterCheckbox) return;

    const permCheckboxes = row.querySelectorAll(".rbac-perm-checkbox");
    let allRowChecked = true;
    permCheckboxes.forEach((cb) => {
        if (!cb.checked) allRowChecked = false;
    });

    masterCheckbox.checked = allRowChecked;
}

// Expose globals
window.openDeleteUserModal = openDeleteUserModal;
window.checkAllAccess = checkAllAccess;
window.uncheckAllAccess = uncheckAllAccess;
window.toggleRowAccess = toggleRowAccess;
window.toggleColumnAccess = toggleColumnAccess;
window.updateRowMasterState = updateRowMasterState;

// ── DOM Ready Initializations ── //
document.addEventListener("DOMContentLoaded", function () {
    // ── Auto-dismiss alerts ── //
    const alerts = document.querySelectorAll(".alert-dismissible");
    if (alerts.length > 0) {
        setTimeout(function () {
            alerts.forEach((alert) => {
                if (typeof bootstrap !== "undefined" && bootstrap.Alert) {
                    new bootstrap.Alert(alert).close();
                } else {
                    alert.style.display = "none";
                }
            });
        }, 5000);
    }

    // ── RBAC Checkbox Change Listeners ── //
    const permCheckboxes = document.querySelectorAll(".rbac-perm-checkbox");
    permCheckboxes.forEach((cb) => {
        cb.addEventListener("change", function () {
            updateRowMasterState(this);
        });
    });

    // Initialize row master checkboxes based on initial values
    const rows = document.querySelectorAll(
        ".rbac-table tbody tr:not(.rbac-category-row)",
    );
    rows.forEach((row) => {
        const master = row.querySelector(".rbac-row-master");
        const perms = row.querySelectorAll(".rbac-perm-checkbox");
        if (master && perms.length > 0) {
            let allChecked = true;
            perms.forEach((p) => {
                if (!p.checked) allChecked = false;
            });
            master.checked = allChecked;
        }
    });

    // ── Mobile Search Handler for User Cards ── //
    const mobileSearchInput = document.getElementById("mobileUserSearchInput");
    if (mobileSearchInput) {
        mobileSearchInput.addEventListener("input", function () {
            const q = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll(
                "#mobileUserCards .mobile-user-card",
            );
            let hasVisible = false;

            cards.forEach((card) => {
                const searchData = (card.dataset.search || "").toLowerCase();
                const match = !q || searchData.includes(q);
                card.style.display = match ? "" : "none";
                if (match) hasVisible = true;
            });

            const noResult = document.getElementById("mobileUserNoResult");
            if (noResult) {
                noResult.classList.toggle("d-none", hasVisible);
            }
        });
    }
});

// ── jQuery & DataTables for Users Index ── //
if (typeof jQuery !== "undefined") {
    jQuery(document).ready(function ($) {
        if ($("#usersDataTable").length > 0) {
            const dataTable = $("#usersDataTable").DataTable({
                responsive: false,
                order: [[1, "asc"]],
                columnDefs: [{ targets: "no-sort", orderable: false }],
                language: {
                    emptyTable: "Belum ada data pengguna yang terdaftar.",
                    zeroRecords:
                        "Tidak ada pengguna yang cocok dengan pencarian atau filter.",
                    info: "Showing _START_–_END_ dari _TOTAL_ entries",
                    infoEmpty: "Tidak ada pengguna",
                    infoFiltered: "(difilter dari _MAX_ total entri)",
                    paginate: {
                        first: "«",
                        previous: "‹",
                        next: "›",
                        last: "»",
                    },
                },
                pagingType: "full_numbers",
                pageLength: 10,
                dom: '<"users-dt-scroll"t><"#usersDtFooter"ip>',
            });

            // Search from custom input
            $("#dtSearchInput").on("keyup input", function () {
                dataTable.search(this.value).draw();
            });

            // Filter Apply & Reset Handlers
            $("#btnApplyFilter").on("click", function () {
                applyUserFilters();
            });

            $("#btnResetFilter").on("click", function () {
                $("#modalFilterRole").val("");
                applyUserFilters();
            });

            function applyUserFilters() {
                const role = $("#modalFilterRole").val();

                // Role filter (Column 3: Peran)
                if (role === "cashier") {
                    dataTable.column(3).search("Petugas Kasir", false, false);
                } else {
                    dataTable.column(3).search("");
                }

                dataTable.draw();

                if (role) {
                    $("#activeFilterBadge").removeClass("d-none");
                    $("#activeFilterBadgeMobile").removeClass("d-none");
                } else {
                    $("#activeFilterBadge").addClass("d-none");
                    $("#activeFilterBadgeMobile").addClass("d-none");
                }

                // Filter mobile cards
                const cards = document.querySelectorAll(
                    "#mobileUserCards .mobile-user-card",
                );
                let hasVisible = false;
                cards.forEach((card) => {
                    const cRole = card.dataset.role || "";
                    let match = true;
                    if (role && cRole !== role) match = false;
                    card.style.display = match ? "" : "none";
                    if (match) hasVisible = true;
                });

                const noResult = document.getElementById("mobileUserNoResult");
                if (noResult) {
                    noResult.classList.toggle("d-none", hasVisible);
                }
            }
        }
    });
}
