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

    return (
        product.is_flexible_product === true ||
        product.is_flexible_product === 1
    );
}

function _getQtyUsedByOtherRows(currentRow, productId) {
    if (!productId) return 0;
    let total = 0;
    document
        .querySelectorAll("#reportItemRows .report-item-row")
        .forEach((row) => {
            if (row === currentRow) return;
            const pSel = row.querySelector(".product-select");
            if (!pSel || parseInt(pSel.value, 10) !== parseInt(productId, 10))
                return;
            if (_isGorenganProduct(pSel.value)) return;
            const qtyInput = row.querySelector(".item-qty");
            const qty = qtyInput ? parseInt(qtyInput.value.trim(), 10) || 0 : 0;
            const origQty = parseInt(row.dataset.originalQty || 0, 10);
            total += qty - origQty;
        });
    return total;
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
   Total HPP ditampilkan di expand row:
   - Produk biasa   : teks read-only (.expand-total-hpp)
   - Produk gorengan: input number manual (.expand-total-hpp-input)
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

    // Sisa stok
    const stockFinalInput = container.querySelector(".item-stock-final");
    const stockFinalEl = expandRow.querySelector(".expand-stock-final");
    if (stockFinalEl && stockFinalInput) {
        const val = stockFinalInput.value;
        const num = parseInt(val, 10);
        stockFinalEl.textContent = val !== "" ? val : "-";
        stockFinalEl.classList.toggle("text-danger", val !== "" && num < 0);
        stockFinalEl.classList.toggle("fw-bold", val !== "" && num < 0);
    }

    // Total HPP di expand row
    // Cek apakah produk gorengan
    const prodSel = container.querySelector(".product-select");
    const isGorengan = _isGorenganProduct(prodSel ? prodSel.value : null);
    const totalHppWrap = expandRow.querySelector(".expand-total-hpp-wrap");

    if (totalHppWrap) {
        if (isGorengan) {
            // Render sebagai input manual jika belum ada
            if (!totalHppWrap.querySelector(".expand-total-hpp-input")) {
                // Ambil nilai tersimpan dari hidden input
                const existingTotal = container.querySelector(
                    ".item-total-hpp-display",
                );
                const savedTotalHpp = existingTotal
                    ? parseFloat(
                          existingTotal.textContent
                              .replace(/[^0-9,-]/g, "")
                              .replace(",", "."),
                      ) || 0
                    : 0;

                totalHppWrap.innerHTML = `
                    <div class="expand-label">Total HPP</div>
                    <div class="expand-value">
                        <input type="number"
                               class="form-control form-control-sm font-monospace rounded-2 expand-total-hpp-input"
                               min="0"
                               value="${savedTotalHpp > 0 ? savedTotalHpp : ""}"
                               oninput="_onExpandTotalHppInput(this)"
                               style="width:130px;">
                    </div>`;
            }
        } else {
            // Produk biasa: pastikan tampilan teks (bukan input)
            if (totalHppWrap.querySelector(".expand-total-hpp-input")) {
                totalHppWrap.innerHTML = `
                    <div class="expand-label">Total HPP</div>
                    <div class="expand-value expand-total-hpp">Rp 0</div>`;
            }
            // Update nilai teks
            const totalHppDisplay = container.querySelector(
                ".item-total-hpp-display",
            );
            const expandTotalHpp =
                totalHppWrap.querySelector(".expand-total-hpp");
            if (expandTotalHpp && totalHppDisplay) {
                expandTotalHpp.textContent = totalHppDisplay.textContent;
            }
        }
    }
}

/* ════════════════════════════════════════════════════════════
   HANDLER: Input Total HPP manual di expand row (gorengan)
   ════════════════════════════════════════════════════════════ */
