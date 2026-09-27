/**
 * WARJOK — Manajemen Restock DataTables & Multi-Items Form JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

$(function () {
    // 1. Inisialisasi DataTables jika ada di halaman index
    if ($("#restockDataTable").length && $.fn.DataTable) {
        initRestockDataTable();
    }

    // 2. Alert auto-dismiss
    setTimeout(function () {
        $(".alert-dismissible").fadeOut("slow");
    }, 5000);

    // 3. Inisialisasi Select2 pada form create/edit (desktop)
    initSelect2Products();

    // 4. Kalkulasi awal saat dimuat
    if ($("#restockItemsTable").length || $("#restockMobileItemCards").length) {
        calculateRestockTotals();
    }
});

let restockRowIndex =
    Math.max(
        $("#restockItemRows tr.restock-item-row").length,
        $("#restockMobileItemCards .restock-mobile-item-card").length,
    ) + 20;

/**
 * Inisialisasi Select2 untuk Satuan Beli & Product Selection
 */
function initSelect2Products(context) {
    const $ctx = context ? $(context) : $(document);

    $ctx.find(".select2-restock-unit").each(function () {
        if (!$(this).hasClass("select2-hidden-accessible")) {
            $(this).select2({
                theme: "bootstrap-5",
                width: "100%",
                placeholder: "Pilih Satuan...",
                allowClear: false,
            });
        }
    });

    $ctx.find(".select2-restock-product").each(function () {
        if (!$(this).hasClass("select2-hidden-accessible")) {
            $(this).select2({
                theme: "bootstrap-5",
                width: "100%",
                placeholder: "Pilih Produk...",
                allowClear: false,
            });
        }
    });
}

/**
 * Deteksi apakah mobile (layar < md)
 */
function isMobileView() {
    return window.innerWidth < 768;
}

/**
 * Tambah Baris Item Restock Baru — dispatch ke desktop/mobile
 */
function addRestockItemRow() {
    if (isMobileView()) {
        addMobileItemCard();
    } else {
        addDesktopItemRow();
    }
}

