/**
 * WARJOK — Manajemen Laporan Penjualan DataTables & Form JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

if (typeof window.reportProductsList === "undefined") {
    window.reportProductsList = [];
}

let reportItemIndex = 100;

/* ════════════════════════════════════════════════════════════
   SEARCHABLE SELECT
   ════════════════════════════════════════════════════════════ */
function positionDropdown(triggerEl, dropdown) {
    const rect = triggerEl.getBoundingClientRect();
    dropdown.style.position = "fixed";
    dropdown.style.top = rect.bottom + 2 + "px";
    dropdown.style.left = rect.left + "px";
    dropdown.style.width = rect.width + "px";
    dropdown.style.zIndex = "99999";
}

function toggleSearchableSelect(triggerEl, e) {
    if (e) e.stopPropagation();
    const wrapper = triggerEl.closest(".searchable-select-wrapper");
    if (!wrapper) return;
    const dropdown = wrapper.querySelector(".searchable-select-dropdown");
    const searchInput = wrapper.querySelector(".searchable-select-search");
    if (!dropdown) return;

    const row = triggerEl.closest("tr");
    const card = triggerEl.closest(".report-item-card");
    const isOpen = dropdown.classList.contains("show");
    closeAllSearchableSelects();

    if (!isOpen) {
        dropdown._sourceWrapper = wrapper;
        dropdown._sourceTrigger = triggerEl;
        document.body.appendChild(dropdown);
        positionDropdown(triggerEl, dropdown);
        dropdown.classList.add("show");
        triggerEl.classList.add("active");
        if (row) row.classList.add("has-open-dropdown");
        if (card) card.classList.add("has-open-dropdown");

        if (searchInput) {
            searchInput.value = "";
            filterSearchableOptions(searchInput);
            setTimeout(() => searchInput.focus(), 50);
        }

        dropdown._repositionHandler = () =>
            positionDropdown(triggerEl, dropdown);
        window.addEventListener("scroll", dropdown._repositionHandler, true);
        window.addEventListener("resize", dropdown._repositionHandler);
    }
}