function _onExpandTotalHppInput(inputEl) {
    const expandRow = inputEl.closest("tr.report-item-expand-row");
    if (!expandRow) return;

    const mainRow = expandRow.previousElementSibling;
    if (!mainRow || !mainRow.classList.contains("report-item-row")) return;

    const totalHppVal = parseFloat(inputEl.value) || 0;

    // Simpan ke hidden display span supaya recalculate bisa baca
    const totalHppDisplay = mainRow.querySelector(".item-total-hpp-display");
    if (totalHppDisplay)
        totalHppDisplay.textContent = formatRupiah(totalHppVal);

    // Simpan ke hidden hpp manual (untuk submit & recalculate)
    const hiddenHppManual = mainRow.querySelector(".item-total-hpp-manual");
    if (hiddenHppManual) hiddenHppManual.value = totalHppVal;

    // Baca totalSales dari hidden input
    const hiddenSalesManual = mainRow.querySelector(".item-total-sales-manual");
    const totalSales = hiddenSalesManual
        ? parseFloat(hiddenSalesManual.value) || 0
        : 0;

    const margin = totalSales - totalHppVal;
    const marginEl = mainRow.querySelector(".item-margin-display");
    if (marginEl) {
        marginEl.textContent = formatRupiah(margin);
        marginEl.classList.toggle("text-danger", margin < 0);
        marginEl.classList.toggle("text-success", margin >= 0);
    }

    _syncPairContainer(mainRow);
    recalculateReportTotals();
}
window._onExpandTotalHppInput = _onExpandTotalHppInput;

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

    // GORENGAN: kalkulasi dari input manual di expand row
    const isGorengan = _isGorenganProduct(productId);
    const qtyInput = container.querySelector(".item-qty");

    if (isGorengan) {
        // Set sisa stok = current stock (tidak berubah karena flexible)
        const stockFinalInput = container.querySelector(".item-stock-final");
        if (stockFinalInput) {
            const qtyRaw = qtyInput ? qtyInput.value.trim() : "";
            const qty = qtyRaw !== "" ? parseInt(qtyRaw, 10) || 0 : 0;
            const originalQty = parseInt(
                container.dataset.originalQty || 0,
                10,
            );
            const effectiveStock = currentStock + originalQty;
            stockFinalInput.value = effectiveStock - qty;
        }

        // Total penjualan dari hidden manual sales
        const manualSalesInput = container.querySelector(
            ".item-total-sales-manual",
        );
        const manualSales = manualSalesInput
            ? parseFloat(manualSalesInput.value) || 0
            : 0;

        // Total HPP dari expand row input (jika sudah ada) atau dari hidden manual hpp
        const expandRow = container.nextElementSibling;
        let manualTotalHpp = 0;
        if (
            expandRow &&
            expandRow.classList.contains("report-item-expand-row")
        ) {
            const expandHppInput = expandRow.querySelector(
                ".expand-total-hpp-input",
            );
            if (expandHppInput) {
                manualTotalHpp = parseFloat(expandHppInput.value) || 0;
            }
        }
        // Fallback ke hidden manual hpp jika expand belum dirender
        if (manualTotalHpp === 0) {
            const manualHppInput = container.querySelector(
                ".item-total-hpp-manual",
            );
            manualTotalHpp = manualHppInput
                ? parseFloat(manualHppInput.value) || 0
                : 0;
        }

        // Simpan ke hidden total hpp manual supaya recalculate bisa baca
        const hiddenHppManual = container.querySelector(
            ".item-total-hpp-manual",
        );
        if (hiddenHppManual) hiddenHppManual.value = manualTotalHpp;

        // Update total hpp display (hidden td) supaya sync mobile
        const totalHppDisplay = container.querySelector(
            ".item-total-hpp-display",
        );
        if (totalHppDisplay)
            totalHppDisplay.textContent = formatRupiah(manualTotalHpp);

        const margin = manualSales - manualTotalHpp;

        const salesEl = container.querySelector(".item-total-sales-display");
        if (salesEl) salesEl.textContent = formatRupiah(manualSales);

        const marginEl = container.querySelector(".item-margin-display");
        if (marginEl) {
            marginEl.textContent = formatRupiah(margin);
            marginEl.classList.toggle("text-danger", margin < 0);
            marginEl.classList.toggle("text-success", margin >= 0);
        }

        // Simpan ke hidden inputs untuk submit
        const hiddenSales = container.querySelector(".item-selling-price");
        if (hiddenSales) hiddenSales.value = 0;
        const hiddenHpp = container.querySelector(".item-hpp-price");
        if (hiddenHpp) hiddenHpp.value = 0;
    } else {
        // Produk biasa
        if (qtyInput) qtyInput.removeAttribute("readonly");

        const qtyRaw = qtyInput ? qtyInput.value.trim() : "";
        const qty = qtyRaw !== "" ? parseInt(qtyRaw, 10) || 0 : 0;

        const originalQty = parseInt(container.dataset.originalQty || 0, 10);
        const qtyOtherRows = _getQtyUsedByOtherRows(container, productId);
        const effectiveStock = currentStock + originalQty - qtyOtherRows;

        const stockFinalInput = container.querySelector(".item-stock-final");
        if (stockFinalInput) {
            stockFinalInput.value = effectiveStock - qty;
        }

        const totalSales = qty * sellingPrice;
        const totalHpp = qty * currentHpp;
        const margin = totalSales - totalHpp;

        const salesEl = container.querySelector(".item-total-sales-display");
        if (salesEl) salesEl.textContent = formatRupiah(totalSales);

        // Update total hpp di hidden display (untuk sync mobile)
        const totalHppDisplay = container.querySelector(
            ".item-total-hpp-display",
        );
        if (totalHppDisplay)
            totalHppDisplay.textContent = formatRupiah(totalHpp);

        const marginEl = container.querySelector(".item-margin-display");
        if (marginEl) {
            marginEl.textContent = formatRupiah(margin);
            marginEl.classList.toggle("text-danger", margin < 0);
            marginEl.classList.toggle("text-success", margin >= 0);
        }
    }

    _validateItemQty(container);
    _syncExpandRow(container);

    _syncPairContainer(container);
    recalculateReportTotals();
    _revalidateRelatedRows(container, productId);
}

function _revalidateRelatedRows(currentRow, productId) {
    if (!productId) return;
    document
        .querySelectorAll("#reportItemRows .report-item-row")
        .forEach((row) => {
            if (row === currentRow) return;
            const pSel = row.querySelector(".product-select");
            if (!pSel || parseInt(pSel.value, 10) !== parseInt(productId, 10))
                return;
            if (_isGorenganProduct(pSel.value)) return;

            const qtyInput = row.querySelector(".item-qty");
            const qty = qtyInput ? parseInt(qtyInput.value.trim(), 10) || 0 : 0;
            const originalQty = parseInt(row.dataset.originalQty || 0, 10);

            const product = window.reportProductsList.find(
                (p) => parseInt(p.id, 10) === parseInt(productId, 10),
            );
            if (!product) return;
            const currentStock = parseInt(product.current_stock ?? 0, 10);
            const qtyOtherRows = _getQtyUsedByOtherRows(row, productId);
            const effectiveStock = currentStock + originalQty - qtyOtherRows;

            const stockFinalInput = row.querySelector(".item-stock-final");
            if (stockFinalInput) {
                stockFinalInput.value = effectiveStock - qty;
            }

            // Update expand row sisa stok
            const expandRow = row.nextElementSibling;
            if (
                expandRow &&
                expandRow.classList.contains("report-item-expand-row")
            ) {
                const expandSF = expandRow.querySelector(".expand-stock-final");
                if (expandSF) {
                    const sfNum = effectiveStock - qty;
                    expandSF.textContent = sfNum.toString();
                    expandSF.classList.toggle("text-danger", sfNum < 0);
                    expandSF.classList.toggle("fw-bold", sfNum < 0);
                }
            }

            // Sync mobile stock final
            const nameEl = row.querySelector("[name*='items[']");
            if (nameEl) {
                const nameMatch = nameEl.name.match(/items\[(\d+)\]/);
                if (nameMatch) {
                    const mobileRow = document.querySelector(
                        `.report-item-row-mobile[data-mobile-idx="${nameMatch[1]}"]`,
                    );
                    if (mobileRow) {
                        const mobileSF =
                            mobileRow.querySelector(".item-stock-final");
                        if (mobileSF && stockFinalInput)
                            mobileSF.value = stockFinalInput.value;
                    }
                }
            }
            _validateItemQtySilent(row, effectiveStock);
        });
}

