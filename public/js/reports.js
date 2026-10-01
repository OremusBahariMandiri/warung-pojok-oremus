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

// Flag untuk mencegah toast muncul double saat inisialisasi halaman edit (load data lama)
let _suppressInitToast = false;

/* ════════════════════════════════════════════════════════════
   FORMATTING HELPERS
   ════════════════════════════════════════════════════════════ */
function formatRupiah(val) {
    const num = Math.round(parseFloat(val) || 0);
    return "Rp " + num.toLocaleString("id-ID");
}

/* ════════════════════════════════════════════════════════════
   GORENGAN HELPER
   ════════════════════════════════════════════════════════════ */
function _isGorenganProduct(productId) {
    if (!productId) return false;
    const product = window.reportProductsList.find(
        (p) => parseInt(p.id, 10) === parseInt(productId, 10),
    );
    if (!product) return false;
    return (product.prod_name || "").toLowerCase().includes("gorengan");
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

/* ════════════════════════════════════════════════════════════
   SYNC — Desktop row → Mobile table row
   ════════════════════════════════════════════════════════════ */
function _syncPairContainer(desktopRow) {
    if (!desktopRow || desktopRow.tagName !== "TR") return;

    const nameEl = desktopRow.querySelector("[name*='items[']");
    if (!nameEl) return;
    const nameMatch = nameEl.name.match(/items\[(\d+)\]/);
    if (!nameMatch) return;
    const idx = nameMatch[1];

    const mobileRow = document.querySelector(
        `.report-item-row-mobile[data-mobile-idx="${idx}"]`,
    );
    if (!mobileRow) return;

    // Sync datasets
    if (desktopRow.dataset.currentStock !== undefined)
        mobileRow.dataset.currentStock = desktopRow.dataset.currentStock;
    if (desktopRow.dataset.sellingPrice !== undefined)
        mobileRow.dataset.sellingPrice = desktopRow.dataset.sellingPrice;
    if (desktopRow.dataset.hpp !== undefined)
        mobileRow.dataset.hpp = desktopRow.dataset.hpp;
    if (desktopRow.dataset.purchasePrice !== undefined)
        mobileRow.dataset.purchasePrice = desktopRow.dataset.purchasePrice;

    // Sync product select
    const desktopProdSel = desktopRow.querySelector(".product-select");
    const mobileProdSel = mobileRow.querySelector(".product-select-mobile");
    if (
        desktopProdSel &&
        mobileProdSel &&
        mobileProdSel.value !== desktopProdSel.value
    ) {
        mobileProdSel.value = desktopProdSel.value;
    }

    // Sync unit options + value
    const desktopUnitSel = desktopRow.querySelector(".selling-unit-select");
    const mobileUnitSel = mobileRow.querySelector(
        ".selling-unit-select-mobile",
    );
    if (desktopUnitSel && mobileUnitSel) {
        if (mobileUnitSel.innerHTML !== desktopUnitSel.innerHTML) {
            mobileUnitSel.innerHTML = desktopUnitSel.innerHTML;
        }
        if (mobileUnitSel.value !== desktopUnitSel.value) {
            mobileUnitSel.value = desktopUnitSel.value;
        }
    }

    // Sync qty
    const desktopQty = desktopRow.querySelector(".item-qty");
    const mobileQty = mobileRow.querySelector(".item-qty-mobile");
    if (desktopQty && mobileQty && mobileQty.value !== desktopQty.value) {
        mobileQty.value = desktopQty.value;
    }

    // Sync stock final
    const desktopSF = desktopRow.querySelector(".item-stock-final");
    const mobileSF = mobileRow.querySelector(".item-stock-final");
    if (desktopSF && mobileSF) mobileSF.value = desktopSF.value;

    // Sync display spans/badges
    const syncDisplay = (cls) => {
        const src = desktopRow.querySelector(cls);
        const tgt = mobileRow.querySelector(cls);
        if (!src || !tgt) return;
        const val = src.tagName === "INPUT" ? src.value : src.textContent;
        if (tgt.tagName === "INPUT") tgt.value = val;
        else tgt.textContent = val;
    };
    syncDisplay(".item-stock-val");
    syncDisplay(".item-hpp-method-val");
    syncDisplay(".item-purchase-price-display");
    syncDisplay(".item-selling-price-display");
    syncDisplay(".item-hpp-display");
    syncDisplay(".item-total-sales-display");
    syncDisplay(".item-total-hpp-display");
    syncDisplay(".item-margin-display");
}

/* ════════════════════════════════════════════════════════════
   EXPAND ROW — Desktop (>=768px)
   ════════════════════════════════════════════════════════════ */
function _syncExpandRow(container) {
    if (!container || container.tagName !== "TR") return;
    const expandRow = container.nextElementSibling;
    if (!expandRow || !expandRow.classList.contains("report-item-expand-row"))
        return;

    const getVal = (cls) => {
        const el = container.querySelector(cls);
        if (!el) return "";
        return el.tagName === "INPUT" ? el.value : el.textContent;
    };

    const setVal = (cls, val) => {
        const el = expandRow.querySelector(cls);
        if (el) el.textContent = val;
    };

    setVal(".expand-stock-val", getVal(".item-stock-val"));
    setVal(".expand-hpp-method-val", getVal(".item-hpp-method-val"));
    setVal(".expand-purchase-price", getVal(".item-purchase-price-display"));
    setVal(".expand-selling-price", getVal(".item-selling-price-display"));
    setVal(".expand-hpp", getVal(".item-hpp-display"));
    setVal(".expand-total-hpp", getVal(".item-total-hpp-display"));

    const stockFinalInput = container.querySelector(".item-stock-final");
    const stockFinalEl = expandRow.querySelector(".expand-stock-final");
    if (stockFinalEl && stockFinalInput) {
        const val = stockFinalInput.value;
        const num = parseInt(val, 10);
        stockFinalEl.textContent = val !== "" ? val : "-";
        stockFinalEl.classList.toggle("text-danger", val !== "" && num < 0);
        stockFinalEl.classList.toggle("fw-bold", val !== "" && num < 0);
    }
}

function _toggleExpandRow(toggleEl) {
    // Guard: expand row hanya untuk desktop
    if (window.innerWidth < 768) return;
    const tr = toggleEl.closest("tr");
    if (!tr) return;
    const expandRow = tr.nextElementSibling;
    if (!expandRow || !expandRow.classList.contains("report-item-expand-row"))
        return;
    const isOpen = tr.classList.contains("row-expanded");
    tr.classList.toggle("row-expanded", !isOpen);
    expandRow.classList.toggle("d-none", isOpen);
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

    // GORENGAN: set qty readonly dan isi otomatis dari current_stock
    const isGorengan = _isGorenganProduct(productId);
    if (isGorengan) {
        const qtyGorengan = container.querySelector(".item-qty");
        if (qtyGorengan) {
            qtyGorengan.setAttribute("readonly", "readonly");
            qtyGorengan.value = currentStock;
        }
    } else {
        const qtyEl = container.querySelector(".item-qty");
        if (qtyEl) {
            qtyEl.removeAttribute("readonly");
        }
    }

    const qtyInput = container.querySelector(".item-qty");
    const qtyRaw = qtyInput ? qtyInput.value.trim() : "";
    const qty = qtyRaw !== "" ? parseInt(qtyRaw, 10) || 0 : 0;

    // FIX: gunakan effectiveStock (currentStock + originalQty) untuk hitung sisa stok
    // GORENGAN: stock_final = currentStock (stok tidak berkurang)
    const stockFinalInput = container.querySelector(".item-stock-final");
    if (stockFinalInput) {
        if (isGorengan) {
            stockFinalInput.value = currentStock;
        } else {
            const originalQty = parseInt(
                container.dataset.originalQty || 0,
                10,
            );
            const effectiveStock = currentStock + originalQty;
            stockFinalInput.value = effectiveStock - qty;
        }
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

    _validateItemQty(container);
    _syncExpandRow(container);

    // GORENGAN: override expand row sisa stok → "Unlimited"
    if (isGorengan) {
        const expandRow = container.nextElementSibling;
        if (
            expandRow &&
            expandRow.classList.contains("report-item-expand-row")
        ) {
            const sfEl = expandRow.querySelector(".expand-stock-final");
            if (sfEl) {
                sfEl.textContent = "Unlimited";
                sfEl.classList.remove("text-danger", "fw-bold");
            }
        }
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
        if (
            product &&
            product.product_hpps &&
            Array.isArray(product.product_hpps) &&
            product.product_hpps.length > 0
        ) {
            const currentUnitId = parseInt(unitSelect.value, 10) || null;
            unitSelect.blur();
            unitSelect.innerHTML =
                '<option value="" disabled selected>Pilih Satuan...</option>';
            let unitOpts =
                '<option value="" disabled selected>Pilih Satuan...</option>';
            product.product_hpps.forEach((hpp) => {
                const unit = hpp.selling_unit || hpp.sellingUnit;
                const uId = parseInt(hpp.selling_unit_id, 10);
                if (uId) {
                    let uName = unit ? unit.unit_name || unit.short_name : null;
                    let uShort = unit
                        ? unit.short_name || unit.unit_name
                        : null;
                    if (!uName && Array.isArray(window.reportUnitsList)) {
                        const globalUnit = window.reportUnitsList.find(
                            (u) => parseInt(u.id, 10) === uId,
                        );
                        if (globalUnit) {
                            uName = globalUnit.unit_name;
                            uShort =
                                globalUnit.short_name || globalUnit.unit_name;
                        }
                    }
                    uName = uName || `Satuan #${uId}`;
                    uShort = uShort || uName;
                    const sel = currentUnitId === uId ? "selected" : "";
                    unitOpts += `<option value="${uId}" ${sel}>${uName} (${uShort})</option>`;
                }
            });
            unitSelect.innerHTML = unitOpts;
            unitSelect.value = "";
        } else {
            unitSelect.innerHTML =
                '<option value="" disabled selected>Pilih Satuan...</option>';
            unitSelect.value = "";
        }
    }

    _updateItemCalculations(container);
    refreshReportProductOptions();
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

    _clearQtyValidation(container);
    _syncExpandRow(container);

    // GORENGAN: kembalikan qty ke editable saat produk di-reset
    const qtyInputReset = container.querySelector(".item-qty");
    if (qtyInputReset) qtyInputReset.removeAttribute("readonly");
}

/* ════════════════════════════════════════════════════════════
   MOBILE TABLE INTERACTION HANDLERS
   Setiap aksi di tabel mobile -> update desktop row -> recalculate -> sync balik ke mobile
   ════════════════════════════════════════════════════════════ */
function onMobileProductChange(selectEl) {
    const mobileRow = selectEl.closest("tr");
    if (!mobileRow) return;
    const idx = mobileRow.dataset.mobileIdx;
    const desktopSel = document.querySelector(
        `.product-select[name="items[${idx}][product_id]"]`,
    );
    if (!desktopSel) return;
    desktopSel.value = selectEl.value;
    // Rebuild unit + recalculate + sync balik ke mobile
    onProductSelectChange(desktopSel);
}

function onMobileUnitChange(selectEl) {
    const mobileRow = selectEl.closest("tr");
    if (!mobileRow) return;
    const idx = mobileRow.dataset.mobileIdx;
    const desktopSel = document.querySelector(
        `.selling-unit-select[name="items[${idx}][selling_unit_id]"]`,
    );
    if (!desktopSel) return;
    desktopSel.value = selectEl.value;
    onSellingUnitChange(desktopSel);
}

function onMobileQtyChange(inputEl) {
    const mobileRow = inputEl.closest("tr");
    if (!mobileRow) return;
    const idx = mobileRow.dataset.mobileIdx;
    const desktopInput = document.querySelector(
        `.item-qty[name="items[${idx}][quantity]"]`,
    );
    if (!desktopInput) return;
    desktopInput.value = inputEl.value;
    onItemQtyOrStockFinalChange(desktopInput);
}

/* ════════════════════════════════════════════════════════════
   STOCK TOAST NOTIFICATION
   ════════════════════════════════════════════════════════════ */
(function _injectToastStyles() {
    if (document.getElementById("stockToastStyles")) return;
    const style = document.createElement("style");
    style.id = "stockToastStyles";
    style.textContent = `
        #stockToastContainer {
            position: fixed;
            top: 60px;
            right: 5px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }
        .stock-toast {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            min-width: 360px;
            max-width: 480px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
            pointer-events: auto;
            animation: stockToastIn 0.25s ease;
        }
        .stock-toast.toast-error {
            background: #fff1f1;
            border-left: 4px solid #dc3545;
            color: #b91c1c;
        }
        .stock-toast.toast-warning {
            background: #fffbea;
            border-left: 4px solid #ffc107;
            color: #92400e;
        }
        .stock-toast .toast-icon { font-size: 1rem; flex-shrink: 0; }
        .stock-toast.toast-hide { animation: stockToastOut 0.3s ease forwards; }
        @keyframes stockToastIn {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes stockToastOut {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(40px); }
        }
    `;
    document.head.appendChild(style);
})();

function _getToastContainer() {
    let el = document.getElementById("stockToastContainer");
    if (!el) {
        el = document.createElement("div");
        el.id = "stockToastContainer";
        document.body.appendChild(el);
    }
    return el;
}

function _showStockToast(type, message) {
    if (_suppressInitToast) return;
    const container = _getToastContainer();
    const toast = document.createElement("div");
    toast.className = "stock-toast toast-" + type;
    const icon = document.createElement("span");
    icon.className = "toast-icon";
    icon.innerHTML =
        type === "error"
            ? '<i class="bi bi-x-circle-fill"></i>'
            : '<i class="bi bi-exclamation-triangle-fill"></i>';
    const text = document.createElement("span");
    text.textContent = message;
    toast.appendChild(icon);
    toast.appendChild(text);
    container.appendChild(toast);
    setTimeout(function () {
        toast.classList.add("toast-hide");
        setTimeout(function () {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 320);
    }, 5000);
}

/* ════════════════════════════════════════════════════════════
   STOCK QTY VALIDATION
   ════════════════════════════════════════════════════════════ */
function _clearQtyValidation(container) {
    const qtyInput = container.querySelector(".item-qty");
    if (qtyInput) {
        qtyInput.classList.remove("is-invalid");
        qtyInput.style.borderColor = "";
        qtyInput.style.boxShadow = "";
    }
    const msgEl = container.querySelector(".item-qty-msg");
    if (msgEl) msgEl.remove();
}

function _validateItemQty(container) {
    const qtyInput = container.querySelector(".item-qty");
    if (!qtyInput) return true;

    // GORENGAN: skip validasi stok, langsung clear dan return true
    const _gProdSel = container.querySelector(".product-select");
    if (_isGorenganProduct(_gProdSel ? _gProdSel.value : null)) {
        _clearQtyValidation(container);
        return true;
    }

    const rawStock = container.dataset.currentStock;
    if (rawStock === undefined || rawStock === "" || rawStock === null) {
        _clearQtyValidation(container);
        return true;
    }

    const currentStock = parseInt(rawStock, 10);
    if (isNaN(currentStock)) {
        _clearQtyValidation(container);
        return true;
    }

    // FIX: hitung effectiveStock dengan originalQty dari data-original-qty
    // Di form create: originalQty = 0, effectiveStock = currentStock (tidak berubah)
    // Di form edit: originalQty = qty tersimpan, effectiveStock = currentStock + originalQty
    const originalQty = parseInt(container.dataset.originalQty || 0, 10);
    const effectiveStock = currentStock + originalQty;

    const qty = parseInt(qtyInput.value, 10) || 0;

    const prodSelect = container.querySelector(".product-select");
    const productId = prodSelect ? parseInt(prodSelect.value, 10) : null;
    const product = productId
        ? window.reportProductsList.find(
              (p) => parseInt(p.id, 10) === productId,
          )
        : null;
    const minStockRaw =
        product && product.min_stock != null
            ? parseInt(product.min_stock, 10)
            : 0;
    const effectiveMinStock =
        minStockRaw > 0 ? minStockRaw : Math.ceil(currentStock * 0.25);

    const existingMsg = container.querySelector(".item-qty-msg");
    if (existingMsg) existingMsg.remove();

    qtyInput.classList.remove("is-invalid");
    qtyInput.style.borderColor = "";
    qtyInput.style.boxShadow = "";

    if (qty <= 0 || qtyInput.value === "") return true;

    if (qty > effectiveStock) {
        qtyInput.classList.add("is-invalid");
        const msg = document.createElement("div");
        msg.className = "item-qty-msg text-danger fw-semibold";
        msg.style.cssText = "font-size:0.72rem;margin-top:3px;";
        msg.textContent = `Stok tidak cukup! Tersedia: ${effectiveStock}`;
        qtyInput.parentNode.appendChild(msg);
        _showStockToast(
            "error",
            `Stok tidak cukup! Stok tersedia: ${effectiveStock}`,
        );
        return false;
    }

    const stockFinal = effectiveStock - qty;
    if (stockFinal <= effectiveMinStock) {
        qtyInput.style.borderColor = "#ffc107";
        qtyInput.style.boxShadow = "0 0 0 0.2rem rgba(255,193,7,0.25)";
        const msg = document.createElement("div");
        msg.className = "item-qty-msg text-warning fw-semibold";
        msg.style.cssText = "font-size:0.72rem;margin-top:3px;";
        msg.textContent = "Stok menipis, disarankan re-stock!";
        qtyInput.parentNode.appendChild(msg);
        _showStockToast("warning", "Stok menipis, disarankan re-stock!");
    }

    return true;
}

// FIX: tambahkan selector #formEditReport dan gunakan effectiveStock yang benar
function _checkFormSubmitState() {
    const submitBtn = document.querySelector(
        "#formCreateReport [type='submit'], #formEditReport [type='submit']",
    );
    if (!submitBtn) return;

    const rows = document.querySelectorAll("#reportItemRows .report-item-row");
    let hasError = false;
    rows.forEach(function (row) {
        // GORENGAN: skip validasi stok untuk produk gorengan
        const _gSel = row.querySelector(".product-select");
        if (_isGorenganProduct(_gSel ? _gSel.value : null)) return;

        const rawStock = row.dataset.currentStock;
        if (rawStock === undefined || rawStock === "" || rawStock === null)
            return;
        const currentStock = parseInt(rawStock, 10);
        if (isNaN(currentStock)) return;
        const originalQty = parseInt(row.dataset.originalQty || 0, 10);
        const effectiveStock = currentStock + originalQty;
        const qtyInput = row.querySelector(".item-qty");
        const qty = parseInt(qtyInput ? qtyInput.value : "0", 10) || 0;
        if (qty > 0 && qty > effectiveStock) hasError = true;
    });

    submitBtn.disabled = hasError;
    submitBtn.classList.toggle("opacity-50", hasError);
    submitBtn.classList.toggle("pe-none", hasError);
}

function refreshReportProductOptions() {
    // Desktop rows
    const desktopSelects = Array.from(
        document.querySelectorAll(
            "#reportItemRows .report-item-row .product-select",
        ),
    );
    const desktopChosen = desktopSelects.map((s) => s.value).filter(Boolean);
    desktopSelects.forEach((sel) => {
        Array.from(sel.options).forEach((opt) => {
            if (!opt.value) return;
            opt.disabled =
                desktopChosen.includes(opt.value) && sel.value !== opt.value;
        });
    });

    // Mobile table rows
    const mobileSelects = Array.from(
        document.querySelectorAll(
            "#reportItemRowsMobile .report-item-row-mobile .product-select-mobile",
        ),
    );
    const mobileChosen = mobileSelects.map((s) => s.value).filter(Boolean);
    mobileSelects.forEach((sel) => {
        Array.from(sel.options).forEach((opt) => {
            if (!opt.value) return;
            opt.disabled =
                mobileChosen.includes(opt.value) && sel.value !== opt.value;
        });
    });

    // Old mobile cards (backward compat)
    const mobileCardSelects = Array.from(
        document.querySelectorAll(".report-item-card .product-select"),
    );
    const mobileCardChosen = mobileCardSelects
        .map((s) => s.value)
        .filter(Boolean);
    mobileCardSelects.forEach((sel) => {
        Array.from(sel.options).forEach((opt) => {
            if (!opt.value) return;
            opt.disabled =
                mobileCardChosen.includes(opt.value) && sel.value !== opt.value;
        });
    });
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
    _checkFormSubmitState();
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
    // Desktop rows
    document
        .querySelectorAll("#reportItemRows .report-item-row")
        .forEach((row, i) => {
            const c = row.querySelector(".row-no-num");
            if (c) c.textContent = i + 1;
        });
    // Mobile table rows
    document
        .querySelectorAll("#reportItemRowsMobile .report-item-row-mobile")
        .forEach((row, i) => {
            const c = row.querySelector(".row-no-num");
            if (c) c.textContent = i + 1;
        });
    // Old mobile cards (backward compat)
    document.querySelectorAll(".report-item-card").forEach((card, i) => {
        const b = card.querySelector(".ric-row-number-mobile");
        if (b) b.textContent = i + 1;
    });
}

/* ════════════════════════════════════════════════════════════
   ADD ROW — Desktop (6-col + expand) + Mobile mirror (13-col)
   ════════════════════════════════════════════════════════════ */
function addReportItemRow() {
    const idx = reportItemIndex++;
    const prodOpts = _buildProductOptions();

    /* -- 1. Desktop: 6-col row + expand row -- */
    const tbody = document.getElementById("reportItemRows");
    if (tbody) {
        document.getElementById("emptyItemRow")?.remove();

        const tr = document.createElement("tr");
        tr.className = "report-item-row align-middle";
        tr.innerHTML = `
            <td class="row-number text-center text-muted fw-medium small col-no report-expand-toggle" onclick="_toggleExpandRow(this)" style="cursor:pointer;">
                <span class="row-expand-arrow"></span>
                <span class="row-no-num"></span>
            </td>
            <td class="col-product-unit">
                <select name="items[${idx}][product_id]" class="form-select form-select-sm product-select rounded-2 mb-1" onchange="onProductSelectChange(this)" required>
                    ${prodOpts}
                </select>
                <select name="items[${idx}][selling_unit_id]" class="form-select form-select-sm selling-unit-select rounded-2" onchange="onSellingUnitChange(this)" required>
                    <option value="" disabled selected>Pilih Satuan...</option>
                </select>
            </td>
            <td class="col-qty">
                <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="" min="1" placeholder="" oninput="onItemQtyOrStockFinalChange(this)" required>
            </td>
            <td class="col-total-sales text-end font-monospace fw-semibold text-dark">
                <span class="item-readonly-badge item-total-sales-display">Rp 0</span>
            </td>
            <td class="col-margin text-end font-monospace fw-bold">
                <span class="item-readonly-badge item-margin-display text-success">Rp 0</span>
            </td>
            <td class="text-center col-action">
                <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 shadow-none" onclick="removeReportItemRow(this)" title="Hapus Baris">
                    <i class="bi bi-trash3-fill fs-6"></i>
                </button>
            </td>
            <td class="report-item-hidden-data" style="display:none;">
                <div class="item-info-stock"><span class="item-stock-val">-</span></div>
                <div class="item-info-hpp"><span class="item-hpp-method-val">-</span></div>
                <input type="text" class="item-purchase-price-display" value="Rp 0" readonly tabindex="-1">
                <span class="item-selling-price-display">Rp 0</span>
                <input type="hidden" name="items[${idx}][selling_price]" class="item-selling-price" value="0">
                <span class="item-hpp-display">Rp 0</span>
                <input type="hidden" name="items[${idx}][hpp]" class="item-hpp-price" value="0">
                <span class="item-total-hpp-display">Rp 0</span>
                <input type="number" name="items[${idx}][stock_final]" class="item-stock-final" value="" readonly tabindex="-1" required>
            </td>`;
        tbody.appendChild(tr);

        // Expand row
        const expandTr = document.createElement("tr");
        expandTr.className = "report-item-expand-row d-none";
        expandTr.dataset.expandFor = idx;
        expandTr.innerHTML = `
            <td colspan="6" class="p-0">
                <div class="report-expand-details">
                    <div class="row g-0">
                        <div class="col-4 expand-cell">
                            <div class="expand-label">Stok saat ini</div>
                            <div class="expand-value expand-stock-val">-</div>
                        </div>
                        <div class="col-4 expand-cell">
                            <div class="expand-label">Metode HPP</div>
                            <div class="expand-value expand-hpp-method-val">-</div>
                        </div>
                        <div class="col-4 expand-cell expand-cell-last">
                            <div class="expand-label">Sisa Stok</div>
                            <div class="expand-value expand-stock-final">-</div>
                        </div>
                        <div class="col-4 expand-cell expand-cell-bottom">
                            <div class="expand-label">Harga Beli</div>
                            <div class="expand-value expand-purchase-price">Rp 0</div>
                        </div>
                        <div class="col-4 expand-cell expand-cell-bottom">
                            <div class="expand-label">Harga Jual</div>
                            <div class="expand-value expand-selling-price">Rp 0</div>
                        </div>
                        <div class="col-4 expand-cell expand-cell-last expand-cell-bottom">
                            <div class="expand-label">HPP / Unit</div>
                            <div class="expand-value expand-hpp">Rp 0</div>
                        </div>
                        <div class="col-6 expand-cell expand-cell-bottom expand-cell-noborder">
                            <div class="expand-label">Total HPP</div>
                            <div class="expand-value expand-total-hpp">Rp 0</div>
                        </div>
                    </div>
                </div>
            </td>`;
        tbody.appendChild(expandTr);
    }

    /* -- 2. Mobile: 13-col mirror row (no name attrs) -- */
    const mobileTbody = document.getElementById("reportItemRowsMobile");
    if (mobileTbody) {
        document.getElementById("emptyItemRowMobile")?.remove();

        const mobileTr = document.createElement("tr");
        mobileTr.className = "report-item-row-mobile align-middle";
        mobileTr.dataset.mobileIdx = idx;
        mobileTr.innerHTML = `
            <td class="col-no text-center text-muted fw-medium small">
                <span class="row-no-num"></span>
            </td>
            <td class="col-product">
                <select class="form-select form-select-sm product-select-mobile rounded-2" onchange="onMobileProductChange(this)">
                    ${prodOpts}
                </select>
            </td>
            <td class="col-unit">
                <select class="form-select form-select-sm selling-unit-select-mobile rounded-2" onchange="onMobileUnitChange(this)">
                    <option value="" disabled selected>Pilih Satuan...</option>
                </select>
            </td>
            <td class="col-info">
                <div class="col-info-text">Stok: <span class="item-stock-val">-</span></div>
                <div class="col-info-text">HPP: <span class="item-hpp-method-val">-</span></div>
            </td>
            <td class="col-qty">
                <input type="number" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty-mobile" value="" min="1" placeholder="" oninput="onMobileQtyChange(this)">
            </td>
            <td class="col-stock-final">
                <input type="number" class="form-control form-control-sm font-monospace text-center rounded-2 item-stock-final bg-light text-secondary" value="" readonly tabindex="-1">
            </td>
            <td class="col-price text-end">
                <span class="item-readonly-badge item-purchase-price-display">Rp 0</span>
            </td>
            <td class="col-selling-price text-end">
                <span class="item-readonly-badge item-selling-price-display">Rp 0</span>
            </td>
            <td class="col-total-sales text-end">
                <span class="item-readonly-badge item-total-sales-display">Rp 0</span>
            </td>
            <td class="col-hpp text-end">
                <span class="item-readonly-badge item-hpp-display">Rp 0</span>
            </td>
            <td class="col-total-hpp text-end">
                <span class="item-readonly-badge item-total-hpp-display">Rp 0</span>
            </td>
            <td class="col-margin text-end">
                <span class="item-readonly-badge item-margin-display text-success">Rp 0</span>
            </td>
            <td class="col-action text-center">
                <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 shadow-none" onclick="removeReportItemMobileRow(this)" title="Hapus Baris">
                    <i class="bi bi-trash3-fill fs-6"></i>
                </button>
            </td>`;
        mobileTbody.appendChild(mobileTr);
    }

    updateRowNumbers();
    recalculateReportTotals();
    refreshReportProductOptions();
}

/* ════════════════════════════════════════════════════════════
   REMOVE ROW
   ════════════════════════════════════════════════════════════ */
function removeReportItemRow(btn) {
    const row = btn.closest("tr");
    if (!row) return;

    // Hapus expand row jika ada
    const nextRow = row.nextElementSibling;
    if (nextRow && nextRow.classList.contains("report-item-expand-row")) {
        nextRow.remove();
    }

    // Hapus mobile mirror row
    const sel = row.querySelector(".product-select");
    if (sel) {
        const m = sel.getAttribute("name")?.match(/items\[(\d+)\]/);
        if (m) {
            document
                .querySelector(
                    `.report-item-row-mobile[data-mobile-idx="${m[1]}"]`,
                )
                ?.remove();
            document
                .querySelector(`.report-item-card[data-card-idx="${m[1]}"]`)
                ?.remove();
        }
    }

    row.remove();
    _checkReportEmptyStates();
    updateRowNumbers();
    recalculateReportTotals();
    refreshReportProductOptions();
    _checkFormSubmitState();
}

function removeReportItemMobileRow(btn) {
    const mobileRow = btn.closest("tr");
    if (!mobileRow) return;
    const idx = mobileRow.dataset.mobileIdx;

    // Hapus desktop row + expand row
    const desktopSel = document.querySelector(
        `.product-select[name="items[${idx}][product_id]"]`,
    );
    if (desktopSel) {
        const desktopRow = desktopSel.closest("tr");
        if (desktopRow) {
            const nextRow = desktopRow.nextElementSibling;
            if (
                nextRow &&
                nextRow.classList.contains("report-item-expand-row")
            ) {
                nextRow.remove();
            }
            desktopRow.remove();
        }
    }

    mobileRow.remove();
    _checkReportEmptyStates();
    updateRowNumbers();
    recalculateReportTotals();
    refreshReportProductOptions();
    _checkFormSubmitState();
}

function removeReportItemCard(btn) {
    const card = btn.closest(".report-item-card");
    if (!card) return;
    const idx = card.dataset.cardIdx;
    const sel = document.querySelector(
        `#reportItemRows .product-select[name="items[${idx}][product_id]"]`,
    );
    if (sel) {
        const mainRow = sel.closest("tr");
        if (mainRow) {
            const nextRow = mainRow.nextElementSibling;
            if (
                nextRow &&
                nextRow.classList.contains("report-item-expand-row")
            ) {
                nextRow.remove();
            }
            mainRow.remove();
        }
    }
    document
        .querySelector(`.report-item-row-mobile[data-mobile-idx="${idx}"]`)
        ?.remove();
    card.remove();
    _checkReportEmptyStates();
    updateRowNumbers();
    recalculateReportTotals();
    refreshReportProductOptions();
    _checkFormSubmitState();
}

function _checkReportEmptyStates() {
    // Desktop table
    const tbody = document.getElementById("reportItemRows");
    if (tbody && tbody.querySelectorAll(".report-item-row").length === 0) {
        tbody.innerHTML = `<tr id="emptyItemRow"><td colspan="6" class="text-center py-4 text-muted small">Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item penjualan.</td></tr>`;
    }
    // Mobile table
    const mobileTbody = document.getElementById("reportItemRowsMobile");
    if (
        mobileTbody &&
        mobileTbody.querySelectorAll(".report-item-row-mobile").length === 0
    ) {
        mobileTbody.innerHTML = `<tr id="emptyItemRowMobile"><td colspan="13" class="text-center py-4 text-muted small">Belum ada produk. Klik "+ Tambah" di atas.</td></tr>`;
    }
    // Old mobile cards container (backward compat)
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
window.onMobileProductChange = onMobileProductChange;
window.onMobileUnitChange = onMobileUnitChange;
window.onMobileQtyChange = onMobileQtyChange;
window.addReportItemRow = addReportItemRow;
window.removeReportItemRow = removeReportItemRow;
window.removeReportItemMobileRow = removeReportItemMobileRow;
window.removeReportItemCard = removeReportItemCard;
window.updateRowNumbers = updateRowNumbers;
window.recalculateReportTotals = recalculateReportTotals;
window.refreshReportProductOptions = refreshReportProductOptions;
window.openDeleteModal = openDeleteModal;
window._toggleExpandRow = _toggleExpandRow;

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
            _suppressInitToast = true;
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
            _suppressInitToast = false;
            updateRowNumbers();
            recalculateReportTotals();
            refreshReportProductOptions();
            _checkFormSubmitState();
            existing.forEach((r) => {
                _validateItemQty(r);
                _syncExpandRow(r);
            });
        }
    }
});

/* ════════════════════════════════════════════════════════════
   DATATABLES -- INDEX PAGE (Laporan Penjualan)
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

            order: [[1, "asc"]],

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

        $("#dtSearchInput").on("keyup input", function () {
            dataTable.search(this.value).draw();
        });
    });
}

// Alert Laporan hari ini
function showTodayReportAlert() {
    const modalEl = document.getElementById("modalTodayReportAlert");
    if (modalEl && typeof bootstrap !== "undefined") {
        new bootstrap.Modal(modalEl).show();
    }
}