/* ─────────────────────────────────────────────
   DESKTOP: Tambah baris table
───────────────────────────────────────────── */
function addDesktopItemRow() {
    const tbody = document.getElementById("restockItemRows");
    if (!tbody) return;

    document.getElementById("emptyItemRow")?.remove();

    const productsList = window.restockProductsList || [];
    const unitsList = window.restockUnitsList || [];

    let unitOptionsHtml =
        '<option value="" disabled selected>Pilih Satuan...</option>';
    unitsList.forEach((u) => {
        const uName = u.unit_name || u.short_name;
        const uShort = u.short_name || u.unit_name;
        unitOptionsHtml += `<option value="${u.id}">${uName} (${uShort})</option>`;
    });

    let productOptionsHtml =
        '<option value="" disabled selected>Pilih Produk...</option>';
    productsList.forEach((p) => {
        const uName = p.unit ? p.unit.short_name || p.unit.unit_name : "Pcs";
        const defaultCost = Number(p.unit_price || p.current_hpp || 0);
        const code = p.prod_code || "PRD-" + p.id;
        const stock = p.current_stock || 0;
        productOptionsHtml += `<option value="${p.id}" data-unit-id="${p.unit_id}" data-unit="${uName}" data-stock="${stock}" data-cost="${defaultCost}">${p.prod_name} (${code})</option>`;
    });

    const tr = document.createElement("tr");
    tr.className = "restock-item-row align-middle";
    tr.innerHTML = `
        <td class="row-number text-center text-muted fw-medium small"></td>
        <td>
            <select name="items[${restockRowIndex}][restock_unit_id]" class="form-select form-select-sm select2-restock-unit rounded-2" onchange="onUnitSelectChange(this)" required>
                ${unitOptionsHtml}
            </select>
        </td>
        <td>
            <select name="items[${restockRowIndex}][product_id]" class="form-select form-select-sm select2-restock-product rounded-2" onchange="onProductSelectChange(this)" required>
                ${productOptionsHtml}
            </select>
        </td>
        <td>
            <div class="item-info-cell">
                <div class="item-info-stock text-dark fw-medium small">Stok: <span class="item-stock-val">-</span></div>
                <div class="item-info-hpp text-muted small">HPP: <span class="item-hpp-val">-</span></div>
            </div>
        </td>
        <td>
            <input type="number" name="items[${restockRowIndex}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" min="1" oninput="onItemQtyOrPriceChange(this)" required>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0">Rp</span>
                <input type="number" name="items[${restockRowIndex}][purchase_price]" class="form-control form-control-sm font-monospace text-end rounded-end-2 item-price"  min="0" oninput="onItemQtyOrPriceChange(this)" required>
            </div>
        </td>
        <td class="text-end font-monospace fw-bold text-dark item-subtotal-cell">
            Rp 0
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="removeRestockItemRow(this)" title="Hapus Baris">
                <i class="bi bi-trash3-fill fs-6"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    initSelect2Products(tr);

    $(tr)
        .find(".select2-restock-product")
        .on("select2:select", function () {
            onProductSelectChange(this);
        });

    $(tr)
        .find(".select2-restock-unit")
        .on("select2:select", function () {
            onUnitSelectChange(this);
        });

    restockRowIndex++;
    updateRestockRowNumbers();
    calculateRestockTotals();
}

/* ─────────────────────────────────────────────
   MOBILE: Tambah card item
───────────────────────────────────────────── */
function addMobileItemCard() {
    const container = document.getElementById("restockMobileItemCards");
    if (!container) return;

    document.getElementById("mobileEmptyItemRow")?.remove();

    const productsList = window.restockProductsList || [];
    const unitsList = window.restockUnitsList || [];

    let unitOptionsHtml =
        '<option value="" disabled selected>Pilih Satuan...</option>';
    unitsList.forEach((u) => {
        const uName = u.unit_name || u.short_name;
        const uShort = u.short_name || u.unit_name;
        unitOptionsHtml += `<option value="${u.id}">${uName} (${uShort})</option>`;
    });

    let productOptionsHtml =
        '<option value="" disabled selected>Pilih Produk...</option>';
    productsList.forEach((p) => {
        const uName = p.unit ? p.unit.short_name || p.unit.unit_name : "Pcs";
        const defaultCost = Number(p.unit_price || p.current_hpp || 0);
        const code = p.prod_code || "PRD-" + p.id;
        const stock = p.current_stock || 0;
        productOptionsHtml += `<option value="${p.id}" data-unit-id="${p.unit_id}" data-unit="${uName}" data-stock="${stock}" data-cost="${defaultCost}">${p.prod_name} (${code})</option>`;
    });

    const cardNum =
        container.querySelectorAll(".restock-mobile-item-card").length + 1;

    const div = document.createElement("div");
    div.className = "restock-mobile-item-card mb-3";
    div.dataset.mobileIndex = restockRowIndex;
    div.innerHTML = `
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-secondary rounded-pill mobile-card-num">Item ${cardNum}</span>
            <button type="button" class="btn btn-link text-danger p-0 border-0 small" onclick="removeMobileItemCard(this)" title="Hapus">
                <i class="bi bi-trash3-fill"></i>
            </button>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold text-dark mb-1">Satuan Beli</label>
            <select name="items[${restockRowIndex}][restock_unit_id]" class="form-select form-select-sm select2-restock-unit rounded-2" required>
                ${unitOptionsHtml}
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold text-dark mb-1">Nama Produk</label>
            <select name="items[${restockRowIndex}][product_id]" class="form-select form-select-sm select2-restock-product rounded-2" onchange="onMobileProductSelectChange(this)" required>
                ${productOptionsHtml}
            </select>
        </div>
        <div class="rounded-2 bg-light border px-3 py-2 mb-2 small mobile-info-bar">
            <span class="text-muted">Stok: </span><span class="fw-semibold mobile-stock-val">-</span>
            &nbsp;|&nbsp;
            <span class="text-muted">HPP: </span><span class="fw-semibold mobile-hpp-val">-</span>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-5">
                <label class="form-label small fw-semibold text-dark mb-1">Jumlah Masuk</label>
                <input type="number" name="items[${restockRowIndex}][quantity]" class="form-control form-control-sm font-monospace text-center item-qty" value="1" min="1" oninput="onMobileItemChange(this)" required>
            </div>
            <div class="col-7">
                <label class="form-label small fw-semibold text-dark mb-1">Harga Beli/Unit</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0">Rp</span>
                    <input type="number" name="items[${restockRowIndex}][purchase_price]" class="form-control form-control-sm font-monospace text-end item-price" value="0" min="0" oninput="onMobileItemChange(this)" required>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded-2 bg-success bg-opacity-10 border border-success border-opacity-25">
            <span class="small fw-semibold text-success">Subtotal</span>
            <span class="fw-bold font-monospace text-success mobile-subtotal-val">Rp 0</span>
        </div>
    `;

    container.appendChild(div);
    initSelect2Products(div);

    $(div)
        .find(".select2-restock-product")
        .on("select2:select", function () {
            onMobileProductSelectChange(this);
        });

    restockRowIndex++;
    calculateRestockTotals();
}

/**
 * Hapus Baris Item Restock (desktop)
 */
function removeRestockItemRow(btn) {
    const row = btn.closest("tr");
    if (!row) return;

    $(row).find(".select2-restock-unit").select2("destroy");
    $(row).find(".select2-restock-product").select2("destroy");
    row.remove();

    const tbody = document.getElementById("restockItemRows");
    if (tbody && tbody.querySelectorAll(".restock-item-row").length === 0) {
        tbody.innerHTML = `<tr id="emptyItemRow"><td colspan="8" class="text-center py-4 text-muted small">Belum ada produk yang ditambahkan. Klik tombol "+ Tambah" di atas untuk menambahkan item.</td></tr>`;
    }

    updateRestockRowNumbers();
    calculateRestockTotals();
}

/**
 * Hapus Card Item (mobile)
 */
function removeMobileItemCard(btn) {
    const card = btn.closest(".restock-mobile-item-card");
    if (!card) return;

    $(card).find(".select2-restock-unit").select2("destroy");
    $(card).find(".select2-restock-product").select2("destroy");
    card.remove();

    const container = document.getElementById("restockMobileItemCards");
    if (
        container &&
        container.querySelectorAll(".restock-mobile-item-card").length === 0
    ) {
        const empty = document.createElement("div");
        empty.id = "mobileEmptyItemRow";
        empty.className = "text-center py-4 text-muted small";
        empty.textContent =
            'Belum ada produk. Klik "+ Tambah" di atas untuk menambahkan item.';
        container.appendChild(empty);
    }

    // Update badge nomor
    container.querySelectorAll(".restock-mobile-item-card").forEach((c, i) => {
        const badge = c.querySelector(".mobile-card-num");
        if (badge) badge.textContent = "Item " + (i + 1);
    });

    calculateRestockTotals();
}

/**
 * Update Nomor Baris (desktop)
 */
function updateRestockRowNumbers() {
    document
        .querySelectorAll("#restockItemRows .restock-item-row")
        .forEach((row, i) => {
            const numCell = row.querySelector(".row-number");
            if (numCell) numCell.textContent = i + 1;
        });
}

/**
 * Event Listener saat Satuan Beli Dipilih (desktop)
 */
function onUnitSelectChange(unitSelectEl) {
    const row = unitSelectEl.closest("tr");
    if (!row) return;

    const unitId = unitSelectEl.value;
    if (!unitId) return;

    const productSelect = row.querySelector(".select2-restock-product");
    if (!productSelect) return;

    const currentProdOpt = productSelect.options[productSelect.selectedIndex];
    if (currentProdOpt && currentProdOpt.value) {
        const prodUnitId = currentProdOpt.getAttribute("data-unit-id");
        if (prodUnitId && prodUnitId != unitId) {
            // Unit berbeda dari produk yang dipilih — biarkan
        }
    }
}

/**
 * Auto-fill info & auto-select unit saat produk dipilih (desktop)
 */
function onProductSelectChange(selectEl) {
    const option = selectEl.options[selectEl.selectedIndex];
    if (!option) return;

    const row = selectEl.closest("tr");
    if (!row) return;

    const unitId = option.getAttribute("data-unit-id") || "";
    const unitName = option.getAttribute("data-unit") || "Pcs";
    const stock = option.getAttribute("data-stock") || "0";
    const defaultCost = parseFloat(option.getAttribute("data-cost")) || 0;

    const unitSelect = row.querySelector(".select2-restock-unit");
    if (
        unitSelect &&
        unitId &&
        (!unitSelect.value || unitSelect.value === "")
    ) {
        $(unitSelect).val(unitId).trigger("change");
    }

    const stockValSpan = row.querySelector(".item-stock-val");
    if (stockValSpan) stockValSpan.textContent = stock + " " + unitName;

    const hppValSpan = row.querySelector(".item-hpp-val");
    if (hppValSpan)
        hppValSpan.textContent =
            "Rp " + Math.round(defaultCost).toLocaleString("id-ID");

    const priceInput = row.querySelector(".item-price");
    if (
        priceInput &&
        (!priceInput.value || parseFloat(priceInput.value) === 0)
    ) {
        priceInput.value = Math.round(defaultCost);
    }

    onItemQtyOrPriceChange(selectEl);
}

/**
 * Auto-fill info saat produk dipilih (mobile)
 */
function onMobileProductSelectChange(selectEl) {
    const option = selectEl.options[selectEl.selectedIndex];
    if (!option) return;

    const card = selectEl.closest(".restock-mobile-item-card");
    if (!card) return;

    const unitId = option.getAttribute("data-unit-id") || "";
    const unitName = option.getAttribute("data-unit") || "Pcs";
    const stock = option.getAttribute("data-stock") || "0";
    const defaultCost = parseFloat(option.getAttribute("data-cost")) || 0;

    const unitSelect = card.querySelector(".select2-restock-unit");
    if (
        unitSelect &&
        unitId &&
        (!unitSelect.value || unitSelect.value === "")
    ) {
        $(unitSelect).val(unitId).trigger("change");
    }

    const stockVal = card.querySelector(".mobile-stock-val");
    if (stockVal) stockVal.textContent = stock + " " + unitName;

    const hppVal = card.querySelector(".mobile-hpp-val");
    if (hppVal)
        hppVal.textContent =
            "Rp " + Math.round(defaultCost).toLocaleString("id-ID");

    const priceInput = card.querySelector(".item-price");
    if (
        priceInput &&
        (!priceInput.value || parseFloat(priceInput.value) === 0)
    ) {
        priceInput.value = Math.round(defaultCost);
    }

    onMobileItemChange(selectEl);
}

/**
 * Hitung Subtotal per Item saat Qty atau Harga Berubah (desktop)
 */
function onItemQtyOrPriceChange(inputEl) {
    const row = inputEl.closest("tr");
    if (!row) return;

    const qty = parseInt(row.querySelector(".item-qty")?.value || 0, 10) || 0;
    const price = parseFloat(row.querySelector(".item-price")?.value || 0) || 0;
    const subtotal = qty * price;

    const subtotalCell = row.querySelector(".item-subtotal-cell");
    if (subtotalCell) {
        subtotalCell.textContent =
            "Rp " + Math.round(subtotal).toLocaleString("id-ID");
    }

    calculateRestockTotals();
}

/**
 * Hitung Subtotal per Item saat Qty atau Harga Berubah (mobile)
 */
function onMobileItemChange(inputEl) {
    const card = inputEl.closest(".restock-mobile-item-card");
    if (!card) return;

    const qty = parseInt(card.querySelector(".item-qty")?.value || 0, 10) || 0;
    const price =
        parseFloat(card.querySelector(".item-price")?.value || 0) || 0;
    const subtotal = qty * price;

    const subtotalVal = card.querySelector(".mobile-subtotal-val");
    if (subtotalVal) {
        subtotalVal.textContent =
            "Rp " + Math.round(subtotal).toLocaleString("id-ID");
    }

    calculateRestockTotals();
}

/**
 * Kalkulasi Total Subtotal, Diskon, dan Grand Total Transaksi Restock
 * — gabungkan dari desktop table dan mobile cards
 */
function calculateRestockTotals() {
    let subtotal = 0;

    // Desktop rows
    document
        .querySelectorAll("#restockItemRows .restock-item-row")
        .forEach((row) => {
            const qty =
                parseInt(row.querySelector(".item-qty")?.value || 0, 10) || 0;
            const price =
                parseFloat(row.querySelector(".item-price")?.value || 0) || 0;
            subtotal += qty * price;
        });

    // Mobile cards (jika sedang mobile view, desktop rows mungkin kosong)
    if (isMobileView()) {
        subtotal = 0; // reset, hitung dari mobile
        document
            .querySelectorAll(
                "#restockMobileItemCards .restock-mobile-item-card",
            )
            .forEach((card) => {
                const qty =
                    parseInt(card.querySelector(".item-qty")?.value || 0, 10) ||
                    0;
                const price =
                    parseFloat(card.querySelector(".item-price")?.value || 0) ||
                    0;
                subtotal += qty * price;
            });
    }

    const subtotalInput = document.getElementById("subtotal");
    if (subtotalInput) subtotalInput.value = Math.round(subtotal);

    const discountInput = document.getElementById("discount");
    const discount = parseFloat(discountInput ? discountInput.value : 0) || 0;

    const grandTotal = Math.max(0, subtotal - discount);
    const grandTotalInput = document.getElementById("grand_total");
    if (grandTotalInput) grandTotalInput.value = Math.round(grandTotal);
}

/**
 * Simpan transaksi dengan status DRAFT
 */
function submitRestockAs(status) {
    // Sync mobile cards ke hidden table rows sebelum submit (jika mobile)
    if (isMobileView()) {
        syncMobileToDesktop();
    }

    const statusInput = document.getElementById("status_restock");
    if (statusInput) statusInput.value = status;

    const form = document.getElementById("formCreateRestock");
    if (form) form.submit();
}

/**
 * Buka modal konfirmasi untuk simpan CONFIRMED
 */
function openConfirmRestockModal() {
    const form = document.getElementById("formCreateRestock");

    // Cek item minimal ada 1
    const desktopRows = document.querySelectorAll(
        "#restockItemRows .restock-item-row",
    ).length;
    const mobileCards = document.querySelectorAll(
        "#restockMobileItemCards .restock-mobile-item-card",
    ).length;

    if (desktopRows === 0 && mobileCards === 0) {
        alert("Pilih minimal satu produk untuk direstock.");
        return;
    }

    const modalEl = document.getElementById("modalConfirmRestock");
    if (modalEl && typeof bootstrap !== "undefined") {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

/**
 * Eksekusi submit setelah konfirmasi CONFIRMED
 */
function executeConfirmRestockSubmit() {
    if (isMobileView()) {
        syncMobileToDesktop();
    }

    const statusInput = document.getElementById("status_restock");
    if (statusInput) statusInput.value = "CONFIRMED";

    const form = document.getElementById("formCreateRestock");
    if (form) form.submit();
}
