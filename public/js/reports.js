/**
 * WARJOK — Manajemen Laporan Penjualan DataTables & Form JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

if (typeof window.reportProductsList === "undefined") {
    window.reportProductsList = [];
}
if (typeof window.reportUnitsList === "undefined") {
    window.reportUnitsList = [];
}

let reportItemIndex = 100;

/* ════════════════════════════════════════════════════════════
   FORMATTING HELPERS
   ════════════════════════════════════════════════════════════ */
function formatRupiah(val) {
    const num = Math.round(parseFloat(val) || 0);
    return "Rp " + num.toLocaleString("id-ID");
}

/* ════════════════════════════════════════════════════════════
   BUILD HELPERS
   ════════════════════════════════════════════════════════════ */
function _buildProductOptions(selectedProdId = null) {
    let html = '<option value="" disabled selected>Pilih Produk...</option>';
    if (Array.isArray(window.reportProductsList)) {
        window.reportProductsList.forEach((p) => {
            const sku = p.prod_code || "PRD-" + p.id;
            const sel =
                selectedProdId && selectedProdId == p.id ? "selected" : "";
            html += `<option value="${p.id}" ${sel}>${p.prod_name} (${sku})</option>`;
        });
    }
    return html;
}

function _buildUnitOptions(selectedUnitId = null) {
    let html = '<option value="" disabled selected>Pilih Satuan...</option>';
    if (Array.isArray(window.reportUnitsList)) {
        window.reportUnitsList.forEach((u) => {
            const shortName = u.short_name || u.unit_name;
            const sel =
                selectedUnitId && selectedUnitId == u.id ? "selected" : "";
            html += `<option value="${u.id}" ${sel}>${u.unit_name} (${shortName})</option>`;
        });
    }
    return html;
}

/* ════════════════════════════════════════════════════════════
   REAL-TIME PAIR SYNC (DESKTOP <-> MOBILE)
   ════════════════════════════════════════════════════════════ */
function _syncPairContainer(sourceContainer) {
    if (!sourceContainer) return;
    const nameMatch = sourceContainer
        .querySelector("[name*='items[']")
        ?.name.match(/items\[(\d+)\]/);
    if (!nameMatch) return;
    const idx = nameMatch[1];

    const isRow = sourceContainer.tagName === "TR";
    const targetContainer = isRow
        ? document.querySelector(`.report-item-card[data-card-idx="${idx}"]`)
        : document
              .querySelector(
                  `#reportItemRows tr .product-select[name="items[${idx}][product_id]"]`,
              )
              ?.closest("tr");

    if (!targetContainer) return;

    // Sync datasets safely
    if (sourceContainer.dataset.currentStock !== undefined) {
        targetContainer.dataset.currentStock =
            sourceContainer.dataset.currentStock;
    }
    if (sourceContainer.dataset.sellingPrice !== undefined) {
        targetContainer.dataset.sellingPrice =
            sourceContainer.dataset.sellingPrice;
    }
    if (sourceContainer.dataset.hpp !== undefined) {
        targetContainer.dataset.hpp = sourceContainer.dataset.hpp;
    }
    if (sourceContainer.dataset.purchasePrice !== undefined) {
        targetContainer.dataset.purchasePrice =
            sourceContainer.dataset.purchasePrice;
    }

    // Sync selects
    const prodSource = sourceContainer.querySelector(".product-select");
    const prodTarget = targetContainer.querySelector(".product-select");
    if (prodSource && prodTarget && prodTarget.value !== prodSource.value) {
        prodTarget.value = prodSource.value;
    }

    const sUnitSource = sourceContainer.querySelector(".selling-unit-select");
    const sUnitTarget = targetContainer.querySelector(".selling-unit-select");
    if (sUnitSource && sUnitTarget) {
        if (sUnitTarget.innerHTML !== sUnitSource.innerHTML) {
            sUnitTarget.innerHTML = sUnitSource.innerHTML;
            sUnitTarget.disabled = sUnitSource.disabled;
        }
        if (sUnitTarget.value !== sUnitSource.value) {
            sUnitTarget.value = sUnitSource.value;
        }
    }

    // Sync inputs
    const qtySource = sourceContainer.querySelector(".item-qty");
    const qtyTarget = targetContainer.querySelector(".item-qty");
    if (qtySource && qtyTarget && qtyTarget.value !== qtySource.value) {
        qtyTarget.value = qtySource.value;
    }

    const stockFinalSource = sourceContainer.querySelector(".item-stock-final");
    const stockFinalTarget = targetContainer.querySelector(".item-stock-final");
    if (stockFinalSource && stockFinalTarget) {
        stockFinalTarget.value = stockFinalSource.value;
    }

    const sPriceInputSource = sourceContainer.querySelector(
        ".item-selling-price",
    );
    const sPriceInputTarget = targetContainer.querySelector(
        ".item-selling-price",
    );
    if (sPriceInputSource && sPriceInputTarget) {
        sPriceInputTarget.value = sPriceInputSource.value;
    }

    const hppInputSource = sourceContainer.querySelector(".item-hpp-price");
    const hppInputTarget = targetContainer.querySelector(".item-hpp-price");
    if (hppInputSource && hppInputTarget) {
        hppInputTarget.value = hppInputSource.value;
    }

    // Sync displays
    const setDisplay = (cls) => {
        const srcEl = sourceContainer.querySelector(cls);
        const tgtEl = targetContainer.querySelector(cls);
        if (srcEl && tgtEl) {
            if (tgtEl.tagName === "INPUT") {
                tgtEl.value =
                    srcEl.tagName === "INPUT" ? srcEl.value : srcEl.textContent;
            } else {
                tgtEl.textContent =
                    srcEl.tagName === "INPUT" ? srcEl.value : srcEl.textContent;
            }
        }
    };

    setDisplay(".item-stock-val");
    setDisplay(".item-hpp-method-val");
    setDisplay(".item-purchase-price-display");
    setDisplay(".item-selling-price-display");
    setDisplay(".item-hpp-display");
    setDisplay(".item-total-sales-display");
    setDisplay(".item-total-hpp-display");
    setDisplay(".item-margin-display");
}

