/**
 * WARJOK — Manajemen Restock DataTables & Multi-Items Form JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

if (typeof window.restockProductsList === "undefined") {
    window.restockProductsList = [];
}

let restockItemIndex = 100;

/* ════════════════════════════════════════════════════════════
   SEARCHABLE SELECT
   ════════════════════════════════════════════════════════════ */
function _positionDropdownFixed(triggerEl, dropdown) {
    const rect = triggerEl.getBoundingClientRect();
    dropdown.style.top = rect.bottom + 2 + "px";
    dropdown.style.left = rect.left + "px";
    dropdown.style.width = rect.width + "px";
    dropdown.style.right = "auto";
    const dropH = dropdown.offsetHeight || 280;
    if (rect.bottom + dropH > window.innerHeight - 8) {
        dropdown.style.top = rect.top - dropH - 2 + "px";
    }
}

function toggleSearchableSelect(triggerEl, e) {
    if (e) e.stopPropagation();
    const wrapper = triggerEl.closest(".searchable-select-wrapper");
    if (!wrapper) return;
    const dropdown = wrapper.querySelector(".searchable-select-dropdown");
    const searchInput = wrapper.querySelector(".searchable-select-search");
    if (!dropdown) return;

    const row = triggerEl.closest("tr");
    const card = triggerEl.closest(".restock-item-card");
    const isOpen = dropdown.classList.contains("show");
    closeAllSearchableSelects();

    if (!isOpen) {
        if (row) {
            dropdown.classList.add("fixed-position");
            dropdown.classList.add("show");
            _positionDropdownFixed(triggerEl, dropdown);
            dropdown._fixedTrigger = triggerEl;
        } else {
            dropdown.classList.add("show");
        }
        triggerEl.classList.add("active");
        if (card) card.classList.add("has-open-dropdown");
        if (searchInput) {
            searchInput.value = "";
            filterSearchableOptions(searchInput);
            setTimeout(() => searchInput.focus(), 50);
        }
    }
}

function filterSearchableOptions(inputEl) {
    const wrapper = inputEl.closest(".searchable-select-wrapper");
    if (!wrapper) return;
    const q = inputEl.value.toLowerCase().trim();
    const options = wrapper.querySelectorAll(".searchable-select-option");
    const optionsContainer = wrapper.querySelector(
        ".searchable-select-options",
    );
    let hasResult = false;

    options.forEach((opt) => {
        const label = (opt.dataset.label || opt.textContent).toLowerCase();
        const match = label.includes(q);
        opt.style.display = match ? "" : "none";
        if (match) hasResult = true;
    });

    let noResult = optionsContainer.querySelector(
        ".searchable-select-no-results",
    );
    if (!hasResult) {
        if (!noResult) {
            noResult = document.createElement("div");
            noResult.className = "searchable-select-no-results";
            noResult.textContent = "Produk tidak ditemukan";
            optionsContainer.appendChild(noResult);
        }
        noResult.style.display = "";
    } else if (noResult) {
        noResult.style.display = "none";
    }
}

function selectSearchableOption(optionEl) {
    const wrapper = optionEl.closest(".searchable-select-wrapper");
    if (!wrapper) return;
    const val = optionEl.dataset.value;
    const label = optionEl.dataset.label || optionEl.textContent;
    const hiddenSel = wrapper.querySelector(".product-select");
    const trigger = wrapper.querySelector(".searchable-select-trigger");
    const dropdown = wrapper.querySelector(".searchable-select-dropdown");

    if (hiddenSel) {
        hiddenSel.value = val;
        onProductSelectChange(hiddenSel);
    }
    if (trigger) {
        trigger.innerHTML = '<span class="selected-text">' + label + "</span>";
        trigger.classList.remove("active", "is-invalid");
    }

    const options = wrapper.querySelectorAll(".searchable-select-option");
    options.forEach((o) => o.classList.remove("selected"));
    optionEl.classList.add("selected");

    if (dropdown) {
        dropdown.classList.remove("show", "fixed-position");
        dropdown.style.cssText = "";
        dropdown._fixedTrigger = null;
    }
    const row = optionEl.closest("tr");
    const card = optionEl.closest(".restock-item-card");
    if (row) row.classList.remove("has-open-dropdown");
    if (card) card.classList.remove("has-open-dropdown");
}

