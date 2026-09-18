/**
 * WARJOK — Manajemen Restock DataTables & Multi-Items Form JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

// Global product list fallback
if (typeof window.restockProductsList === "undefined") {
    window.restockProductsList = [];
}

let restockItemIndex = 100;

// ── Multi-Item Repeater Logic for Create Restock ── //
function addRestockItemRow() {
    const tbody = document.getElementById("restockItemRows");
    if (!tbody) return;

    // Remove empty placeholder row if exists
    const emptyRow = document.getElementById("emptyItemRow");
    if (emptyRow) emptyRow.remove();

    const tr = document.createElement("tr");
    tr.className = "restock-item-row align-middle";

    let optionsHtml =
        '<option value="" disabled selected>Pilih Produk...</option>';
    if (
        window.restockProductsList &&
        Array.isArray(window.restockProductsList) &&
        window.restockProductsList.length > 0
    ) {
        window.restockProductsList.forEach((p) => {
            const unitName =
                p.unit && (p.unit.short_name || p.unit.unit_name)
                    ? p.unit.short_name || p.unit.unit_name
                    : "Pcs";
            const cost = Number(p.current_hpp || p.unit_price || 0);
            const sku = p.sku || "PRD-" + p.id;
            const stock =
                p.current_stock !== undefined && p.current_stock !== null
                    ? p.current_stock
                    : 0;
            optionsHtml += `<option value="${p.id}" data-hpp-method="${p.hpp_method}" data-cost="${cost}" data-stock="${stock}" data-unit="${unitName}">${p.prod_name} (${sku}) - Stok: ${stock} ${unitName}</option>`;
        });
    }

    tr.innerHTML = `
        <td class="row-number text-center text-muted fw-medium small"></td>
        <td>
            <select name="items[${restockItemIndex}][product_id]" class="form-select form-select-sm rounded-2 product-select" onchange="onProductSelectChange(this)" required>
                ${optionsHtml}
            </select>
        </td>
        <td class="text-center current-stock-cell text-muted small">-</td>
        <td class="text-center hpp-method-cell text-muted small">-</td>
        <td>
            <input type="number" name="items[${restockItemIndex}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" min="1" oninput="onItemQtyOrPriceChange(this)" required>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0">Rp</span>
                <input type="number" name="items[${restockItemIndex}][unit_price]" class="form-control form-control-sm font-monospace text-end rounded-end-2 item-unit-price" min="0" oninput="onItemQtyOrPriceChange(this)" readonly required>
            </div>
            <div class="unit-price-hint text-muted text-end small" style="font-size: 0.7rem;"></div>
        </td>
        <td class="text-end font-monospace fw-bold text-dark item-subtotal-cell">
            Rp 0
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 shadow-none" onclick="removeRestockItemRow(this)" title="Hapus Baris">
                <i class="bi bi-x-circle-fill fs-5"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    restockItemIndex++;
    updateRowNumbers();
    recalculateRestockTotals();
}

function removeRestockItemRow(btn) {
    const row = btn.closest("tr");
    if (row) row.remove();

    const tbody = document.getElementById("restockItemRows");
    if (tbody && tbody.querySelectorAll(".restock-item-row").length === 0) {
        tbody.innerHTML = `
            <tr id="emptyItemRow">
                <td colspan="8" class="text-center py-4 text-muted small">
                    Belum ada produk yang ditambahkan. Klik tombol "+ Tambah Produk" di atas untuk menambahkan item.
                </td>
            </tr>
        `;
    }

    updateRowNumbers();
    recalculateRestockTotals();
}

function updateRowNumbers() {
    const rows = document.querySelectorAll(".restock-item-row");
    rows.forEach((row, idx) => {
        const numCell = row.querySelector(".row-number");
        if (numCell) numCell.textContent = idx + 1;
    });
}

function onProductSelectChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const row = selectEl.closest("tr");
    if (!row || !selectedOption) return;

    const hppMethod = selectedOption.getAttribute("data-hpp-method");
    const cost = parseFloat(selectedOption.getAttribute("data-cost")) || 0;
    const stock = selectedOption.getAttribute("data-stock") || "0";
    const unit = selectedOption.getAttribute("data-unit") || "Pcs";

    const stockCell = row.querySelector(".current-stock-cell");
    const hppMethodCell = row.querySelector(".hpp-method-cell");
    const priceInput = row.querySelector(".item-unit-price");
    const hintDiv = row.querySelector(".unit-price-hint");

    if (stockCell) stockCell.textContent = stock + " " + unit;
    if (hppMethodCell)
        hppMethodCell.textContent =
            hppMethod === "calculated" ? "Otomatis" : "Manual";
    if (priceInput) {
        priceInput.value = Math.round(cost);
        if (hppMethod === "calculated") {
            priceInput.readOnly = true;
            priceInput.classList.add("bg-light");
            if (hintDiv) hintDiv.textContent = "Otomatis dari HPP";
        } else {
            priceInput.readOnly = false;
            priceInput.classList.remove("bg-light");
            if (hintDiv) hintDiv.textContent = "Dapat disesuaikan";
        }
    }

    onItemQtyOrPriceChange(selectEl);
}

function onItemQtyOrPriceChange(inputEl) {
    const row = inputEl.closest("tr");
    if (!row) return;

    const qtyInput = row.querySelector(".item-qty");
    const priceInput = row.querySelector(".item-unit-price");
    const subtotalCell = row.querySelector(".item-subtotal-cell");

    const qty = parseInt(qtyInput ? qtyInput.value : 0, 10) || 0;
    const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
    const subtotal = qty * price;

    if (subtotalCell) {
        subtotalCell.textContent =
            "Rp " + Math.round(subtotal).toLocaleString("id-ID");
    }

    recalculateRestockTotals();
}

function recalculateRestockTotals() {
    const rows = document.querySelectorAll(".restock-item-row");
    let totalItems = 0;
    let totalQty = 0;
    let grandTotalValue = 0;

    rows.forEach((row) => {
        const productSelect = row.querySelector(".product-select");
        if (productSelect && productSelect.value) {
            totalItems++;
            const qtyInput = row.querySelector(".item-qty");
            const priceInput = row.querySelector(".item-unit-price");
            const qty = parseInt(qtyInput ? qtyInput.value : 0, 10) || 0;
            const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
            totalQty += qty;
            grandTotalValue += qty * price;
        }
    });

    const displayTotalItems = document.getElementById("displayTotalItems");
    const displayTotalQty = document.getElementById("displayTotalQty");
    const displayGrandTotal = document.getElementById("displayGrandTotal");

    if (displayTotalItems)
        displayTotalItems.textContent = totalItems + " Produk";
    if (displayTotalQty) displayTotalQty.textContent = totalQty + " Unit";
    if (displayGrandTotal)
        displayGrandTotal.textContent =
            "Rp " + Math.round(grandTotalValue).toLocaleString("id-ID");
}

// Delete / Rollback Confirmation Modal
function openDeleteModal(id, code, itemCount, totalValue) {
    const codeEl = document.getElementById("deleteRestockCode");
    const formEl = document.getElementById("deleteRestockForm");
    if (codeEl) codeEl.textContent = code;
    if (formEl) formEl.action = "/restock/" + id;

    const countSpan = document.getElementById("deleteRestockItemCount");
    const valueSpan = document.getElementById("deleteRestockTotalValue");
    if (countSpan) countSpan.textContent = itemCount;
    if (valueSpan)
        valueSpan.textContent =
            "Rp " + Number(totalValue).toLocaleString("id-ID");

    const modalEl = document.getElementById("modalDeleteRestock");
    if (modalEl && typeof bootstrap !== "undefined") {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

// Expose functions to window object
window.addRestockItemRow = addRestockItemRow;
window.removeRestockItemRow = removeRestockItemRow;
window.updateRowNumbers = updateRowNumbers;
window.onProductSelectChange = onProductSelectChange;
window.onItemQtyOrPriceChange = onItemQtyOrPriceChange;
window.recalculateRestockTotals = recalculateRestockTotals;
window.openDeleteModal = openDeleteModal;

// Initialize on DOM ready
document.addEventListener("DOMContentLoaded", function () {
    // 1. If on Create Form
    const restockTbody = document.getElementById("restockItemRows");
    if (restockTbody) {
        const existingRows = restockTbody.querySelectorAll(".restock-item-row");
        if (existingRows.length === 0) {
            addRestockItemRow();
        } else {
            updateRowNumbers();
            recalculateRestockTotals();
        }
    }

    // 2. Auto dismiss alerts after 5s
    const alerts = document.querySelectorAll(".alert-dismissible");
    if (alerts.length > 0) {
        setTimeout(function () {
            alerts.forEach((alert) => {
                if (typeof bootstrap !== "undefined" && bootstrap.Alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } else {
                    alert.style.display = "none";
                }
            });
        }, 5000);
    }
});

// jQuery / DataTables handling for Index page
if (typeof jQuery !== "undefined") {
    jQuery(document).ready(function ($) {
        if ($("#restockDataTable").length > 0) {
            const dataTable = $("#restockDataTable").DataTable({
                responsive: false,
                order: [[2, "desc"]], // Sort by Restock Date by default
                columnDefs: [{ targets: "no-sort", orderable: false }],
                language: {
                    emptyTable:
                        "Belum ada riwayat transaksi restock. Klik tombol 'Tambah Restock' untuk mencatat transaksi baru.",
                    zeroRecords:
                        "Tidak ada transaksi restock yang cocok dengan pencarian",
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
                dom: '<"table-responsive"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>',
                pageLength: 10,
            });

            // Custom Search Input binding
            $("#dtSearchInput").on("keyup input", function () {
                dataTable.search(this.value).draw();
            });

            // Modal Filter Action: Apply
            $("#btnApplyFilter").on("click", function () {
                applyFilters();
            });

            // Modal Filter Action: Reset
            $("#btnResetFilter").on("click", function () {
                $("#modalFilterSupplier").val("");
                $("#modalFilterStartDate").val("");
                $("#modalFilterEndDate").val("");
                applyFilters();
            });

            function applyFilters() {
                const supplier = $("#modalFilterSupplier").val();
                const startDate = $("#modalFilterStartDate").val();
                const endDate = $("#modalFilterEndDate").val();

                // Supplier Filter (col index 3: Supplier)
                dataTable
                    .column(3)
                    .search(supplier ? supplier : "", true, false);

                // Date Range Custom Search
                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
                    (fn) => fn.name !== "restockDateRangeFilter",
                );
                if (startDate || endDate) {
                    const restockDateRangeFilter = function (
                        settings,
                        data,
                        dataIndex,
                    ) {
                        if (settings.nTable.id !== "restockDataTable")
                            return true;
                        const rowNode = dataTable.row(dataIndex).node();
                        const rawDate = $(rowNode).attr("data-date"); // YYYY-MM-DD
                        if (!rawDate) return true;

                        if (startDate && rawDate < startDate) return false;
                        if (endDate && rawDate > endDate) return false;
                        return true;
                    };
                    $.fn.dataTable.ext.search.push(restockDateRangeFilter);
                }

                dataTable.draw();

                // Active filter badge indicator
                if (supplier || startDate || endDate) {
                    $("#activeFilterBadge").removeClass("d-none");
                } else {
                    $("#activeFilterBadge").addClass("d-none");
                }
            }
        }
    });
}