function onProductSelectChange(selectEl) {
    const productId = parseInt(selectEl.value, 10);
    const container =
        selectEl.closest("tr") || selectEl.closest(".report-item-card");
    if (!container) return;

    const selectedProduct = window.reportProductsList.find(
        (p) => parseInt(p.id, 10) === parseInt(productId, 10),
    );

    const isFlexible = selectedProduct
        ? selectedProduct.is_flexible_product === true ||
          selectedProduct.is_flexible_product === 1
        : false;

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
    _applyFlexibleUiToRow(container, isFlexible);
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

    const totalHppDisplay = container.querySelector(".item-total-hpp-display");
    if (totalHppDisplay) totalHppDisplay.textContent = "Rp 0";

    const marginEl = container.querySelector(".item-margin-display");
    if (marginEl) {
        marginEl.textContent = "Rp 0";
        marginEl.classList.remove("text-danger");
        marginEl.classList.add("text-success");
    }

    _clearQtyValidation(container);

    // Reset expand row Total HPP ke teks biasa
    const expandRow = container.nextElementSibling;
    if (expandRow && expandRow.classList.contains("report-item-expand-row")) {
        const totalHppWrap = expandRow.querySelector(".expand-total-hpp-wrap");
        if (totalHppWrap) {
            totalHppWrap.innerHTML = `
                <div class="expand-label">Total HPP</div>
                <div class="expand-value expand-total-hpp">Rp 0</div>`;
        }
    }

    _syncExpandRow(container);

    // GORENGAN: kembalikan qty ke editable saat produk di-reset
    const qtyInputReset = container.querySelector(".item-qty");
    if (qtyInputReset) qtyInputReset.removeAttribute("readonly");
}

/* ════════════════════════════════════════════════════════════
   _applyFlexibleUiToRow
   FIX: Hapus hidden input lama dari report-item-hidden-data td
        sebelum membuat hidden input baru di salesCell,
        agar tidak ada duplikasi name attribute saat form submit.
   ════════════════════════════════════════════════════════════ */
function _applyFlexibleUiToRow(container, isFlexible) {
    const qtyCell = container.querySelector(".item-qty-cell");
    const salesCell = container.querySelector(".item-total-sales-cell");

    if (isFlexible) {
        if (qtyCell) {
            const badge = qtyCell.querySelector(".flexible-qty-badge");
            if (badge) badge.remove();
            const qtyInput = qtyCell.querySelector(".item-qty");
            if (qtyInput) {
                qtyInput.removeAttribute("readonly");
                qtyInput.style.display = "";
            }
        }

        // Total penjualan: input manual di kolom tabel
        if (
            salesCell &&
            !salesCell.querySelector(".item-total-sales-manual-input")
        ) {
            // Ambil hidden input lama dari report-item-hidden-data td
            const existingHiddenSales = container.querySelector(
                ".item-total-sales-manual",
            );
            const salesName = existingHiddenSales
                ? existingHiddenSales.name
                : "";
            const salesValue = existingHiddenSales
                ? parseFloat(existingHiddenSales.value) || 0
                : 0;

            // ✅ FIX: Hapus hidden input lama dari hidden td agar tidak duplikat saat submit
            // Jika hidden input lama berada di luar salesCell (di hidden td), hapus
            if (
                existingHiddenSales &&
                !salesCell.contains(existingHiddenSales)
            ) {
                existingHiddenSales.remove();
            }

            salesCell.innerHTML = `
        <div class="d-flex flex-column gap-1">
            <input type="number"
                   class="form-control form-control-sm font-monospace text-end rounded-2 item-total-sales-manual-input"
                   min="0"
                   value="${salesValue > 0 ? salesValue : ""}"
                   oninput="_onFlexibleSalesInput(this)"
                   style="width:130px;">
            <input type="hidden" name="${salesName}" class="item-total-sales-manual" value="${salesValue}">
        </div>`;
        } else if (
            salesCell &&
            salesCell.querySelector(".item-total-sales-manual-input")
        ) {
            // Jika sudah ada visible input, pastikan hidden input di luar salesCell sudah tidak ada
            // (cegah duplikasi jika _applyFlexibleUiToRow dipanggil 2x)
            const hiddenTd = container.querySelector(
                ".report-item-hidden-data",
            );
            if (hiddenTd) {
                const oldHidden = hiddenTd.querySelector(
                    ".item-total-sales-manual",
                );
                if (oldHidden) oldHidden.remove();
            }
        }

        // Total HPP gorengan: dirender di expand row oleh _syncExpandRow
        // Pastikan expand row sudah diinisialisasi ulang ke state gorengan
        const expandRow = container.nextElementSibling;
        if (
            expandRow &&
            expandRow.classList.contains("report-item-expand-row")
        ) {
            const totalHppWrap = expandRow.querySelector(
                ".expand-total-hpp-wrap",
            );
            if (
                totalHppWrap &&
                !totalHppWrap.querySelector(".expand-total-hpp-input")
            ) {
                totalHppWrap.innerHTML = `
                    <div class="expand-label">Total HPP</div>
                    <div class="expand-value">
                        <input type="number"
                               class="form-control form-control-sm font-monospace rounded-2 expand-total-hpp-input"
                               min="0"
                               value=""
                               placeholder="0"
                               oninput="_onExpandTotalHppInput(this)"
                               style="width:130px;">
                    </div>`;
            }
        }
    } else {
        // Produk biasa: kembalikan tampilan normal
        if (qtyCell) {
            const badge = qtyCell.querySelector(".flexible-qty-badge");
            if (badge) badge.remove();
            const qtyInput = qtyCell.querySelector(".item-qty");
            if (qtyInput) {
                qtyInput.removeAttribute("readonly");
                qtyInput.style.display = "";
            }
        }
        if (salesCell) {
            const flexInputs = salesCell.querySelector(
                ".item-total-sales-manual-input",
            );
            if (flexInputs) {
                // ✅ FIX: Saat revert ke produk biasa, pastikan hidden input
                // dikembalikan ke hidden td supaya form submit tetap benar
                const hiddenTd = container.querySelector(
                    ".report-item-hidden-data",
                );
                const hiddenSalesInCell = salesCell.querySelector(
                    ".item-total-sales-manual",
                );
                if (hiddenTd && hiddenSalesInCell) {
                    // Jika tidak ada hidden input di hidden td, pindahkan kembali
                    const alreadyInHiddenTd = hiddenTd.querySelector(
                        ".item-total-sales-manual",
                    );
                    if (!alreadyInHiddenTd) {
                        hiddenSalesInCell.value = "0";
                        hiddenTd.appendChild(hiddenSalesInCell);
                    }
                }
                salesCell.innerHTML = `<span class="item-readonly-badge item-total-sales-display">Rp 0</span>`;
            }
        }

        // Reset expand row Total HPP ke teks biasa
        const expandRow = container.nextElementSibling;
        if (
            expandRow &&
            expandRow.classList.contains("report-item-expand-row")
        ) {
            const totalHppWrap = expandRow.querySelector(
                ".expand-total-hpp-wrap",
            );
            if (
                totalHppWrap &&
                totalHppWrap.querySelector(".expand-total-hpp-input")
            ) {
                totalHppWrap.innerHTML = `
                    <div class="expand-label">Total HPP</div>
                    <div class="expand-value expand-total-hpp">Rp 0</div>`;
            }
        }
    }
}