function closeAllSearchableSelects() {
    document
        .querySelectorAll(".searchable-select-dropdown.show")
        .forEach((d) => {
            d.classList.remove("show", "fixed-position");
            d.style.cssText = "";
            d._fixedTrigger = null;
        });
    document
        .querySelectorAll(".searchable-select-trigger.active")
        .forEach((t) => t.classList.remove("active"));
    document
        .querySelectorAll(
            "tr.has-open-dropdown, .restock-item-card.has-open-dropdown",
        )
        .forEach((r) => r.classList.remove("has-open-dropdown"));
}

function _repositionOpenFixedDropdowns() {
    document
        .querySelectorAll(".searchable-select-dropdown.show.fixed-position")
        .forEach((d) => {
            if (d._fixedTrigger) _positionDropdownFixed(d._fixedTrigger, d);
        });
}
window.addEventListener("scroll", _repositionOpenFixedDropdowns, true);
window.addEventListener("resize", _repositionOpenFixedDropdowns);

if (!window.__restockSearchableSelectInit) {
    window.__restockSearchableSelectInit = true;
    ["click", "mousedown", "touchstart", "focusin"].forEach((ev) => {
        document.addEventListener(
            ev,
            function (e) {
                if (!e.target.closest(".searchable-select-wrapper"))
                    closeAllSearchableSelects();
            },
            true,
        );
    });
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeAllSearchableSelects();
    });
}

/* ════════════════════════════════════════════════════════════
   BUILD HELPERS
   ════════════════════════════════════════════════════════════ */
function buildSelectOptionsHtml() {
    let selectOpts =
        '<option value="" disabled selected>Pilih Produk...</option>';
    let divOpts = "";

    if (
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
            const label = `${p.prod_name} (${sku}) - Stok: ${stock} ${unitName}`;

            selectOpts += `<option value="${p.id}" data-hpp-method="${p.hpp_method}" data-cost="${cost}" data-stock="${stock}" data-unit="${unitName}">${label}</option>`;
            divOpts += `<div class="searchable-select-option" data-value="${p.id}" data-label="${label}" onclick="selectSearchableOption(this)">${label}</div>`;
        });
    }
    return { selectOpts, divOpts };
}

function buildSearchableSelectHtml(idx, selectOpts, divOpts) {
    return `
        <div class="searchable-select-wrapper">
            <select name="items[${idx}][product_id]" class="d-none product-select" onchange="onProductSelectChange(this)">
                ${selectOpts}
            </select>
            <div class="searchable-select-trigger" onclick="toggleSearchableSelect(this, event)" tabindex="0" role="combobox">
                <span class="placeholder-text">Pilih Produk...</span>
            </div>
            <div class="searchable-select-dropdown">
                <div class="searchable-select-search-wrap">
                    <input type="text" class="searchable-select-search" oninput="filterSearchableOptions(this)" placeholder="Cari produk..." autocomplete="off">
                </div>
                <div class="searchable-select-options">${divOpts}</div>
            </div>
        </div>`;
}

/* ════════════════════════════════════════════════════════════
   ADD / REMOVE ROWS & CARDS
   ════════════════════════════════════════════════════════════ */