/* ════════════════════════════════════════════════════════════
   EVENT HANDLERS FOR PRODUCT & SELLING UNIT SELECTION
   ════════════════════════════════════════════════════════════ */
function _updateItemCalculations(container) {
    if (!container) return;

    const prodSelect = container.querySelector(".product-select");
    const unitSelect = container.querySelector(".selling-unit-select");

    const productId = prodSelect ? parseInt(prodSelect.value, 10) : null;
    const unitId = unitSelect ? parseInt(unitSelect.value, 10) : null;

    if (!productId) {
        _resetContainerData(container);
        _syncPairContainer(container);
        recalculateReportTotals();
        return;
    }

    const product = window.reportProductsList.find(
        (p) => parseInt(p.id, 10) === productId,
    );
    if (!product) return;

    const currentStock = parseInt(
        product.current_stock !== undefined && product.current_stock !== null
            ? product.current_stock
            : 0,
        10,
    );

    let purchasePrice = 0;
    if (
        product.purchase_prices_by_unit &&
        unitId &&
        product.purchase_prices_by_unit[unitId] !== undefined
    ) {
        purchasePrice = parseFloat(product.purchase_prices_by_unit[unitId]);
    } else if (
        product.latest_purchase_price !== undefined &&
        product.latest_purchase_price !== null
    ) {
        purchasePrice = parseFloat(product.latest_purchase_price);
    } else {
        purchasePrice = parseFloat(product.unit_price || 0);
    }

    let sellingPrice = parseFloat(product.unit_price || 0);
    let currentHpp = parseFloat(product.unit_price || 0);
    let hppMethod = product.hpp_method || "MANUAL";

    if (unitId && Array.isArray(product.product_hpps)) {
        const hppConfig = product.product_hpps.find(
            (h) => parseInt(h.selling_unit_id, 10) === unitId,
        );
        if (hppConfig) {
            sellingPrice = parseFloat(hppConfig.selling_price || 0);
            currentHpp = parseFloat(hppConfig.current_hpp || 0);
            hppMethod = hppConfig.hpp_method || "MANUAL";
        }
    }

    let unitName = "Unit";
    if (unitId && Array.isArray(window.reportUnitsList)) {
        const foundUnit = window.reportUnitsList.find(
            (u) => parseInt(u.id, 10) === unitId,
        );
        if (foundUnit) {
            unitName = foundUnit.short_name || foundUnit.unit_name;
        }
    }
    if (unitName === "Unit" && product.unit) {
        unitName = product.unit.short_name || product.unit.unit_name;
    }

    container.dataset.currentStock = currentStock;
    container.dataset.sellingPrice = sellingPrice;
    container.dataset.hpp = currentHpp;
    container.dataset.purchasePrice = purchasePrice;

    const stockEl = container.querySelector(".item-stock-val");
    if (stockEl) stockEl.textContent = `${currentStock} ${unitName}`;

    const hppMethodEl = container.querySelector(".item-hpp-method-val");
    if (hppMethodEl) hppMethodEl.textContent = hppMethod;

    const pPriceEl = container.querySelector(".item-purchase-price-display");
    if (pPriceEl) {
        if (pPriceEl.tagName === "INPUT") {
            pPriceEl.value = formatRupiah(purchasePrice);
        } else {
            pPriceEl.textContent = formatRupiah(purchasePrice);
        }
    }

    const sPriceEl = container.querySelector(".item-selling-price-display");
    if (sPriceEl) sPriceEl.textContent = formatRupiah(sellingPrice);
    const sPriceInput = container.querySelector(".item-selling-price");
    if (sPriceInput) sPriceInput.value = sellingPrice;

    const hppEl = container.querySelector(".item-hpp-display");
    if (hppEl) hppEl.textContent = formatRupiah(currentHpp);
    const hppInput = container.querySelector(".item-hpp-price");
    if (hppInput) hppInput.value = currentHpp;

    const qtyInput = container.querySelector(".item-qty");
    const qtyRaw = qtyInput ? qtyInput.value.trim() : "";
    const qty = qtyRaw !== "" ? parseInt(qtyRaw, 10) || 0 : 0;

    const stockFinalInput = container.querySelector(".item-stock-final");
    if (stockFinalInput) {
        stockFinalInput.value = currentStock - qty;
    }

    const totalSales = qty * sellingPrice;
    const totalHpp = qty * currentHpp;
    const margin = totalSales - totalHpp;

    const salesEl = container.querySelector(".item-total-sales-display");
    if (salesEl) salesEl.textContent = formatRupiah(totalSales);

    const totalHppEl = container.querySelector(".item-total-hpp-display");
    if (totalHppEl) totalHppEl.textContent = formatRupiah(totalHpp);

    const marginEl = container.querySelector(".item-margin-display");
    if (marginEl) {
        marginEl.textContent = formatRupiah(margin);
        marginEl.classList.toggle("text-danger", margin < 0);
        marginEl.classList.toggle("text-success", margin >= 0);
    }

    _syncPairContainer(container);
    recalculateReportTotals();
}

