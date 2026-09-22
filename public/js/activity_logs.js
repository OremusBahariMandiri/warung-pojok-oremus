/**
 * WARJOK — Manajemen Log Aktivitas DataTables & Audit Viewer JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

/* ════════════════════════════════════════════════════════════
   COPY TO CLIPBOARD
   ════════════════════════════════════════════════════════════ */
function copyLogJson(elementId, btnEl) {
    const codeEl = document.getElementById(elementId);
    if (!codeEl) return;
    navigator.clipboard
        .writeText(codeEl.innerText || codeEl.textContent)
        .then(() => {
            if (btnEl) {
                const orig = btnEl.innerHTML;
                btnEl.innerHTML = '<i class="bi bi-check2 me-1"></i> Tersalin!';
                btnEl.classList.remove("btn-outline-light", "btn-light");
                btnEl.classList.add("btn-success", "text-white");
                setTimeout(() => {
                    btnEl.innerHTML = orig;
                    btnEl.classList.remove("btn-success", "text-white");
                    btnEl.classList.add("btn-outline-light");
                }, 2000);
            }
        })
        .catch((err) => console.error("Gagal menyalin:", err));
}

/* ════════════════════════════════════════════════════════════
   QUICK PREVIEW MODAL
   ════════════════════════════════════════════════════════════ */
function openQuickLogModal(btn) {
    const dataStr = btn.getAttribute("data-log");
    if (!dataStr) return;
    const setText = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    };
    try {
        const log = JSON.parse(dataStr);
        setText("quickLogId", "#" + log.id);
        setText("quickLogAction", log.action);
        setText("quickLogModule", log.module);
        setText("quickLogDesc", log.description || "-");
        setText("quickLogUser", log.user_name || "Sistem");
        setText("quickLogTime", log.time || "-");
        setText("quickLogIp", log.ip || "-");

        const detailLink = document.getElementById("quickLogDetailLink");
        if (detailLink) detailLink.href = "/activity-logs/" + log.id;

        const oldJsonEl = document.getElementById("quickLogOldJson");
        const newJsonEl = document.getElementById("quickLogNewJson");
        if (oldJsonEl)
            oldJsonEl.textContent = log.old_values
                ? JSON.stringify(log.old_values, null, 2)
                : "Tidak ada data lama (null)";
        if (newJsonEl)
            newJsonEl.textContent = log.new_values
                ? JSON.stringify(log.new_values, null, 2)
                : "Tidak ada data baru (null)";

        const modalEl = document.getElementById("modalQuickViewLog");
        if (modalEl && typeof bootstrap !== "undefined")
            new bootstrap.Modal(modalEl).show();
    } catch (e) {
        console.error("Error parsing log data for quick view:", e);
    }
}

window.copyLogJson = copyLogJson;
window.openQuickLogModal = openQuickLogModal;

/* ════════════════════════════════════════════════════════════
   DOM READY
   ════════════════════════════════════════════════════════════ */
document.addEventListener("DOMContentLoaded", function () {
    /* Auto-dismiss alerts */
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

    /* Mobile search */
    const mobileSearch = document.getElementById("mobileLogSearchInput");
    if (mobileSearch) {
        mobileSearch.addEventListener("input", function () {
            const q = this.value.toLowerCase().trim();
            let hasVisible = false;
            document
                .querySelectorAll("#mobileLogCards .mobile-log-card")
                .forEach((card) => {
                    const match =
                        !q ||
                        (card.dataset.search || "").toLowerCase().includes(q);
                    card.style.display = match ? "" : "none";
                    if (match) hasVisible = true;
                });
            const noResult = document.getElementById("mobileLogNoResult");
            if (noResult) noResult.classList.toggle("d-none", hasVisible);
        });
    }
});

/* ════════════════════════════════════════════════════════════
   DATATABLES — INDEX PAGE (Log Aktivitas)
   Mobile (< 768px) : responsive false → card HTML lama
   Desktop (≥ 768px): responsive inline child row
   Kolom disembunyikan duluan:
     Deskripsi Aktivitas (4) → responsivePriority: 11
     Pengguna            (5) → responsivePriority: 12
   ════════════════════════════════════════════════════════════ */