function addRestockTableRow(idx, selectOpts, divOpts) {
    const tbody = document.getElementById("restockItemRows");
    if (!tbody) return;
    document.getElementById("emptyItemRow")?.remove();

    const tr = document.createElement("tr");
    tr.className = "restock-item-row align-middle";
    tr.innerHTML = `
        <td class="row-number text-center text-muted fw-medium small col-no"></td>
        <td class="col-product">${buildSearchableSelectHtml(idx, selectOpts, divOpts)}</td>
        <td class="text-center col-info">
            <div class="item-info-cell current-stock-cell">
                <div class="item-info-stock">Stok: -</div>
                <div class="item-info-hpp">HPP: -</div>
            </div>
        </td>
        <td class="col-qty">
            <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" min="1" oninput="onItemQtyOrPriceChange(this)">
        </td>
        <td class="col-price">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0">Rp</span>
                <input type="number" name="items[${idx}][unit_price]" class="form-control form-control-sm font-monospace text-end rounded-end-2 item-unit-price" min="0" oninput="onItemQtyOrPriceChange(this)" readonly>
            </div>
            <div class="unit-price-hint text-muted text-end small"></div>
        </td>
        <td class="text-end font-monospace fw-bold text-dark item-subtotal-cell col-subtotal">Rp 0</td>
        <td class="text-center col-action">
            <button type="button" class="btn-delete-row" onclick="removeRestockItemRow(this)" title="Hapus Baris">
                <i class="bi bi-trash3-fill"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
}

function addRestockMobileCard(idx, selectOpts, divOpts) {
    const container = document.getElementById("restockCardsMobile");
    if (!container) return;
    document.getElementById("mobileEmptyState")?.remove();

    const card = document.createElement("div");
    card.className = "restock-item-card";
    card.dataset.cardIdx = idx;
    card.innerHTML = `
        <div class="card-header-row">
            <div class="card-num-badge row-number-mobile">-</div>
            <div class="card-product-wrap">${buildSearchableSelectHtml(idx, selectOpts, divOpts)}</div>
            <div class="card-delete-btn">
                <button type="button" class="btn-delete-row" onclick="removeRestockItemCard(this)" title="Hapus">
                    <i class="bi bi-trash3-fill"></i>
                </button>
            </div>
        </div>
        <div class="card-info-row">
            <span class="card-info-stock current-stock-cell-mobile">Stok: -</span>
            <span class="card-info-divider"></span>
            <span class="card-info-hpp hpp-method-cell-mobile">HPP: -</span>
        </div>
        <div class="card-fields-row">
            <div class="card-field">
                <label>Jumlah Masuk</label>
                <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" min="1" oninput="onItemQtyOrPriceChange(this)">
            </div>
            <div class="card-field">
                <label>Harga Modal / Unit</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0">Rp</span>
                    <input type="number" name="items[${idx}][unit_price]" class="form-control form-control-sm font-monospace text-end item-unit-price" min="0" oninput="onItemQtyOrPriceChange(this)" readonly>
                </div>
                <div class="unit-price-hint text-muted text-end" style="font-size:0.68rem;margin-top:2px;"></div>
            </div>
            <div class="card-subtotal">
                <span class="card-subtotal-label">Subtotal</span>
                <span class="card-subtotal-value item-subtotal-cell">Rp 0</span>
            </div>
        </div>`;
    container.appendChild(card);
}

function addRestockItemRow() {
    const { selectOpts, divOpts } = buildSelectOptionsHtml();
    addRestockTableRow(restockItemIndex, selectOpts, divOpts);
    addRestockMobileCard(restockItemIndex, selectOpts, divOpts);
    restockItemIndex++;
    updateRowNumbers();
    recalculateRestockTotals();
}

function removeRestockItemRow(btn) {
    const row = btn.closest("tr");
    if (!row) return;
    const select = row.querySelector(".product-select");
    if (select) {
        const m = select.getAttribute("name")?.match(/items\[(\d+)\]/);
        if (m)
            document
                .querySelector(`.restock-item-card[data-card-idx="${m[1]}"]`)
                ?.remove();
    }
    row.remove();
    _checkEmptyStates();
    updateRowNumbers();
    recalculateRestockTotals();
}

function removeRestockItemCard(btn) {
    const card = btn.closest(".restock-item-card");
    if (!card) return;
    const idx = card.dataset.cardIdx;
    const sel = document.querySelector(
        `#restockItemRows .product-select[name="items[${idx}][product_id]"]`,
    );
    if (sel) sel.closest("tr")?.remove();
    card.remove();
    _checkEmptyStates();
    updateRowNumbers();
    recalculateRestockTotals();
}