function onProductSelectChange(selectEl) {
    const productId = parseInt(selectEl.value, 10);
    const container =
        selectEl.closest("tr") || selectEl.closest(".report-item-card");
    if (!container) return;

    const unitSelect = container.querySelector(".selling-unit-select");
    if (unitSelect && productId) {
        const product = window.reportProductsList.find(
            (p) => parseInt(p.id, 10) === productId,
        );
        if (product && product.product_hpps && Array.isArray(product.product_hpps) && product.product_hpps.length > 0) {
            const currentUnitId = parseInt(unitSelect.value, 10) || null;
            let unitOpts = '<option value="" disabled selected>Pilih Satuan...</option>';
            product.product_hpps.forEach((hpp) => {
                const unit = hpp.selling_unit || hpp.sellingUnit;
                const uId = parseInt(hpp.selling_unit_id, 10);
                if (uId) {
                    let uName = unit ? (unit.unit_name || unit.short_name) : null;
                    let uShort = unit ? (unit.short_name || unit.unit_name) : null;
                    if (!uName && Array.isArray(window.reportUnitsList)) {
                        const globalUnit = window.reportUnitsList.find(u => parseInt(u.id, 10) === uId);
                        if (globalUnit) {
                            uName = globalUnit.unit_name;
                            uShort = globalUnit.short_name || globalUnit.unit_name;
                        }
                    }
                    uName = uName || `Satuan #${uId}`;
                    uShort = uShort || uName;
                    const sel = currentUnitId === uId ? "selected" : "";
                    unitOpts += `<option value="${uId}" ${sel}>${uName} (${uShort})</option>`;
                }
            });
            unitSelect.innerHTML = unitOpts;
            if (currentUnitId) unitSelect.value = currentUnitId;
        }
    }

    _updateItemCalculations(container);
}

