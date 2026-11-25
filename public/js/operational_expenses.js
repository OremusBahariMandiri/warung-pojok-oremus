/**
 * WARJOK — Transaksi Operasional JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 *
 * Handles:
 *  - Halaman Index: DataTables init (order asc & dynamic rowCallback), search box, date range filter modal, delete modal
 *  - Halaman Create & Edit: Auto-format input mata uang (Rp) dan auto-kalkulasi Total Biaya (qty x price)
 */

// ── INDEX PAGE: Modal Konfirmasi Hapus (Exposed to window for inline onclick) ──
function openDeleteExpenseModal(id, name, date, total) {
    const nameEl = document.getElementById("deleteExpenseName");
    const dateEl = document.getElementById("deleteExpenseDate");
    const totalEl = document.getElementById("deleteExpenseTotal");
    const form = document.getElementById("deleteExpenseForm");

    if (nameEl) nameEl.textContent = name;
    if (dateEl) dateEl.textContent = date;
    if (totalEl) totalEl.textContent = total;
    if (form) form.action = "/operational-expenses/" + id;

    const modalEl = document.getElementById("modalDeleteExpense");
    if (modalEl && typeof bootstrap !== "undefined") {
        new bootstrap.Modal(modalEl).show();
    }
}
window.openDeleteExpenseModal = openDeleteExpenseModal;

// ── DOM READY HANDLER ──
document.addEventListener("DOMContentLoaded", function () {
    // ═══════════════════════════════════════════════════════════
    // 1. CREATE & EDIT PAGE: Form Auto-Kalkulasi Total Biaya
    // ═══════════════════════════════════════════════════════════
    const qtyInput = document.getElementById("quantity");
    const priceDisplay = document.getElementById("price_display");
    const priceHidden = document.getElementById("price");
    const totalPreview = document.getElementById("totalPreview");

    if (qtyInput && priceDisplay && priceHidden && totalPreview) {
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function calculateTotal() {
            const qty = parseFloat(qtyInput.value) || 0;
            const rawPrice = priceDisplay.value.replace(/[^0-9]/g, "");
            const price = parseFloat(rawPrice) || 0;

            priceHidden.value = price;

            const total = qty * price;
            totalPreview.textContent = "Rp " + formatNumber(total);
        }

        priceDisplay.addEventListener("input", function () {
            let value = this.value.replace(/[^0-9]/g, "");
            if (value) {
                this.value = formatNumber(parseInt(value, 10));
            } else {
                this.value = "";
            }
            calculateTotal();
        });

        qtyInput.addEventListener("input", calculateTotal);

        // Initial calculation on load
        calculateTotal();
    }

    // ═══════════════════════════════════════════════════════════
    // 2. INDEX PAGE: DataTables & Filter Integration
    // ═══════════════════════════════════════════════════════════
    if (
        typeof jQuery !== "undefined" &&
        $("#operationalExpensesTable").length &&
        typeof $.fn.DataTable !== "undefined"
    ) {
        const table = $("#operationalExpensesTable").DataTable({
            responsive: true,
            order: [[1, "asc"]], // Sort by Tanggal asc
            rowCallback: function (row, data, displayNum, displayIndex) {
                $("td:first", row).html(displayIndex + 1);
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Cari data...",
                lengthMenu: "Tampilkan _MENU_ data",
                zeroRecords:
                    "Tidak ada data pengeluaran operasional yang cocok dengan pencarian",
                emptyTable: "Belum ada transaksi operasional yang tercatat.",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "«",
                    previous: "‹",
                    next: "›",
                    last: "»",
                },
            },
            pagingType: "full_numbers",
            columnDefs: [
                // No & Aksi: tidak bisa di-sort
                { targets: [0, 7], orderable: false },
                // Hanya kolom Keterangan (6) yang masuk child row (expand row)
                { targets: [6], className: "none" },
                // Aksi: prioritas tampil tertinggi (selalu visible)
                { targets: 7, responsivePriority: 1 },
                // No: prioritas kedua (membawa kontrol expand ▶ / ▼)
                { targets: 0, responsivePriority: 2 },
                // Tanggal: prioritas ketiga (selalu tampil)
                { targets: 1, responsivePriority: 3 },
            ],
            dom: '<"table-responsive-wrapper"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>',
        });

        // Custom Search Box Integration
        $("#dtSearchInput").on("keyup input", function () {
            table.search(this.value).draw();
        });

        // Modal Filter Integration (Date Range Filter)
        const btnApplyFilter = document.getElementById("btnApplyFilter");
        const btnResetFilter = document.getElementById("btnResetFilter");

        btnApplyFilter?.addEventListener("click", function () {
            const start =
                document.getElementById("modalFilterStartDate")?.value || "";
            const end =
                document.getElementById("modalFilterEndDate")?.value || "";

            const hasFilter = start || end;
            document
                .getElementById("activeFilterBadge")
                ?.classList.toggle("d-none", !hasFilter);

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== "operationalExpensesTable")
                    return true;
                const row = $(settings.nTable).find("tbody tr").eq(dataIndex);
                const rowDate = row.data("date") || "";
                if (start && rowDate < start) return false;
                if (end && rowDate > end) return false;
                return true;
            });
            table.draw();
            $.fn.dataTable.ext.search.pop();
        });

        btnResetFilter?.addEventListener("click", function () {
            const si = document.getElementById("modalFilterStartDate");
            const ei = document.getElementById("modalFilterEndDate");
            if (si) si.value = "";
            if (ei) ei.value = "";
            document
                .getElementById("activeFilterBadge")
                ?.classList.add("d-none");
            table.draw();
        });
    }
});