if (typeof jQuery !== "undefined") {
    jQuery(document).ready(function ($) {
        if ($("#activityLogsDataTable").length === 0) return;

        const isMobile = window.innerWidth < 768;

        const dataTable = $("#activityLogsDataTable").DataTable({
            responsive: isMobile
                ? false
                : {
                      details: {
                          type: "inline",
                          target: "tr",
                          renderer:
                              $.fn.dataTable.Responsive.renderer.listHiddenNodes(),
                      },
                  },

            order: [[1, "desc"]],

            columnDefs: [
                { targets: "no-sort", orderable: false },
                // Prioritas — makin kecil = makin dipertahankan
                { targets: 0, responsivePriority: 1 }, // No
                { targets: 1, responsivePriority: 2 }, // Waktu
                { targets: 2, responsivePriority: 3 }, // Modul
                { targets: 3, responsivePriority: 4 }, // Aksi
                { targets: 4, responsivePriority: 11 }, // Deskripsi — disembunyikan duluan
                { targets: 5, responsivePriority: 12 }, // Pengguna  — disembunyikan duluan
                { targets: 6, responsivePriority: 1 }, // Aksi tombol — selalu tampil
            ],

            scrollX: false,
            autoWidth: false,

            language: {
                emptyTable: "Belum ada riwayat aktivitas yang tercatat.",
                zeroRecords:
                    "Tidak ada log aktivitas yang cocok dengan pencarian atau filter.",
                info: "Menampilkan _START_–_END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada aktivitas",
                infoFiltered: "(difilter dari _MAX_ total entri)",
                paginate: { first: "«", previous: "‹", next: "›", last: "»" },
            },
            pagingType: "full_numbers",
            pageLength: 15,
            dom: '<"table-responsive-wrapper"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>',
        });

        /* Resize handler */
        let resizeTimer;
        $(window).on("resize", function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (window.innerWidth < 768 !== isMobile) dataTable.destroy();
            }, 300);
        });

        /* Search */
        $("#dtSearchInput").on("keyup input", function () {
            dataTable.search(this.value).draw();
        });

        /* Filter Apply & Reset */
        $("#btnApplyFilter").on("click", function () {
            applyLogFilters();
        });
        $("#btnResetFilter").on("click", function () {
            $("#modalFilterModule").val("");
            $("#modalFilterAction").val("");
            $("#modalFilterUser").val("");
            $("#modalFilterStartDate").val("");
            $("#modalFilterEndDate").val("");
            applyLogFilters();
        });

        function applyLogFilters() {
            const module = $("#modalFilterModule").val();
            const action = $("#modalFilterAction").val();
            const user = $("#modalFilterUser").val();
            const startDate = $("#modalFilterStartDate").val();
            const endDate = $("#modalFilterEndDate").val();

            // Kolom 2: Modul
            dataTable
                .column(2)
                .search(module ? "^" + module + "$" : "", true, false);
            // Kolom 3: Aksi
            dataTable
                .column(3)
                .search(action ? "^" + action + "$" : "", true, false);
            // Kolom 5: Pengguna
            dataTable.column(5).search(user || "", false, false);

            /* Date range custom filter */
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
                (fn) => fn.name !== "activityDateRangeFilter",
            );
            if (startDate || endDate) {
                const activityDateRangeFilter = function (
                    settings,
                    data,
                    dataIndex,
                ) {
                    if (settings.nTable.id !== "activityLogsDataTable")
                        return true;
                    const rowDate = $(dataTable.row(dataIndex).node()).attr(
                        "data-date",
                    );
                    if (!rowDate) return true;
                    if (startDate && rowDate < startDate) return false;
                    if (endDate && rowDate > endDate) return false;
                    return true;
                };
                activityDateRangeFilter.name = "activityDateRangeFilter";
                $.fn.dataTable.ext.search.push(activityDateRangeFilter);
            }

            dataTable.draw();

            const isFiltered = !!(
                module ||
                action ||
                user ||
                startDate ||
                endDate
            );
            $("#activeFilterBadge").toggleClass("d-none", !isFiltered);
            $("#activeFilterBadgeMobile").toggleClass("d-none", !isFiltered);

            /* Sync mobile cards */
            filterMobileCards(module, action, user, startDate, endDate);
        }

        function filterMobileCards(module, action, user, startDate, endDate) {
            let hasVisible = false;
            document
                .querySelectorAll("#mobileLogCards .mobile-log-card")
                .forEach((card) => {
                    const cMod = (card.dataset.module || "").toUpperCase();
                    const cAct = (card.dataset.action || "").toUpperCase();
                    const cUsr = (card.dataset.user || "").toLowerCase();
                    const cDate = card.dataset.date || "";

                    let match = true;
                    if (module && cMod !== module.toUpperCase()) match = false;
                    if (action && cAct !== action.toUpperCase()) match = false;
                    if (user && !cUsr.includes(user.toLowerCase()))
                        match = false;
                    if (startDate && cDate < startDate) match = false;
                    if (endDate && cDate > endDate) match = false;

                    card.style.display = match ? "" : "none";
                    if (match) hasVisible = true;
                });
            const noResult = document.getElementById("mobileLogNoResult");
            if (noResult) noResult.classList.toggle("d-none", hasVisible);
        }
    });
}