function _onFlexibleSalesInput(inputEl) {
    const container = inputEl.closest("tr");
    if (!container) return;

    // ✅ FIX: Cari hidden input di dalam salesCell (bukan di seluruh container,
    // karena hidden input lama di hidden td sudah dihapus)
    const salesCell = container.querySelector(".item-total-sales-cell");
    const hiddenInput = salesCell
        ? salesCell.querySelector(".item-total-sales-manual")
        : container.querySelector(".item-total-sales-manual");
    if (hiddenInput) hiddenInput.value = inputEl.value || 0;

    const salesDisplay = container.querySelector(".item-total-sales-display");
    if (salesDisplay) {
        salesDisplay.textContent = formatRupiah(parseFloat(inputEl.value) || 0);
    }

    const hiddenHppManual = container.querySelector(".item-total-hpp-manual");
    const currentHpp = hiddenHppManual
        ? parseFloat(hiddenHppManual.value) || 0
        : 0;
    const currentSales = parseFloat(inputEl.value) || 0;
    const margin = currentSales - currentHpp;
    const marginEl = container.querySelector(".item-margin-display");
    if (marginEl) {
        marginEl.textContent = formatRupiah(margin);
        marginEl.classList.toggle("text-danger", margin < 0);
        marginEl.classList.toggle("text-success", margin >= 0);
    }

    _updateItemCalculations(container);
    recalculateReportTotals();
}

function _onFlexibleHppInput(inputEl) {
    const container = inputEl.closest("tr");
    if (!container) return;
    const hiddenInput = container.querySelector(".item-total-hpp-manual");
    if (hiddenInput) hiddenInput.value = inputEl.value || 0;
    _updateItemCalculations(container);
    recalculateReportTotals();
}

window._onFlexibleSalesInput = _onFlexibleSalesInput;
window._onFlexibleHppInput = _onFlexibleHppInput;

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

let _toastDebounceMap = {};
function _showStockToast(type, message) {
    if (_suppressInitToast) return;
    const key = type + "|" + message;
    if (_toastDebounceMap[key]) return;
    _toastDebounceMap[key] = true;
    setTimeout(() => {
        delete _toastDebounceMap[key];
    }, 3000);
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

    const _gProdSel = container.querySelector(".product-select");

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

    const prodSelect = container.querySelector(".product-select");
    const productId = prodSelect ? parseInt(prodSelect.value, 10) : null;
    const originalQty = parseInt(container.dataset.originalQty || 0, 10);

    const isGorengan = _isGorenganProduct(productId);

    if (isGorengan) {
        const effectiveStock = currentStock + originalQty;
        return _validateItemQtyWithEffective(container, effectiveStock);
    }

    const qtyOtherRows = _getQtyUsedByOtherRows(container, productId);
    const effectiveStock = currentStock + originalQty - qtyOtherRows;

    return _validateItemQtyWithEffective(container, effectiveStock);
}

function _validateItemQtySilent(container, effectiveStock) {
    const qtyInput = container.querySelector(".item-qty");
    if (!qtyInput) return true;

    const qty = parseInt(qtyInput.value, 10) || 0;

    const prodSelect = container.querySelector(".product-select");
    const productId = prodSelect ? parseInt(prodSelect.value, 10) : null;
    const product = productId
        ? window.reportProductsList.find(
              (p) => parseInt(p.id, 10) === productId,
          )
        : null;
    const currentStockRaw = parseInt(container.dataset.currentStock || 0, 10);
    const minStockRaw =
        product && product.min_stock != null
            ? parseInt(product.min_stock, 10)
            : 0;
    const effectiveMinStock =
        minStockRaw > 0 ? minStockRaw : Math.ceil(currentStockRaw * 0.25);

    const existingMsg = container.querySelector(".item-qty-msg");
    if (existingMsg) existingMsg.remove();
    qtyInput.classList.remove("is-invalid");
    qtyInput.style.borderColor = "";
    qtyInput.style.boxShadow = "";

    if (qty <= 0 || qtyInput.value === "") return true;

    // Tampilkan error visual TANPA toast
    if (qty > effectiveStock) {
        qtyInput.classList.add("is-invalid");
        const msg = document.createElement("div");
        msg.className = "item-qty-msg text-danger fw-semibold";
        msg.style.cssText = "font-size:0.72rem;margin-top:3px;";
        msg.textContent = `Stok tidak cukup! Tersedia: ${effectiveStock}`;
        qtyInput.parentNode.appendChild(msg);
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
    }

    return true;
}