function _resetContainerData(container) {
    container.dataset.currentStock = "";
    container.dataset.sellingPrice = "0";
    container.dataset.hpp = "0";
    container.dataset.purchasePrice = "0";

    const stockEl = container.querySelector(".item-stock-val");
    if (stockEl) stockEl.textContent = "-";

    const hppMethodEl = container.querySelector(".item-hpp-method-val");
    if (hppMethodEl) hppMethodEl.textContent = "-";

    const stockFinalInput = container.querySelector(".item-stock-final");
    if (stockFinalInput) stockFinalInput.value = "";

    const pPriceEl = container.querySelector(".item-purchase-price-display");
    if (pPriceEl) {
        if (pPriceEl.tagName === "INPUT") {
            pPriceEl.value = "Rp 0";
        } else {
            pPriceEl.textContent = "Rp 0";
        }
    }

    const sPriceEl = container.querySelector(".item-selling-price-display");
    if (sPriceEl) sPriceEl.textContent = "Rp 0";
    const sPriceInput = container.querySelector(".item-selling-price");
    if (sPriceInput) sPriceInput.value = 0;

    const hppEl = container.querySelector(".item-hpp-display");
    if (hppEl) hppEl.textContent = "Rp 0";
    const hppInput = container.querySelector(".item-hpp-price");
    if (hppInput) hppInput.value = 0;

    const salesEl = container.querySelector(".item-total-sales-display");
    if (salesEl) salesEl.textContent = "Rp 0";

    const totalHppEl = container.querySelector(".item-total-hpp-display");
    if (totalHppEl) totalHppEl.textContent = "Rp 0";

    const marginEl = container.querySelector(".item-margin-display");
    if (marginEl) {
        marginEl.textContent = "Rp 0";
        marginEl.classList.remove("text-danger");
        marginEl.classList.add("text-success");
    }
}

function onSellingUnitChange(selectEl) {
    const container =
        selectEl.closest("tr") || selectEl.closest(".report-item-card");
    if (!container) return;

    _updateItemCalculations(container);
}

function onItemQtyOrStockFinalChange(inputEl) {
    if (!inputEl) return;
    const container =
        inputEl.closest("tr") || inputEl.closest(".report-item-card");
    if (!container) return;

    _updateItemCalculations(container);
}

/* ════════════════════════════════════════════════════════════
   RECALCULATE TOTALS & ROW NUMBERS
   ════════════════════════════════════════════════════════════ */