function filterSearchableOptions(inputEl) {
    const dropdown = inputEl.closest(".searchable-select-dropdown");
    if (!dropdown) return;
    const q = inputEl.value.toLowerCase().trim();
    const options = dropdown.querySelectorAll(".searchable-select-option");
    const optionsContainer = dropdown.querySelector(
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
    const dropdown = optionEl.closest(".searchable-select-dropdown");
    if (!dropdown) return;
    const wrapper = dropdown._sourceWrapper;
    const trigger = dropdown._sourceTrigger;
    if (!wrapper || !trigger) return;

    const val = optionEl.dataset.value;
    const label = optionEl.dataset.label || optionEl.textContent.trim();
    const hiddenSel = wrapper.querySelector(".product-select");

    const currentRow = wrapper.closest("tr");
    const currentCard = wrapper.closest(".report-item-card");
    let isDuplicate = false;
    document
        .querySelectorAll(".report-item-row, .report-item-card")
        .forEach((el) => {
            if (el !== currentRow && el !== currentCard) {
                const sel = el.querySelector(".product-select");
                if (sel && sel.value === val) isDuplicate = true;
            }
        });

    if (isDuplicate) {
        alert(
            "Produk ini sudah dipilih pada baris lain. Silakan pilih produk yang berbeda.",
        );
        closeAllSearchableSelects();
        return;
    }

    if (hiddenSel) {
        hiddenSel.value = val;
        onProductSelectChange(hiddenSel);
    }
    if (trigger) {
        trigger.innerHTML = '<span class="selected-text">' + label + "</span>";
        trigger.classList.remove("active", "is-invalid");
    }

    dropdown
        .querySelectorAll(".searchable-select-option")
        .forEach((o) => o.classList.remove("selected"));
    optionEl.classList.add("selected");
    closeAllSearchableSelects();
}

function closeAllSearchableSelects() {
    document
        .querySelectorAll(".searchable-select-dropdown.show")
        .forEach((d) => {
            d.classList.remove("show");
            d.style.cssText = "";
            if (d._sourceWrapper) {
                d._sourceWrapper.appendChild(d);
                d._sourceWrapper = null;
            }
            d._sourceTrigger = null;
            if (d._repositionHandler) {
                window.removeEventListener(
                    "scroll",
                    d._repositionHandler,
                    true,
                );
                window.removeEventListener("resize", d._repositionHandler);
                d._repositionHandler = null;
            }
        });
    document
        .querySelectorAll(".searchable-select-trigger.active")
        .forEach((t) => t.classList.remove("active"));
    document
        .querySelectorAll(
            "tr.has-open-dropdown, .report-item-card.has-open-dropdown",
        )
        .forEach((r) => r.classList.remove("has-open-dropdown"));
}

if (!window.__reportsSearchableSelectInit) {
    window.__reportsSearchableSelectInit = true;
    ["click", "mousedown", "touchstart", "focusin"].forEach((ev) => {
        document.addEventListener(
            ev,
            function (e) {
                const insideWrapper = e.target.closest(
                    ".searchable-select-wrapper",
                );
                const insideDropdown = e.target.closest(
                    ".searchable-select-dropdown",
                );
                if (!insideWrapper && !insideDropdown)
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
function _buildReportSelectOptions() {
    let selectOpts =
        '<option value="" disabled selected>Pilih Produk...</option>';
    let divOpts = "";
    if (
        Array.isArray(window.reportProductsList) &&
        window.reportProductsList.length > 0
    ) {
        window.reportProductsList.forEach((p) => {
            const unitName =
                p.unit && (p.unit.short_name || p.unit.unit_name)
                    ? p.unit.short_name || p.unit.unit_name
                    : "Pcs";
            const price = Number(p.selling_price || 0);
            const hpp = Number(p.current_hpp || 0);
            const sku = p.sku || "PRD-" + p.id;
            const stock =
                p.current_stock !== undefined && p.current_stock !== null
                    ? p.current_stock
                    : 0;
            const label = `${p.prod_name} (${sku}) - Stok: ${stock} ${unitName}`;
            selectOpts += `<option value="${p.id}" data-price="${price}" data-hpp="${hpp}" data-stock="${stock}" data-unit="${unitName}">${label}</option>`;
            divOpts += `<div class="searchable-select-option" data-value="${p.id}" data-label="${label}" onclick="selectSearchableOption(this)">${label}</div>`;
        });
    }
    return { selectOpts, divOpts };
}

function _buildSelectHtml(idx, selectOpts, divOpts) {
    return `<div class="searchable-select-wrapper">
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
function _addReportTableRow(idx, selectOpts, divOpts) {
    const tbody = document.getElementById("reportItemRows");
    if (!tbody) return;
    document.getElementById("emptyItemRow")?.remove();

    const tr = document.createElement("tr");
    tr.className = "report-item-row align-middle";
    tr.innerHTML = `
        <td class="row-number text-center text-muted fw-medium small col-no"></td>
        <td class="col-product">
            ${_buildSelectHtml(idx, selectOpts, divOpts)}
            <div class="stock-info-text">Stok tersedia: <span class="stock-num">-</span></div>
            <div class="stock-warning-text"><i class="bi bi-exclamation-circle-fill me-1"></i>Jumlah terjual melebihi stok!</div>
        </td>
        <td class="col-qty">
            <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" min="1" oninput="onItemQtyChange(this)">
        </td>
        <td class="col-price text-end font-monospace small">
            <span class="item-readonly-badge item-price-display">Rp 0</span>
            <input type="hidden" name="items[${idx}][selling_price]" class="item-selling-price" value="0">
        </td>
        <td class="col-total-price text-end font-monospace fw-semibold text-dark">
            <span class="item-readonly-badge item-total-price-display">Rp 0</span>
        </td>
        <td class="col-hpp text-end font-monospace small">
            <span class="item-readonly-badge item-hpp-display">Rp 0</span>
            <input type="hidden" name="items[${idx}][hpp]" class="item-hpp-price" value="0">
        </td>
        <td class="col-total-hpp text-end font-monospace small text-muted">
            <span class="item-readonly-badge item-total-hpp-display">Rp 0</span>
        </td>
        <td class="col-margin text-end font-monospace fw-bold text-success">
            <span class="item-readonly-badge item-margin-display text-success">Rp 0</span>
        </td>
        <td class="text-center col-action">
            <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 shadow-none" onclick="removeReportItemRow(this)" title="Hapus Baris">
                <i class="bi bi-x-circle-fill fs-5"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
}

function _addReportMobileCard(idx, selectOpts, divOpts) {
    const container = document.getElementById("reportCardsMobile");
    if (!container) return;
    document.getElementById("reportMobileEmptyState")?.remove();

    const card = document.createElement("div");
    card.className = "report-item-card";
    card.dataset.cardIdx = idx;
    card.innerHTML = `
        <div class="ric-header">
            <div class="ric-num-badge ric-row-number-mobile">-</div>
            <div class="ric-product-wrap">
                ${_buildSelectHtml(idx, selectOpts, divOpts)}
                <div class="ric-stock-info stock-info-text">Stok tersedia: <span class="stock-num">-</span></div>
                <div class="ric-stock-warning stock-warning-text" style="display:none;"><i class="bi bi-exclamation-circle-fill me-1"></i><span class="warning-msg">Jumlah terjual melebihi stok!</span></div>
            </div>
            <div class="ric-delete-btn">
                <button type="button" class="btn-delete-row" onclick="removeReportItemCard(this)" title="Hapus">
                    <i class="bi bi-trash3-fill"></i>
                </button>
            </div>
        </div>
        <div class="ric-qty-row">
            <div class="ric-qty-label">Jumlah Terjual</div>
            <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" min="1" oninput="onItemQtyChange(this)">
        </div>
        <div class="ric-stats-grid">
            <div class="ric-stat">
                <div class="ric-stat-label">Harga Jual</div>
                <div class="ric-stat-value item-price-display">Rp 0</div>
                <input type="hidden" name="items[${idx}][selling_price]" class="item-selling-price" value="0">
            </div>
            <div class="ric-stat">
                <div class="ric-stat-label">Total Penjualan</div>
                <div class="ric-stat-value item-total-price-display">Rp 0</div>
            </div>
            <div class="ric-stat">
                <div class="ric-stat-label">HPP / Unit</div>
                <div class="ric-stat-value item-hpp-display">Rp 0</div>
                <input type="hidden" name="items[${idx}][hpp]" class="item-hpp-price" value="0">
            </div>
            <div class="ric-stat">
                <div class="ric-stat-label">Total HPP</div>
                <div class="ric-stat-value item-total-hpp-display">Rp 0</div>
            </div>
            <div class="ric-margin-bar">
                <span class="ric-margin-label">Margin</span>
                <span class="ric-margin-value item-margin-display">Rp 0</span>
            </div>
        </div>`;
    container.appendChild(card);
}

function addReportItemRow() {
    const { selectOpts, divOpts } = _buildReportSelectOptions();
    _addReportTableRow(reportItemIndex, selectOpts, divOpts);
    _addReportMobileCard(reportItemIndex, selectOpts, divOpts);
    reportItemIndex++;
    updateRowNumbers();
    recalculateReportTotals();
}

function removeReportItemRow(btn) {
    const row = btn.closest("tr");
    if (!row) return;
    const select = row.querySelector(".product-select");
    if (select) {
        const m = select.getAttribute("name")?.match(/items\[(\d+)\]/);
        if (m)
            document
                .querySelector(`.report-item-card[data-card-idx="${m[1]}"]`)
                ?.remove();
    }
    row.remove();
    _checkReportEmptyStates();
    updateRowNumbers();
    recalculateReportTotals();
}

function removeReportItemCard(btn) {
    const card = btn.closest(".report-item-card");
    if (!card) return;
    const idx = card.dataset.cardIdx;
    const sel = document.querySelector(
        `#reportItemRows .product-select[name="items[${idx}][product_id]"]`,
    );
    if (sel) sel.closest("tr")?.remove();
    card.remove();
    _checkReportEmptyStates();
    updateRowNumbers();
    recalculateReportTotals();
}

function _checkReportEmptyStates() {
    const tbody = document.getElementById("reportItemRows");
    if (tbody && tbody.querySelectorAll(".report-item-row").length === 0) {
        tbody.innerHTML = `<tr id="emptyItemRow"><td colspan="9" class="text-center py-4 text-muted small">Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item penjualan.</td></tr>`;
    }
    const mob = document.getElementById("reportCardsMobile");
    if (mob && mob.querySelectorAll(".report-item-card").length === 0) {
        if (!document.getElementById("reportMobileEmptyState")) {
            const el = document.createElement("div");
            el.className = "reports-mobile-empty";
            el.id = "reportMobileEmptyState";
            el.textContent = 'Belum ada produk. Klik "+ Tambah" di atas.';
            mob.appendChild(el);
        }
    }
}

function updateRowNumbers() {
    document
        .querySelectorAll("#reportItemRows .report-item-row")
        .forEach((row, i) => {
            const c = row.querySelector(".row-number");
            if (c) c.textContent = i + 1;
        });
    document.querySelectorAll(".report-item-card").forEach((card, i) => {
        const b = card.querySelector(".ric-row-number-mobile");
        if (b) b.textContent = i + 1;
    });
}

function _applyProductInfoToContainer(container, { price, hpp, stock, unit }) {
    const stockNum = container.querySelector(".stock-num");
    const priceDisp = container.querySelector(".item-price-display");
    const hppDisp = container.querySelector(".item-hpp-display");
    const priceInput = container.querySelector(".item-selling-price");
    const hppInput = container.querySelector(".item-hpp-price");
    const qtyInput = container.querySelector(".item-qty");

    if (stockNum) stockNum.textContent = stock + " " + unit;
    if (priceDisp)
        priceDisp.textContent =
            "Rp " + Math.round(price).toLocaleString("id-ID");
    if (hppDisp)
        hppDisp.textContent = "Rp " + Math.round(hpp).toLocaleString("id-ID");
    if (priceInput) priceInput.value = price;
    if (hppInput) hppInput.value = hpp;
    if (qtyInput) qtyInput.max = stock;

    container.dataset.currentStock = stock;
    container.dataset.sellingPrice = price;
    container.dataset.hpp = hpp;
}

function onProductSelectChange(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt) return;
    const price = parseFloat(opt.getAttribute("data-price")) || 0;
    const hpp = parseFloat(opt.getAttribute("data-hpp")) || 0;
    const stock = parseInt(opt.getAttribute("data-stock"), 10) || 0;
    const unit = opt.getAttribute("data-unit") || "Pcs";

    const row = selectEl.closest("tr");
    if (row) {
        _applyProductInfoToContainer(row, { price, hpp, stock, unit });
        onItemQtyChange(row.querySelector(".item-qty") || selectEl);
    }
    const m = selectEl.getAttribute("name")?.match(/items\[(\d+)\]/);
    if (m) {
        const card = document.querySelector(
            `.report-item-card[data-card-idx="${m[1]}"]`,
        );
        if (card) {
            _applyProductInfoToContainer(card, { price, hpp, stock, unit });
            onItemQtyChange(card.querySelector(".item-qty") || selectEl);
        }
    }
}

function onItemQtyChange(inputEl) {
    if (!inputEl) return;
    const row = inputEl.closest("tr");
    const card = inputEl.closest(".report-item-card");
    const container = row || card;
    if (!container) return;

    const qtyInput = container.querySelector(".item-qty");
    const qty = parseInt(qtyInput?.value || 0, 10) || 0;
    const currentStock = parseInt(container.dataset.currentStock || 0, 10) || 0;
    const price = parseFloat(container.dataset.sellingPrice || 0) || 0;
    const hpp = parseFloat(container.dataset.hpp || 0) || 0;

    const warningText = container.querySelector(".stock-warning-text");
    if (qty > currentStock && currentStock >= 0) {
        qtyInput?.classList.add("is-invalid");
        if (warningText) {
            warningText.style.display = "block";
            const msg =
                warningText.querySelector(".warning-msg") || warningText;
            msg.textContent = `Jumlah terjual (${qty}) melebihi stok tersedia (${currentStock})!`;
        }
    } else {
        qtyInput?.classList.remove("is-invalid");
        if (warningText) warningText.style.display = "none";
    }

    const totalPrice = qty * price;
    const totalHpp = qty * hpp;
    const margin = totalPrice - totalHpp;
    const fmt = (v) => "Rp " + Math.round(v).toLocaleString("id-ID");

    const set = (sel, val, cls) => {
        const el = container.querySelector(sel);
        if (!el) return;
        el.textContent = val;
        if (cls !== undefined) {
            el.classList.toggle("text-danger", cls < 0);
            el.classList.toggle("text-success", cls >= 0);
        }
    };
    set(".item-total-price-display", fmt(totalPrice));
    set(".item-total-hpp-display", fmt(totalHpp));
    set(".item-margin-display", fmt(margin), margin);
    const bar = container.querySelector(".ric-margin-bar");
    if (bar) bar.classList.toggle("negative", margin < 0);

    // Sync pair
    const m = qtyInput?.getAttribute("name")?.match(/items\[(\d+)\]/);
    if (m) {
        const idx = m[1];
        const syncPair = (pairEl) => {
            if (!pairEl) return;
            const pQty = pairEl.querySelector(".item-qty");
            if (pQty) pQty.value = qtyInput?.value ?? "";
            pairEl.dataset.currentStock = currentStock;
            pairEl.dataset.sellingPrice = price;
            pairEl.dataset.hpp = hpp;
            const ps = (sel) => {
                const e = pairEl.querySelector(sel);
                return e;
            };
            if (ps(".item-total-price-display"))
                ps(".item-total-price-display").textContent = fmt(totalPrice);
            if (ps(".item-total-hpp-display"))
                ps(".item-total-hpp-display").textContent = fmt(totalHpp);
            if (ps(".item-margin-display")) {
                ps(".item-margin-display").textContent = fmt(margin);
                ps(".item-margin-display").classList.toggle(
                    "text-danger",
                    margin < 0,
                );
                ps(".item-margin-display").classList.toggle(
                    "text-success",
                    margin >= 0,
                );
            }
            const pb = pairEl.querySelector(".ric-margin-bar");
            if (pb) pb.classList.toggle("negative", margin < 0);
        };

        if (row) {
            syncPair(
                document.querySelector(
                    `.report-item-card[data-card-idx="${idx}"]`,
                ),
            );
        } else if (card) {
            const sel = document.querySelector(
                `#reportItemRows .product-select[name="items[${idx}][product_id]"]`,
            );
            syncPair(sel?.closest("tr"));
        }
    }
    recalculateReportTotals();
}

function recalculateReportTotals() {
    const rows = document.querySelectorAll("#reportItemRows .report-item-row");
    let totalItems = 0,
        totalQty = 0,
        grandSales = 0,
        grandHpp = 0;

    rows.forEach((row) => {
        const sel = row.querySelector(".product-select");
        if (sel && sel.value) {
            totalItems++;
            const qty =
                parseInt(row.querySelector(".item-qty")?.value || 0, 10) || 0;
            const price = parseFloat(row.dataset.sellingPrice || 0) || 0;
            const hpp = parseFloat(row.dataset.hpp || 0) || 0;
            totalQty += qty;
            grandSales += qty * price;
            grandHpp += qty * hpp;
        }
    });

    const grandMargin = grandSales - grandHpp;
    const fmt = (v) => "Rp " + Math.round(v).toLocaleString("id-ID");
    const el = (id) => document.getElementById(id);
    if (el("displayTotalItems"))
        el("displayTotalItems").textContent = totalItems + " Produk";
    if (el("displayTotalQty"))
        el("displayTotalQty").textContent = totalQty + " Unit";
    if (el("displayTotalSales"))
        el("displayTotalSales").textContent = fmt(grandSales);
    if (el("displayTotalHpp"))
        el("displayTotalHpp").textContent = fmt(grandHpp);
    if (el("displayTotalMargin")) {
        el("displayTotalMargin").textContent = fmt(grandMargin);
        el("displayTotalMargin").classList.toggle(
            "text-danger",
            grandMargin < 0,
        );
        el("displayTotalMargin").classList.toggle(
            "text-success",
            grandMargin >= 0,
        );
    }
}

function openDeleteModal(id, dateStr, totalQty, totalSales) {
    const dateEl = document.getElementById("deleteReportDate");
    const formEl = document.getElementById("deleteReportForm");
    if (dateEl) dateEl.textContent = dateStr;
    if (formEl) formEl.action = "/reports/" + id;
    const qtySpan = document.getElementById("deleteReportQty");
    const salesSpan = document.getElementById("deleteReportSales");
    if (qtySpan) qtySpan.textContent = totalQty + " Unit";
    if (salesSpan)
        salesSpan.textContent =
            "Rp " + Number(totalSales).toLocaleString("id-ID");
    const modalEl = document.getElementById("modalDeleteReport");
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
window.addReportItemRow = addReportItemRow;
window.removeReportItemRow = removeReportItemRow;
window.removeReportItemCard = removeReportItemCard;
window.updateRowNumbers = updateRowNumbers;
window.onProductSelectChange = onProductSelectChange;
window.onItemQtyChange = onItemQtyChange;
window.recalculateReportTotals = recalculateReportTotals;
window.openDeleteModal = openDeleteModal;

/* ════════════════════════════════════════════════════════════
   DOM READY
   ════════════════════════════════════════════════════════════ */
document.addEventListener("DOMContentLoaded", function () {
    const reportTbody = document.getElementById("reportItemRows");
    if (reportTbody) {
        const existing = reportTbody.querySelectorAll(".report-item-row");
        if (existing.length === 0) {
            addReportItemRow();
        } else {
            existing.forEach((r) => {
                const sel = r.querySelector(".product-select");
                if (sel && sel.value) onProductSelectChange(sel);
            });
            updateRowNumbers();
            recalculateReportTotals();
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

    // Mobile search
    const mobileSearch = document.getElementById("mobileSearchInput");
    if (mobileSearch) {
        mobileSearch.addEventListener("input", function () {
            const q = this.value.toLowerCase().trim();
            let hasVisible = false;
            document
                .querySelectorAll("#mobileReportCards .mobile-report-card")
                .forEach((card) => {
                    const match =
                        !q ||
                        (card.dataset.search || "").toLowerCase().includes(q);
                    card.style.display = match ? "" : "none";
                    if (match) hasVisible = true;
                });
            const noResult = document.getElementById("mobileNoResult");
            if (noResult) noResult.classList.toggle("d-none", hasVisible);
        });
    }
});

/* ════════════════════════════════════════════════════════════
   DATATABLES — INDEX PAGE (Laporan Penjualan)
   Mobile (< 768px) : responsive false → card HTML lama
   Desktop (≥ 768px): responsive inline child row
   Kolom disembunyikan duluan:
     Total Margin (6)  → responsivePriority: 11
     Dibuat Oleh  (7)  → responsivePriority: 12
   ════════════════════════════════════════════════════════════ */
if (typeof jQuery !== "undefined") {
    jQuery(document).ready(function ($) {
        if ($("#reportsDataTable").length === 0) return;

        const isMobile = window.innerWidth < 768;

        const dataTable = $("#reportsDataTable").DataTable({
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
                { targets: 1, responsivePriority: 2 }, // Tanggal
                { targets: 2, responsivePriority: 6 }, // Jumlah Produk
                { targets: 3, responsivePriority: 7 }, // Produk Terjual
                { targets: 4, responsivePriority: 3 }, // Total Penjualan
                { targets: 5, responsivePriority: 4 }, // Total HPP
                { targets: 6, responsivePriority: 11 }, // Total Margin  — disembunyikan duluan
                { targets: 7, responsivePriority: 12 }, // Dibuat Oleh   — disembunyikan duluan
                { targets: 8, responsivePriority: 1 }, // Aksi          — selalu tampil
            ],

            scrollX: false,
            autoWidth: false,

            language: {
                emptyTable:
                    "Belum ada laporan penjualan yang tercatat. Klik tombol '+ Tambah' untuk membuat laporan baru.",
                zeroRecords:
                    "Tidak ada laporan penjualan yang cocok dengan pencarian",
                info: "Menampilkan _START_–_END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada laporan",
                infoFiltered: "(difilter dari _MAX_ total)",
                paginate: { first: "«", previous: "‹", next: "›", last: "»" },
            },
            pagingType: "full_numbers",
            dom: '<"table-responsive-wrapper"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>',
            pageLength: 10,
        });

        // Resize handler
        let resizeTimer;
        $(window).on("resize", function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (window.innerWidth < 768 !== isMobile) {
                    dataTable.destroy();
                }
            }, 300);
        });

        // Search
        $("#dtSearchInput").on("keyup input", function () {
            dataTable.search(this.value).draw();
        });

        // Filter
        $("#btnApplyFilter").on("click", function () {
            applyFilters();
        });
        $("#btnResetFilter").on("click", function () {
            $("#modalFilterStartDate").val("");
            $("#modalFilterEndDate").val("");
            applyFilters();
        });

        function applyFilters() {
            const startDate = $("#modalFilterStartDate").val();
            const endDate = $("#modalFilterEndDate").val();

            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
                (fn) => fn.name !== "reportDateRangeFilter",
            );

            if (startDate || endDate) {
                const reportDateRangeFilter = function (
                    settings,
                    data,
                    dataIndex,
                ) {
                    if (settings.nTable.id !== "reportsDataTable") return true;
                    const rawDate = $(dataTable.row(dataIndex).node()).attr(
                        "data-date",
                    );
                    if (!rawDate) return true;
                    if (startDate && rawDate < startDate) return false;
                    if (endDate && rawDate > endDate) return false;
                    return true;
                };
                $.fn.dataTable.ext.search.push(reportDateRangeFilter);
            }

            dataTable.draw();
            const active = !!(startDate || endDate);
            $("#activeFilterBadge").toggleClass("d-none", !active);
            $("#activeFilterBadgeMobile").toggleClass("d-none", !active);
        }
    });
}