function _validateItemQtyWithEffective(container, effectiveStock) {
    const qtyInput = container.querySelector(".item-qty");
    if (!qtyInput) return true;

    const qty = parseInt(qtyInput.value, 10) || 0;

    const prodSelect = container.querySelector(".product-select");
    const productId = prodSelect ? parseInt(prodSelect.value, 10) : null;
    const product = productId
        ? window.reportProductsList.find(
              (p) => parseInt(p.id, 10) === productId,
          )
        : null;
    const currentStockRaw = parseInt(container.dataset.currentStock || 0, 10);
    const minStockRaw =
        product && product.min_stock != null
            ? parseInt(product.min_stock, 10)
            : 0;
    const effectiveMinStock =
        minStockRaw > 0 ? minStockRaw : Math.ceil(currentStockRaw * 0.25);

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

function _checkFormSubmitState() {
    // const submitBtn = document.querySelector(
    //     "#formCreateReport [type='submit'], #formEditReport [type='submit']",
    // );
    // if (!submitBtn) return;

    // const rows = document.querySelectorAll("#reportItemRows .report-item-row");
    // let hasError = false;
    // rows.forEach(function (row) {
    //     const _gSel = row.querySelector(".product-select");
    //     if (_isGorenganProduct(_gSel ? _gSel.value : null)) return;

    //     const rawStock = row.dataset.currentStock;
    //     if (rawStock === undefined || rawStock === "" || rawStock === null)
    //         return;
    //     const currentStock = parseInt(rawStock, 10);
    //     if (isNaN(currentStock)) return;

    //     const productId = _gSel ? parseInt(_gSel.value, 10) : null;
    //     const originalQty = parseInt(row.dataset.originalQty || 0, 10);
    //     const qtyOtherRows = _getQtyUsedByOtherRows(row, productId);
    //     const effectiveStock = currentStock + originalQty - qtyOtherRows;

    //     const qtyInput = row.querySelector(".item-qty");
    //     const qty = parseInt(qtyInput ? qtyInput.value : "0", 10) || 0;
    //     if (qty > 0 && qty > effectiveStock) hasError = true;
    // });

    // submitBtn.disabled = hasError;
    // submitBtn.classList.toggle("opacity-50", hasError);
    // submitBtn.classList.toggle("pe-none", hasError);
    return;
}

function refreshReportProductOptions() {
    const desktopRows = Array.from(
        document.querySelectorAll("#reportItemRows .report-item-row"),
    );

    desktopRows.forEach((row) => {
        const prodSel = row.querySelector(".product-select");
        const unitSel = row.querySelector(".selling-unit-select");
        if (!prodSel || !unitSel) return;

        const thisProdId = prodSel.value;
        if (!thisProdId) return;

        const usedUnitIds = desktopRows
            .filter((r) => r !== row)
            .map((r) => {
                const pSel = r.querySelector(".product-select");
                const uSel = r.querySelector(".selling-unit-select");
                if (!pSel || !uSel) return null;
                return pSel.value === thisProdId ? uSel.value : null;
            })
            .filter(Boolean);

        Array.from(unitSel.options).forEach((opt) => {
            if (!opt.value) return;
            opt.disabled =
                usedUnitIds.includes(opt.value) && unitSel.value !== opt.value;
        });
    });

    const mobileRows = Array.from(
        document.querySelectorAll(
            "#reportItemRowsMobile .report-item-row-mobile",
        ),
    );

    mobileRows.forEach((mRow) => {
        const mProdSel = mRow.querySelector(".product-select-mobile");
        const mUnitSel = mRow.querySelector(".selling-unit-select-mobile");
        if (!mProdSel || !mUnitSel) return;

        const thisProdId = mProdSel.value;
        if (!thisProdId) return;

        const usedUnitIds = mobileRows
            .filter((r) => r !== mRow)
            .map((r) => {
                const pSel = r.querySelector(".product-select-mobile");
                const uSel = r.querySelector(".selling-unit-select-mobile");
                if (!pSel || !uSel) return null;
                return pSel.value === thisProdId ? uSel.value : null;
            })
            .filter(Boolean);

        Array.from(mUnitSel.options).forEach((opt) => {
            if (!opt.value) return;
            opt.disabled =
                usedUnitIds.includes(opt.value) && mUnitSel.value !== opt.value;
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
            totalQty += qty;

            if (_isGorenganProduct(prodSelect.value)) {
                // Produk fleksibel: ambil dari hidden manual
                // ✅ FIX: Cari di seluruh row (termasuk salesCell) karena
                // hidden input sudah dipindahkan ke salesCell
                const manualSalesInput = row.querySelector(
                    ".item-total-sales-manual",
                );
                grandSales += manualSalesInput
                    ? parseFloat(manualSalesInput.value) || 0
                    : 0;

                // Total HPP: dari expand row input jika terbuka, atau dari hidden manual
                const expandRow = row.nextElementSibling;
                let manualTotalHpp = 0;
                if (
                    expandRow &&
                    expandRow.classList.contains("report-item-expand-row")
                ) {
                    const expandHppInput = expandRow.querySelector(
                        ".expand-total-hpp-input",
                    );
                    if (expandHppInput) {
                        manualTotalHpp = parseFloat(expandHppInput.value) || 0;
                    }
                }
                if (manualTotalHpp === 0) {
                    const manualHppInput = row.querySelector(
                        ".item-total-hpp-manual",
                    );
                    manualTotalHpp = manualHppInput
                        ? parseFloat(manualHppInput.value) || 0
                        : 0;
                }
                grandHpp += manualTotalHpp;
            } else {
                const sellingPrice = parseFloat(row.dataset.sellingPrice || 0);
                const hpp = parseFloat(row.dataset.hpp || 0);
                grandSales += qty * sellingPrice;
                grandHpp += qty * hpp;
            }
        }
    });

    const grandMargin = grandSales - grandHpp;
    const gajiPreview = grandMargin * 0.5;
    const netMargin = grandMargin - gajiPreview;

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
    if (el("displayTotalGrossMargin")) {
        el("displayTotalGrossMargin").textContent = formatRupiah(grandMargin);
        el("displayTotalGrossMargin").classList.toggle(
            "text-danger",
            grandMargin < 0,
        );
        el("displayTotalGrossMargin").classList.toggle(
            "text-success",
            grandMargin >= 0,
        );
    }
    if (el("displayTotalNetMargin")) {
        el("displayTotalNetMargin").textContent = formatRupiah(netMargin);
        el("displayTotalNetMargin").classList.toggle(
            "text-danger",
            netMargin < 0,
        );
        el("displayTotalNetMargin").classList.toggle(
            "text-primary",
            netMargin >= 0,
        );
    }
}

function updateRowNumbers() {
    document
        .querySelectorAll("#reportItemRows .report-item-row")
        .forEach((row, i) => {
            const c = row.querySelector(".row-no-num");
            if (c) c.textContent = i + 1;
        });
    document
        .querySelectorAll("#reportItemRowsMobile .report-item-row-mobile")
        .forEach((row, i) => {
            const c = row.querySelector(".row-no-num");
            if (c) c.textContent = i + 1;
        });
    document.querySelectorAll(".report-item-card").forEach((card, i) => {
        const b = card.querySelector(".ric-row-number-mobile");
        if (b) b.textContent = i + 1;
    });
}

/* ════════════════════════════════════════════════════════════
   ADD ROW — Desktop (5-col + expand) + Mobile mirror (13-col)
   Kolom Total HPP dihapus dari desktop, dipindah ke expand row
   ════════════════════════════════════════════════════════════ */
function addReportItemRow() {
    const idx = reportItemIndex++;
    const prodOpts = _buildProductOptions();

    /* -- 1. Desktop: 5-col row + expand row -- */
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
            <td class="col-qty item-qty-cell">
                <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="" min="1" oninput="onItemQtyOrStockFinalChange(this)" required>
            </td>
            <td class="col-total-sales text-end font-monospace fw-semibold text-dark item-total-sales-cell">
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
                <input type="hidden" name="items[${idx}][total_sales_manual]" class="item-total-sales-manual" value="0">
                <input type="hidden" name="items[${idx}][total_hpp_manual]" class="item-total-hpp-manual" value="0">
                <input type="number" name="items[${idx}][stock_final]" class="item-stock-final" value="" readonly tabindex="-1" required>
            </td>`;
        tbody.appendChild(tr);

        // Expand row — Total HPP ada di sini sebagai .expand-total-hpp-wrap
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
                        <div class="col-6 expand-cell expand-cell-bottom expand-cell-noborder expand-total-hpp-wrap">
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

    const nextRow = row.nextElementSibling;
    if (nextRow && nextRow.classList.contains("report-item-expand-row")) {
        nextRow.remove();
    }

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
    const tbody = document.getElementById("reportItemRows");
    if (tbody && tbody.querySelectorAll(".report-item-row").length === 0) {
        tbody.innerHTML = `<tr id="emptyItemRow"><td colspan="6" class="text-center py-4 text-muted small">Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item penjualan.</td></tr>`;
    }
    const mobileTbody = document.getElementById("reportItemRowsMobile");
    if (
        mobileTbody &&
        mobileTbody.querySelectorAll(".report-item-row-mobile").length === 0
    ) {
        mobileTbody.innerHTML = `<tr id="emptyItemRowMobile"><td colspan="13" class="text-center py-4 text-muted small">Belum ada produk. Klik "+ Tambah" di atas.</td></tr>`;
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

                    const isFlexible = _isGorenganProduct(pSel.value);
                    if (isFlexible) {
                        // ✅ FIX: Baca nilai tersimpan dari hidden input SEBELUM
                        // _applyFlexibleUiToRow dipanggil (karena setelah dipanggil,
                        // hidden input di hidden td akan dihapus)
                        const hiddenTd = r.querySelector(
                            ".report-item-hidden-data",
                        );
                        const hiddenSalesBefore = hiddenTd
                            ? hiddenTd.querySelector(".item-total-sales-manual")
                            : null;
                        const hiddenHpp = r.querySelector(
                            ".item-total-hpp-manual",
                        );

                        const savedSalesValue = hiddenSalesBefore
                            ? parseFloat(hiddenSalesBefore.value) || 0
                            : 0;
                        const savedHppValue = hiddenHpp
                            ? parseFloat(hiddenHpp.value) || 0
                            : 0;

                        _applyFlexibleUiToRow(r, true);

                        // Setelah _applyFlexibleUiToRow, hidden input sales ada di salesCell
                        const salesCell = r.querySelector(
                            ".item-total-sales-cell",
                        );

                        // Isi visible sales input dan hidden input di salesCell
                        if (salesCell) {
                            const visibleSalesInput = salesCell.querySelector(
                                ".item-total-sales-manual-input",
                            );
                            const hiddenSalesInCell = salesCell.querySelector(
                                ".item-total-sales-manual",
                            );

                            if (visibleSalesInput && savedSalesValue > 0) {
                                visibleSalesInput.value = savedSalesValue;
                            }
                            // ✅ FIX: Pastikan hidden input di salesCell juga punya nilai yang benar
                            if (hiddenSalesInCell && savedSalesValue > 0) {
                                hiddenSalesInCell.value = savedSalesValue;
                            }
                        }

                        // Isi visible hpp input di expand row
                        const expandRow = r.nextElementSibling;
                        if (
                            expandRow &&
                            expandRow.classList.contains(
                                "report-item-expand-row",
                            )
                        ) {
                            const expandHppInput = expandRow.querySelector(
                                ".expand-total-hpp-input",
                            );
                            if (expandHppInput && savedHppValue > 0) {
                                expandHppInput.value = savedHppValue;
                            }
                        }

                        // Pastikan hidden hpp manual juga ter-update
                        const hiddenHppManual = r.querySelector(
                            ".item-total-hpp-manual",
                        );
                        if (hiddenHppManual && savedHppValue > 0) {
                            hiddenHppManual.value = savedHppValue;
                        }

                        // Recalculate setelah value diisi
                        _updateItemCalculations(r);
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

            order: [[1, "desc"]],

            columnDefs: [
                { targets: "no-sort", orderable: false },
                { targets: 0, responsivePriority: 1 },
                { targets: 1, responsivePriority: 2 },
                { targets: 2, responsivePriority: 6 },
                { targets: 3, responsivePriority: 5 },
                { targets: 4, responsivePriority: 3 },
                { targets: 5, responsivePriority: 7 },
                { targets: 6, responsivePriority: 1 },
                { targets: 7, responsivePriority: 10 },
                { targets: 8, responsivePriority: 9 },
                { targets: 9, responsivePriority: 8 },
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

function showTodayReportAlert() {
    const modalEl = document.getElementById("modalTodayReportAlert");
    if (modalEl && typeof bootstrap !== "undefined") {
        new bootstrap.Modal(modalEl).show();
    }
}

/* ════════════════════════════════════════════════════════════
   SALARY CALCULATOR — Kalkulator Gaji Pekerja
   Dijalankan hanya di halaman salary_calculator
   ════════════════════════════════════════════════════════════ */
(function initSalaryCalculator() {
    const scDataEl = document.getElementById("scData");
    if (!scDataEl) return;

    const baseMargin = parseFloat(scDataEl.dataset.baseMargin) || 0;
    const saveUrl = scDataEl.dataset.saveUrl;
    const csrfToken = scDataEl.dataset.csrf;
    const startDate = scDataEl.dataset.startDate;
    const endDate = scDataEl.dataset.endDate;
    const reportId = scDataEl.dataset.reportId || "";

    const savedCommissionType = scDataEl.dataset.savedCommissionType || "";
    const savedCommissionValue = scDataEl.dataset.savedCommissionValue || "";

    const komisiOpsi = document.getElementById("komisiOpsi");
    const komisiNilai = document.getElementById("komisiNilai");
    const prefixLabel = document.getElementById("prefixLabel");
    const labelSatuan = document.getElementById("labelSatuan");
    const rumusDisplay = document.getElementById("rumusDisplay");
    const hasilGaji = document.getElementById("hasilGaji");
    const hasilGajiSub = document.getElementById("hasilGajiSub");
    const hasilPemilik = document.getElementById("hasilPemilik");
    const btnSimpan = document.getElementById("btnSimpan");
    const btnCetak = document.getElementById("btnCetakStruk");
    const btnKonfirm = document.getElementById("btnSimpanKonfirm");
    const simpanSum = document.getElementById("simpanSummary");
    const catatanEl = document.getElementById("catatanKomisi");

    if (!komisiOpsi || !komisiNilai) return;

    function fRp(val) {
        const num = Math.round(parseFloat(val) || 0);
        return "Rp " + num.toLocaleString("id-ID");
    }

    function updatePrefix(resetValue) {
        const isNominal = komisiOpsi.value === "nominal";
        prefixLabel.textContent = isNominal ? "Rp" : "%";
        labelSatuan.textContent = isNominal ? "(Rp)" : "(%)";
        if (resetValue) {
            komisiNilai.value = "";
        }
        calculate();
    }

    function calculate() {
        const nilaiRaw = parseFloat(komisiNilai.value);
        const opsi = komisiOpsi.value;

        if (!komisiNilai.value || isNaN(nilaiRaw) || nilaiRaw < 0) {
            rumusDisplay.textContent = "—";
            hasilGaji.textContent = "Rp 0";
            hasilGajiSub.textContent = "";
            hasilPemilik.textContent = "Rp 0";
            return;
        }

        let gajiPekerja = 0;
        let rumus = "";

        if (opsi === "persentase") {
            gajiPekerja = baseMargin * (nilaiRaw / 100);
            rumus =
                fRp(baseMargin) + " × " + nilaiRaw + "% = " + fRp(gajiPekerja);
            hasilGajiSub.textContent = nilaiRaw + "% dari total margin";
        } else {
            gajiPekerja = nilaiRaw;
            rumus = "Nominal tetap = " + fRp(gajiPekerja);
            hasilGajiSub.textContent = "Nominal tetap";
        }

        const bagianPemilik = baseMargin - gajiPekerja;

        rumusDisplay.textContent = rumus;
        hasilGaji.textContent = fRp(gajiPekerja);
        hasilPemilik.textContent = fRp(bagianPemilik);

        hasilPemilik.classList.toggle("text-danger", bagianPemilik < 0);
        hasilPemilik.classList.toggle("text-dark", bagianPemilik >= 0);
    }

    komisiOpsi.addEventListener("change", function () {
        updatePrefix(true);
    });
    komisiNilai.addEventListener("input", calculate);

    updatePrefix(false);

    if (savedCommissionValue !== "" && parseFloat(savedCommissionValue) >= 0) {
        calculate();
    }

    if (btnSimpan) {
        btnSimpan.addEventListener("click", function () {
            const nilaiRaw = parseFloat(komisiNilai.value);
            if (!komisiNilai.value || isNaN(nilaiRaw) || nilaiRaw < 0) {
                alert("Masukkan nilai komisi terlebih dahulu.");
                return;
            }

            const opsi = komisiOpsi.value;
            const gajiPekerja =
                opsi === "persentase"
                    ? baseMargin * (nilaiRaw / 100)
                    : nilaiRaw;
            const bagianPemilik = baseMargin - gajiPekerja;

            if (simpanSum) {
                simpanSum.innerHTML = `
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <span class="text-muted">Periode</span>
                        <span class="fw-semibold">${startDate === endDate ? startDate : startDate + " – " + endDate}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <span class="text-muted">Total Margin Kotor</span>
                        <span class="fw-semibold font-monospace">${fRp(baseMargin)}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <span class="text-muted">Opsi Komisi</span>
                        <span class="fw-semibold">${opsi === "persentase" ? "Persentase (" + nilaiRaw + "%)" : "Nominal"}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <span class="text-muted">Gaji Pekerja</span>
                        <span class="fw-bold text-primary font-monospace">${fRp(gajiPekerja)}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Margin Bersih</span>
                        <span class="fw-bold font-monospace ${bagianPemilik < 0 ? "text-danger" : "text-dark"}">${fRp(bagianPemilik)}</span>
                    </div>`;
            }

            const modalEl = document.getElementById("modalSimpanKonfirmasi");
            if (modalEl && typeof bootstrap !== "undefined") {
                new bootstrap.Modal(modalEl).show();
            }
        });
    }

    if (btnKonfirm) {
        btnKonfirm.addEventListener("click", function () {
            const nilaiRaw = parseFloat(komisiNilai.value) || 0;
            const opsi = komisiOpsi.value;
            const gajiPekerja =
                opsi === "persentase"
                    ? baseMargin * (nilaiRaw / 100)
                    : nilaiRaw;
            const bagianPemilik = baseMargin - gajiPekerja;
            const catatan = catatanEl ? catatanEl.value : "";

            btnKonfirm.disabled = true;
            btnKonfirm.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

            const payload = {
                start_date: startDate,
                end_date: endDate,
                commission_type: opsi,
                commission_value: nilaiRaw,
                profit_share_amount: gajiPekerja,
                owner_share_amount: bagianPemilik,
                notes: catatan,
            };

            if (reportId !== "") {
                payload.report_id = reportId;
            }

            fetch(saveUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify(payload),
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    const modalEl = document.getElementById(
                        "modalSimpanKonfirmasi",
                    );
                    if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();

                    const toast = document.getElementById("scToast");
                    const toastMsg = document.getElementById("scToastMsg");
                    if (toast && toastMsg) {
                        const ok = data.status === "success";
                        toast.className =
                            "toast align-items-center border-0 text-white " +
                            (ok ? "bg-success" : "bg-danger");
                        toastMsg.textContent = ok
                            ? "Data komisi berhasil disimpan."
                            : data.message || "Gagal menyimpan.";
                        new bootstrap.Toast(toast, { delay: 4000 }).show();
                    }
                })
                .catch(function () {
                    alert("Terjadi kesalahan. Coba lagi.");
                })
                .finally(function () {
                    btnKonfirm.disabled = false;
                    btnKonfirm.innerHTML =
                        '<i class="bi bi-check-lg me-1"></i> Ya, Simpan';
                });
        });
    }

    if (btnCetak) {
        btnCetak.addEventListener("click", function () {
            const nilaiRaw = parseFloat(komisiNilai.value) || 0;
            const opsi = komisiOpsi.value;
            const gajiPekerja =
                opsi === "persentase"
                    ? baseMargin * (nilaiRaw / 100)
                    : nilaiRaw;
            const bagianPemilik = baseMargin - gajiPekerja;
            const catatan = catatanEl ? catatanEl.value : "";
            const tgl =
                startDate === endDate
                    ? startDate
                    : startDate + " s/d " + endDate;

            const printArea = document.getElementById("printArea");
            if (!printArea) return;
            printArea.innerHTML = `
                <div class="sc-struk-title">WARUNG POJOK OREMUS</div>
                <div class="sc-struk-title" style="font-size:.8rem;">Struk Kalkulasi Gaji Pekerja</div>
                <div class="sc-struk-line"></div>
                <div class="sc-struk-row"><span>Periode</span><span>${tgl}</span></div>
                <div class="sc-struk-row"><span>Total Margin</span><span>${fRp(baseMargin)}</span></div>
                <div class="sc-struk-row"><span>Opsi Komisi</span><span>${opsi === "persentase" ? nilaiRaw + "%" : "Nominal"}</span></div>
                <div class="sc-struk-line"></div>
                <div class="sc-struk-row"><span>Gaji Pekerja</span><span>${fRp(gajiPekerja)}</span></div>
                <div class="sc-struk-row"><span>Bagian Pemilik</span><span>${fRp(bagianPemilik)}</span></div>
                ${catatan ? '<div class="sc-struk-line"></div><div style="font-size:.75rem;">Catatan: ' + catatan + "</div>" : ""}
                <div class="sc-struk-line"></div>
                <div style="text-align:center;font-size:.72rem;">Terima kasih</div>`;
            window.print();
        });
    }
})();

function onFlexibleManualInput(inputEl) {
    if (!inputEl) return;
    const container =
        inputEl.closest("tr") || inputEl.closest(".report-item-card");
    if (!container) return;
    _updateItemCalculations(container);
    recalculateReportTotals();
}
window.onFlexibleManualInput = onFlexibleManualInput;