function recalculateReportTotals() {
    const rows = document.querySelectorAll("#reportItemRows .report-item-row");
    let totalItems = 0,
        totalQty = 0,
        grandSales = 0,
        grandHpp = 0;

    rows.forEach((row) => {
        const prodSelect = row.querySelector(".product-select");
        if (prodSelect && prodSelect.value) {
            totalItems++;
            const qtyInput = row.querySelector(".item-qty");
            const qtyRaw = qtyInput ? qtyInput.value.trim() : "";
            const qty = qtyRaw !== "" ? parseInt(qtyRaw, 10) || 0 : 0;
            const sellingPrice = parseFloat(row.dataset.sellingPrice || 0);
            const hpp = parseFloat(row.dataset.hpp || 0);
            totalQty += qty;
            grandSales += qty * sellingPrice;
            grandHpp += qty * hpp;
        }
    });

    const grandMargin = grandSales - grandHpp;
    const el = (id) => document.getElementById(id);

    if (el("displayTotalItems"))
        el("displayTotalItems").textContent = totalItems + " Produk";
    if (el("displayTotalQty"))
        el("displayTotalQty").textContent = totalQty + " Unit";
    if (el("displayTotalSales"))
        el("displayTotalSales").textContent = formatRupiah(grandSales);
    if (el("displayTotalHpp"))
        el("displayTotalHpp").textContent = formatRupiah(grandHpp);
    if (el("displayTotalMargin")) {
        el("displayTotalMargin").textContent = formatRupiah(grandMargin);
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

/* ════════════════════════════════════════════════════════════
   ADD / REMOVE ROW & MOBILE CARDS
   ════════════════════════════════════════════════════════════ */
function addReportItemRow() {
    const idx = reportItemIndex++;
    const prodOpts = _buildProductOptions();

    // Desktop Table Row
    const tbody = document.getElementById("reportItemRows");
    if (tbody) {
        document.getElementById("emptyItemRow")?.remove();
        const tr = document.createElement("tr");
        tr.className = "report-item-row align-middle";
        tr.innerHTML = `
            <td class="row-number text-center text-muted fw-medium small col-no"></td>
            <td class="col-product">
                <select name="items[${idx}][product_id]" class="form-select form-select-sm product-select rounded-2" onchange="onProductSelectChange(this)" required>
                    ${prodOpts}
                </select>
            </td>
            <td class="col-unit">
                <select name="items[${idx}][selling_unit_id]" class="form-select form-select-sm selling-unit-select rounded-2" onchange="onSellingUnitChange(this)" required>
                    <option value="" disabled selected>Pilih Satuan...</option>
                </select>
            </td>
            <td class="col-info text-start small">
                <div class="item-info-stock text-dark fw-medium" style="font-size: 0.78rem;">Stok saat ini: <span class="item-stock-val">-</span></div>
                <div class="item-info-hpp text-muted" style="font-size: 0.72rem;">Metode HPP: <span class="item-hpp-method-val">-</span></div>
            </td>
            <td class="col-qty">
                <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="" min="1" placeholder="" oninput="onItemQtyOrStockFinalChange(this)" required>
            </td>
            <td class="col-stock-final">
                <input type="number" name="items[${idx}][stock_final]" class="form-control form-control-sm font-monospace text-center rounded-2 item-stock-final bg-light text-secondary" value="" readonly tabindex="-1" required>
            </td>
            <td class="col-price text-end font-monospace small">
                <input type="text" class="form-control form-control-sm font-monospace text-end rounded-2 bg-light text-muted item-purchase-price-display" value="Rp 0" disabled tabindex="-1">
            </td>
            <td class="col-selling-price text-end font-monospace small">
                <span class="item-readonly-badge item-selling-price-display">Rp 0</span>
                <input type="hidden" name="items[${idx}][selling_price]" class="item-selling-price" value="0">
            </td>
            <td class="col-total-sales text-end font-monospace fw-semibold text-dark">
                <span class="item-readonly-badge item-total-sales-display">Rp 0</span>
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
                    <i class="bi bi-trash3-fill fs-6"></i>
                </button>
            </td>`;
        tbody.appendChild(tr);
    }

    // Mobile Card
    const mobContainer = document.getElementById("reportCardsMobile");
    if (mobContainer) {
        document.getElementById("reportMobileEmptyState")?.remove();
        const card = document.createElement("div");
        card.className = "report-item-card";
        card.dataset.cardIdx = idx;
        card.innerHTML = `
            <div class="ric-header">
                <div class="ric-num-badge ric-row-number-mobile">-</div>
                <div class="ric-product-wrap">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Nama Produk</label>
                        <select name="items[${idx}][product_id]" class="form-select form-select-sm product-select rounded-2" onchange="onProductSelectChange(this)" required>
                            ${prodOpts}
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Satuan Jual</label>
                        <select name="items[${idx}][selling_unit_id]" class="form-select form-select-sm selling-unit-select rounded-2" onchange="onSellingUnitChange(this)" required>
                            <option value="" disabled selected>Pilih Satuan...</option>
                        </select>
                    </div>
                </div>
                <div class="ric-delete-btn">
                    <button type="button" class="btn-delete-row" onclick="removeReportItemCard(this)" title="Hapus">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="ric-qty-label">Jumlah Terjual</label>
                    <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="" min="1" placeholder="" oninput="onItemQtyOrStockFinalChange(this)" required>
                </div>
                <div class="col-6">
                    <label class="ric-qty-label">Stok Akhir (Otomatis)</label>
                    <input type="number" name="items[${idx}][stock_final]" class="form-control form-control-sm font-monospace text-center rounded-2 item-stock-final bg-light text-secondary" value="" readonly tabindex="-1" required>
                </div>
            </div>
            <div class="ric-stats-grid">
                <div class="ric-stat">
                    <div class="ric-stat-label">Stok saat ini</div>
                    <div class="ric-stat-value item-stock-val">-</div>
                </div>
                <div class="ric-stat">
                    <div class="ric-stat-label">Metode HPP</div>
                    <div class="ric-stat-value item-hpp-method-val">-</div>
                </div>
                <div class="ric-stat">
                    <div class="ric-stat-label">Harga Beli</div>
                    <div class="ric-stat-value item-purchase-price-display">Rp 0</div>
                </div>
                <div class="ric-stat">
                    <div class="ric-stat-label">Harga Jual</div>
                    <div class="ric-stat-value item-selling-price-display">Rp 0</div>
                    <input type="hidden" name="items[${idx}][selling_price]" class="item-selling-price" value="0">
                </div>
                <div class="ric-stat">
                    <div class="ric-stat-label">Total Penjualan</div>
                    <div class="ric-stat-value item-total-sales-display">Rp 0</div>
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
        mobContainer.appendChild(card);
    }

    updateRowNumbers();
    recalculateReportTotals();
}

function removeReportItemRow(btn) {
    const row = btn.closest("tr");
    if (!row) return;
    const sel = row.querySelector(".product-select");
    if (sel) {
        const m = sel.getAttribute("name")?.match(/items\[(\d+)\]/);
        if (m) {
            document
                .querySelector(`.report-item-card[data-card-idx="${m[1]}"]`)
                ?.remove();
        }
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
        tbody.innerHTML = `<tr id="emptyItemRow"><td colspan="13" class="text-center py-4 text-muted small">Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item penjualan.</td></tr>`;
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

function openDeleteModal(id, dateStr, totalQty, totalSales) {
    const dateEl = document.getElementById("deleteReportDate");
    const formEl = document.getElementById("deleteReportForm");
    if (dateEl) dateEl.textContent = dateStr;
    if (formEl) formEl.action = "/reports/" + id;
    const qtySpan = document.getElementById("deleteReportQty");
    const salesSpan = document.getElementById("deleteReportSales");
    if (qtySpan) qtySpan.textContent = totalQty + " Unit";
    if (salesSpan) salesSpan.textContent = formatRupiah(totalSales);
    const modalEl = document.getElementById("modalDeleteReport");
    if (modalEl && typeof bootstrap !== "undefined") {
        new bootstrap.Modal(modalEl).show();
    }
}

/* ════════════════════════════════════════════════════════════
   EXPOSE GLOBALS
   ════════════════════════════════════════════════════════════ */
window.onSellingUnitChange = onSellingUnitChange;
window.onProductSelectChange = onProductSelectChange;
window.onItemQtyOrStockFinalChange = onItemQtyOrStockFinalChange;
window.addReportItemRow = addReportItemRow;
window.removeReportItemRow = removeReportItemRow;
window.removeReportItemCard = removeReportItemCard;
window.updateRowNumbers = updateRowNumbers;
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
                const pSel = r.querySelector(".product-select");
                const uSel = r.querySelector(".selling-unit-select");
                if (pSel && pSel.value) {
                    const currentUnitVal = uSel ? uSel.value : null;
                    onProductSelectChange(pSel);
                    if (uSel && currentUnitVal) {
                        uSel.value = currentUnitVal;
                        onSellingUnitChange(uSel);
                    }
                }
            });
            updateRowNumbers();
            recalculateReportTotals();
        }
    }
});

/* ════════════════════════════════════════════════════════════
   DATATABLES — INDEX PAGE (Laporan Penjualan)
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
                { targets: 0, responsivePriority: 1 },
                { targets: 1, responsivePriority: 2 },
                { targets: 2, responsivePriority: 6 },
                { targets: 3, responsivePriority: 7 },
                { targets: 4, responsivePriority: 3 },
                { targets: 5, responsivePriority: 4 },
                { targets: 6, responsivePriority: 11 },
                { targets: 7, responsivePriority: 12 },
                { targets: 8, responsivePriority: 1 },
            ],

            scrollX: false,
            autoWidth: false,

            language: {
                emptyTable:
                    "Belum ada laporan penjualan yang tercatat. Klik tombol '+ Tambah' untuk membuat laporan baru.",
                zeroRecords:
                    "Tidak ada laporan penjualan yang cocok dengan pencarian",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Tidak ada laporan",
                infoFiltered: "(difilter dari _MAX_ total)",
                paginate: { first: "«", previous: "‹", next: "›", last: "»" },
            },
            pagingType: "full_numbers",
            dom: '<"table-responsive-wrapper"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>',
            pageLength: 10,
        });

        // Search
        $("#dtSearchInput").on("keyup input", function () {
            dataTable.search(this.value).draw();
        });
    });
}