function _checkEmptyStates() {
    const tbody = document.getElementById("restockItemRows");
    if (tbody && tbody.querySelectorAll(".restock-item-row").length === 0) {
        tbody.innerHTML = `<tr id="emptyItemRow"><td colspan="7" class="text-center py-4 text-muted small">Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item.</td></tr>`;
    }
    const mob = document.getElementById("restockCardsMobile");
    if (mob && mob.querySelectorAll(".restock-item-card").length === 0) {
        if (!document.getElementById("mobileEmptyState")) {
            const el = document.createElement("div");
            el.className = "restock-mobile-empty";
            el.id = "mobileEmptyState";
            el.textContent =
                'Belum ada produk yang ditambahkan. Klik tombol "+Tambah" di atas.';
            mob.appendChild(el);
        }
    }
}

function updateRowNumbers() {
    document
        .querySelectorAll("#restockItemRows .restock-item-row")
        .forEach((row, i) => {
            const c = row.querySelector(".row-number");
            if (c) c.textContent = i + 1;
        });
    document.querySelectorAll(".restock-item-card").forEach((card, i) => {
        const b = card.querySelector(".row-number-mobile");
        if (b) b.textContent = i + 1;
    });
}

function onProductSelectChange(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt) return;
    const hppMethod = opt.getAttribute("data-hpp-method");
    const cost = parseFloat(opt.getAttribute("data-cost")) || 0;
    const stock = opt.getAttribute("data-stock") || "0";
    const unit = opt.getAttribute("data-unit") || "Pcs";
    const isAuto = hppMethod === "calculated";

    const row = selectEl.closest("tr");
    if (row) _applyProductToTableRow(row, { stock, unit, cost, isAuto });

    const m = selectEl.getAttribute("name")?.match(/items\[(\d+)\]/);
    if (m) {
        const card = document.querySelector(
            `.restock-item-card[data-card-idx="${m[1]}"]`,
        );
        if (card)
            _applyProductToMobileCard(card, { stock, unit, cost, isAuto });
    }
    onItemQtyOrPriceChange(selectEl);
}

function _applyProductToTableRow(row, { stock, unit, cost, isAuto }) {
    const stockDiv = row.querySelector(".item-info-stock");
    const hppDiv = row.querySelector(".item-info-hpp");
    if (stockDiv) stockDiv.textContent = "Stok: " + stock + " " + unit;
    if (hppDiv) hppDiv.textContent = "HPP: " + (isAuto ? "Otomatis" : "Manual");

    const priceInput = row.querySelector(".item-unit-price");
    const hintDiv = row.querySelector(".unit-price-hint");
    if (priceInput) {
        priceInput.value = Math.round(cost);
        priceInput.readOnly = isAuto;
        priceInput.classList.toggle("bg-light", isAuto);
    }
    if (hintDiv)
        hintDiv.textContent = isAuto
            ? "Otomatis dari HPP"
            : "Dapat disesuaikan";
}

function _applyProductToMobileCard(card, { stock, unit, cost, isAuto }) {
    const stockEl = card.querySelector(".current-stock-cell-mobile");
    const hppEl = card.querySelector(".hpp-method-cell-mobile");
    if (stockEl) stockEl.textContent = "Stok: " + stock + " " + unit;
    if (hppEl) hppEl.textContent = "HPP: " + (isAuto ? "Otomatis" : "Manual");

    const priceInput = card.querySelector(".item-unit-price");
    const hintDiv = card.querySelector(".unit-price-hint");
    if (priceInput) {
        priceInput.value = Math.round(cost);
        priceInput.readOnly = isAuto;
        priceInput.classList.toggle("bg-light", isAuto);
    }
    if (hintDiv)
        hintDiv.textContent = isAuto
            ? "Otomatis dari HPP"
            : "Dapat disesuaikan";
}

