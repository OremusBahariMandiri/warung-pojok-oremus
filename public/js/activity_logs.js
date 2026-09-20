/**
 * WARJOK — Manajemen Log Aktivitas DataTables & Audit Viewer JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

// ── Copy to Clipboard Function ── //
function copyLogJson(elementId, btnEl) {
    const codeEl = document.getElementById(elementId);
    if (!codeEl) return;

    const textToCopy = codeEl.innerText || codeEl.textContent;
    navigator.clipboard
        .writeText(textToCopy)
        .then(() => {
            if (btnEl) {
                const originalHtml = btnEl.innerHTML;
                btnEl.innerHTML = '<i class="bi bi-check2 me-1"></i> Tersalin!';
                btnEl.classList.remove("btn-outline-light", "btn-light");
                btnEl.classList.add("btn-success", "text-white");
                setTimeout(() => {
                    btnEl.innerHTML = originalHtml;
                    btnEl.classList.remove("btn-success", "text-white");
                    btnEl.classList.add("btn-outline-light");
                }, 2000);
            }
        })
        .catch((err) => {
            console.error("Gagal menyalin:", err);
        });
}

// ── Quick Preview Modal Helper ── //
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
        if (detailLink) {
            detailLink.href = "/activity-logs/" + log.id;
        }

        const oldJsonEl = document.getElementById("quickLogOldJson");
        const newJsonEl = document.getElementById("quickLogNewJson");

        if (oldJsonEl) {
            oldJsonEl.textContent = log.old_values
                ? JSON.stringify(log.old_values, null, 2)
                : "Tidak ada data lama (null)";
        }
        if (newJsonEl) {
            newJsonEl.textContent = log.new_values
                ? JSON.stringify(log.new_values, null, 2)
                : "Tidak ada data baru (null)";
        }

        const modalEl = document.getElementById("modalQuickViewLog");
        if (modalEl && typeof bootstrap !== "undefined") {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    } catch (e) {
        console.error("Error parsing log data for quick view:", e);
    }
}

// Expose globals
window.copyLogJson = copyLogJson;
window.openQuickLogModal = openQuickLogModal;

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

    // ── Mobile Search Handler ── //
    const mobileSearchInput = document.getElementById("mobileLogSearchInput");
    if (mobileSearchInput) {
        mobileSearchInput.addEventListener("input", function () {
            const q = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll(
                "#mobileLogCards .mobile-log-card",
            );
            let hasVisible = false;

            cards.forEach((card) => {
                const searchData = (card.dataset.search || "").toLowerCase();
                const match = !q || searchData.includes(q);
                card.style.display = match ? "" : "none";
                if (match) hasVisible = true;
            });

            const noResult = document.getElementById("mobileLogNoResult");
            if (noResult) {
                noResult.classList.toggle("d-none", hasVisible);
            }
        });
    }
});

// ── jQuery / DataTables Handler for Index Page ── //
if (typeof jQuery !== "undefined") {
    jQuery(document).ready(function ($) {
        if ($("#activityLogsDataTable").length > 0) {
            const dataTable = $("#activityLogsDataTable").DataTable({
                responsive: false,
                order: [[1, "desc"]],
                columnDefs: [{ targets: "no-sort", orderable: false }],
                language: {
                    emptyTable: "Belum ada riwayat aktivitas yang tercatat.",
                    zeroRecords:
                        "Tidak ada log aktivitas yang cocok dengan pencarian atau filter.",
                    info: "Showing _START_–_END_ dari _TOTAL_ entries",
                    infoEmpty: "Tidak ada aktivitas",
                    infoFiltered: "(difilter dari _MAX_ total entri)",
                    paginate: {
                        first: "«",
                        previous: "‹",
                        next: "›",
                        last: "»",
                    },
                },
                pagingType: "full_numbers",
                pageLength: 15,
                dom: '<"activity-dt-scroll"t><"#activityLogsDtFooter"ip>',
            });

            // Search from custom input
            $("#dtSearchInput").on("keyup input", function () {
                dataTable.search(this.value).draw();
            });

            // Filter Apply & Reset Handlers
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

                // Module filter (Column 2)
                dataTable
                    .column(2)
                    .search(module ? "^" + module + "$" : "", true, false);

                // Action filter (Column 3)
                dataTable
                    .column(3)
                    .search(action ? "^" + action + "$" : "", true, false);

                // User filter (Column 5)
                dataTable.column(5).search(user || "", false, false);

                // Date Range Filter via custom DataTables search function
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

                // Indicator Badge for Active Filters
                const isFiltered = !!(
                    module ||
                    action ||
                    user ||
                    startDate ||
                    endDate
                );
                if (isFiltered) {
                    $("#activeFilterBadge").removeClass("d-none");
                    $("#activeFilterBadgeMobile").removeClass("d-none");
                } else {
                    $("#activeFilterBadge").addClass("d-none");
                    $("#activeFilterBadgeMobile").addClass("d-none");
                }

                // Also filter Mobile Cards if on mobile view
                filterMobileCards(module, action, user, startDate, endDate);
            }

            function filterMobileCards(
                module,
                action,
                user,
                startDate,
                endDate,
            ) {
                const cards = document.querySelectorAll(
                    "#mobileLogCards .mobile-log-card",
                );
                let hasVisible = false;

                cards.forEach((card) => {
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
                if (noResult) {
                    noResult.classList.toggle("d-none", hasVisible);
                }
            }
        }
    });
}