function onItemQtyOrPriceChange(inputEl) {
    const row = inputEl.closest("tr");
    const card = inputEl.closest(".restock-item-card");
    const container = row || card;
    if (!container) return;

    const qtyInput = container.querySelector(".item-qty");
    const priceInput = container.querySelector(".item-unit-price");
    const subtotalCell = container.querySelector(".item-subtotal-cell");

    const qty = parseInt(qtyInput?.value || 0, 10) || 0;
    const price = parseFloat(priceInput?.value || 0) || 0;
    const subtotal = qty * price;
    const formatted = "Rp " + Math.round(subtotal).toLocaleString("id-ID");

    if (subtotalCell) subtotalCell.textContent = formatted;

    const nameAttr = (qtyInput || priceInput)?.getAttribute("name");
    const m = nameAttr?.match(/items\[(\d+)\]/);
    if (m) {
        const idx = m[1];
        if (row) {
            const pairedCard = document.querySelector(
                `.restock-item-card[data-card-idx="${idx}"]`,
            );
            if (pairedCard) {
                if (pairedCard.querySelector(".item-qty"))
                    pairedCard.querySelector(".item-qty").value =
                        qtyInput?.value;
                if (pairedCard.querySelector(".item-unit-price"))
                    pairedCard.querySelector(".item-unit-price").value =
                        priceInput?.value;
                const ps = pairedCard.querySelector(".item-subtotal-cell");
                if (ps) ps.textContent = formatted;
            }
        } else if (card) {
            const sel = document.querySelector(
                `#restockItemRows .product-select[name="items[${idx}][product_id]"]`,
            );
            if (sel) {
                const pairedRow = sel.closest("tr");
                if (pairedRow) {
                    if (pairedRow.querySelector(".item-qty"))
                        pairedRow.querySelector(".item-qty").value =
                            qtyInput?.value;
                    if (pairedRow.querySelector(".item-unit-price"))
                        pairedRow.querySelector(".item-unit-price").value =
                            priceInput?.value;
                    const ps = pairedRow.querySelector(".item-subtotal-cell");
                    if (ps) ps.textContent = formatted;
                }
            }
        }
    }
    recalculateRestockTotals();
}

function recalculateRestockTotals() {
    const rows = document.querySelectorAll(
        "#restockItemRows .restock-item-row",
    );
    let totalItems = 0,
        totalQty = 0,
        grandTotalValue = 0;

    rows.forEach((row) => {
        const productSelect = row.querySelector(".product-select");
        if (productSelect && productSelect.value) {
            totalItems++;
            const qty =
                parseInt(row.querySelector(".item-qty")?.value || 0, 10) || 0;
            const price =
                parseFloat(row.querySelector(".item-unit-price")?.value || 0) ||
                0;
            totalQty += qty;
            grandTotalValue += qty * price;
        }
    });

    const di = document.getElementById("displayTotalItems");
    const dq = document.getElementById("displayTotalQty");
    const dg = document.getElementById("displayGrandTotal");
    if (di) di.textContent = totalItems + " Produk";
    if (dq) dq.textContent = totalQty + " Unit";
    if (dg)
        dg.textContent =
            "Rp " + Math.round(grandTotalValue).toLocaleString("id-ID");
}

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
    if (modalEl && typeof bootstrap !== "undefined")
        new bootstrap.Modal(modalEl).show();
}

/* ════════════════════════════════════════════════════════════
   EXPOSE GLOBALS
   ════════════════════════════════════════════════════════════ */
window.toggleSearchableSelect = toggleSearchableSelect;
window.filterSearchableOptions = filterSearchableOptions;
window.selectSearchableOption = selectSearchableOption;
window.closeAllSearchableSelects = closeAllSearchableSelects;
window.addRestockItemRow = addRestockItemRow;
window.removeRestockItemRow = removeRestockItemRow;
window.removeRestockItemCard = removeRestockItemCard;
window.updateRowNumbers = updateRowNumbers;
window.onProductSelectChange = onProductSelectChange;
window.onItemQtyOrPriceChange = onItemQtyOrPriceChange;
window.recalculateRestockTotals = recalculateRestockTotals;
window.openDeleteModal = openDeleteModal;

/* ════════════════════════════════════════════════════════════
   DOM READY
   ════════════════════════════════════════════════════════════ */
document.addEventListener("DOMContentLoaded", function () {
    const restockTbody = document.getElementById("restockItemRows");
    if (restockTbody) {
        const existing = restockTbody.querySelectorAll(".restock-item-row");
        if (existing.length === 0) {
            addRestockItemRow();
        } else {
            updateRowNumbers();
            recalculateRestockTotals();
        }
    }

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
});

/* ════════════════════════════════════════════════════════════
   DATATABLES — INDEX PAGE (Riwayat Restock)
   Mobile (< 768px) : responsive false → card HTML lama tetap tampil
   Desktop (≥ 768px): responsive inline child row
   Kolom disembunyikan duluan: Total Nilai Biaya (5) + Petugas (6)
   ════════════════════════════════════════════════════════════ */
if (typeof jQuery !== "undefined") {
    jQuery(document).ready(function ($) {
        if ($("#restockDataTable").length === 0) return;

        const isMobile = window.innerWidth < 768;

        const dataTable = $("#restockDataTable").DataTable({
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

            paging: !isMobile,
            info: !isMobile,
            order: [[2, "desc"]],

            columnDefs: [
                { targets: "no-sort", orderable: false },
                // Prioritas — makin kecil = makin dipertahankan
                { targets: 0, responsivePriority: 1 }, // No
                { targets: 1, responsivePriority: 2 }, // Kode Restock
                { targets: 2, responsivePriority: 3 }, // Tanggal
                { targets: 3, responsivePriority: 4 }, // Supplier
                { targets: 4, responsivePriority: 5 }, // Total Item Masuk
                { targets: 5, responsivePriority: 11 }, // Total Nilai Biaya — disembunyikan duluan
                { targets: 6, responsivePriority: 12 }, // Petugas           — disembunyikan duluan
                { targets: 7, responsivePriority: 1 }, // Aksi              — selalu tampil
            ],

            scrollX: false,
            autoWidth: false,

            language: {
                emptyTable:
                    "Belum ada riwayat transaksi restock. Klik tombol 'Tambah Restock' untuk mencatat transaksi baru.",
                zeroRecords:
                    "Tidak ada transaksi restock yang cocok dengan pencarian",
                info: "Menampilkan _START_–_END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                paginate: { first: "«", previous: "‹", next: "›", last: "»" },
            },
            pagingType: "full_numbers",
            dom: isMobile
                ? "t"
                : '<"table-responsive-wrapper"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>',

            pageLength: 10,
        });

        // Resize handler
        let resizeTimer;
        $(window).on("resize", function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                const nowMobile = window.innerWidth < 768;
                if (nowMobile !== isMobile) {
                    dataTable.destroy();
                    // reinit via jQuery ready pattern
                    jQuery(document).ready(function ($) {});
                }
            }, 300);
        });

        // Search
        $("#dtSearchInput").on("keyup input", function () {
            dataTable.search(this.value).draw();
        });

        // Filter apply
        $("#btnApplyFilter").on("click", function () {
            applyFilters();
        });

        // Filter reset
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

            dataTable.column(3).search(supplier ? supplier : "", true, false);

            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
                (fn) => fn.name !== "restockDateRangeFilter",
            );

            if (startDate || endDate) {
                const restockDateRangeFilter = function (
                    settings,
                    data,
                    dataIndex,
                ) {
                    if (settings.nTable.id !== "restockDataTable") return true;
                    const rawDate = $(dataTable.row(dataIndex).node()).attr(
                        "data-date",
                    );
                    if (!rawDate) return true;
                    if (startDate && rawDate < startDate) return false;
                    if (endDate && rawDate > endDate) return false;
                    return true;
                };
                $.fn.dataTable.ext.search.push(restockDateRangeFilter);
            }

            dataTable.draw();

            if (supplier || startDate || endDate) {
                $("#activeFilterBadge").removeClass("d-none");
            } else {
                $("#activeFilterBadge").addClass("d-none");
            }
        }
    });
}
