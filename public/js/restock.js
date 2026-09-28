/**
 * WARJOK — Manajemen Restock DataTables & Multi-Items Form JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 *
 * Handles:
 *  - Halaman Index      : DataTables init, mobile filter/search, delete modal
 *  - Halaman Create/Edit: Select2, tambah/hapus item, kalkulasi total, submit form
 */

$(function () {
    // ── INDEX PAGE ──────────────────────────────────────
    if (
        $("#restockDataTable").length &&
        typeof $.fn.DataTable !== "undefined"
    ) {
        initRestockDataTable();
        stampMobileCardDates();
        initMobileFilterSearch();
    }

    // ── SEMUA HALAMAN ────────────────────────────────────
    // Alert auto-dismiss
    setTimeout(function () {
        $(".alert-dismissible").fadeOut("slow");
    }, 5000);

    // ── CREATE / EDIT PAGE ───────────────────────────────
    initSelect2Products();

    if ($("#restockItemsTable").length || $("#restockMobileItemCards").length) {
        calculateRestockTotals();
    }
});

let restockRowIndex =
    Math.max(
        $("#restockItemRows tr.restock-item-row").length,
        $("#restockMobileItemCards .restock-mobile-item-card").length,
    ) + 20;

/* ═══════════════════════════════════════════════════════════
   INDEX — DATATABLES
═══════════════════════════════════════════════════════════ */

var restockDT = null;

function initRestockDataTable() {
    /*
     * Struktur kolom (8 kolom visible):
     *  0 = No          → no-sort
     *  1 = No. Invoice
     *  2 = Kode Restock
     *  3 = Tanggal     → default sort desc
     *  4 = Supplier    → filter kolom
     *  5 = Status
     *  6 = Total Item
     *  7 = Aksi        → no-sort
     *
     * Grand Total & Petugas → responsive child row (expand/collapse ▶)
     * Data diambil dari data-grand-total & data-petugas pada <tr>.
     */
    /*
     * Struktur kolom (10 kolom total):
     *  0 = No            → no-sort
     *  1 = No. Invoice
     *  2 = Kode Restock
     *  3 = Tanggal       → default sort desc
     *  4 = Supplier      → applyColumnFilters column(4)
     *  5 = Status
     *  6 = Total Item
     *  7 = Grand Total   → className:'none' → selalu di child row
     *  8 = Petugas       → className:'none' → selalu di child row
     *  9 = Aksi          → no-sort
     */
    restockDT = $("#restockDataTable").DataTable({
        dom: "t<'row mt-2 align-items-center'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        pageLength: 25,
        responsive: true,
        autoWidth: false,
        order: [[3, "desc"]],
        language: {
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoFiltered: "(difilter dari _MAX_ total)",
            lengthMenu: "Tampilkan _MENU_ baris",
            zeroRecords: `<div class="d-flex flex-column align-items-center justify-content-center py-4 text-center text-muted">
                <i class="bi bi-search fs-3 mb-2 opacity-50"></i>
                <div class="fw-semibold small">Data tidak ditemukan</div>
                <div class="small">Coba gunakan kata kunci atau filter yang berbeda.</div>
            </div>`,
            emptyTable: `<div class="d-flex flex-column align-items-center justify-content-center py-4 text-center text-muted">
                <i class="bi bi-inbox fs-3 mb-2 opacity-50"></i>
                <div class="fw-semibold small">Belum ada riwayat restock</div>
                <div class="small">Klik <strong>+ Tambah</strong> untuk mencatat transaksi restock baru.</div>
            </div>`,
            paginate: { first: "«", last: "»", next: "›", previous: "‹" },
        },
        columnDefs: [
            { targets: [0, 9], orderable: false },
            { targets: [7, 8], className: "none" },
            { targets: 9, responsivePriority: 1 },
            { targets: 0, responsivePriority: 2 },
        ],
    });

    // Search box → DataTables
    $("#dtSearchInput").on("input", function () {
        restockDT.search($(this).val()).draw();
        if (window.innerWidth < 768) filterMobileCards();
    });

    // Apply filter
    $("#btnApplyFilter").on("click", function () {
        applyColumnFilters();
        if (window.innerWidth < 768) filterMobileCards();

        var hasFilter =
            $("#modalFilterSupplier").val() ||
            $("#modalFilterStartDate").val() ||
            $("#modalFilterEndDate").val();
        $("#activeFilterBadge").toggleClass("d-none", !hasFilter);
    });

    // Reset filter
    $("#btnResetFilter").on("click", function () {
        $("#modalFilterSupplier").val("");
        $("#modalFilterStartDate").val("");
        $("#modalFilterEndDate").val("");
        restockDT.search("").columns().search("").draw();
        $("#activeFilterBadge").addClass("d-none");
        if (window.innerWidth < 768) filterMobileCards();
    });
}

function applyColumnFilters() {
    if (!restockDT) return;

    var supplier = $("#modalFilterSupplier").val() || "";
    var startDate = $("#modalFilterStartDate").val() || "";
    var endDate = $("#modalFilterEndDate").val() || "";

    restockDT.column(4).search(supplier);

    // Range filter tanggal via custom search
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== "restockDataTable") return true;
        if (!startDate && !endDate) return true;
        var rowDate =
            $($("#restockDataTable tbody tr")[dataIndex]).data("date") || "";
        return (
            (!startDate || rowDate >= startDate) &&
            (!endDate || rowDate <= endDate)
        );
    });

    restockDT.draw();
    $.fn.dataTable.ext.search.pop();
}

/* ═══════════════════════════════════════════════════════════
   INDEX — MOBILE FILTER & SEARCH
═══════════════════════════════════════════════════════════ */

function stampMobileCardDates() {
    document
        .querySelectorAll("#restockDataTable tbody tr[data-date]")
        .forEach(function (tr, i) {
            var cards = document.querySelectorAll(".restock-list-card");
            if (cards[i]) cards[i].dataset.date = tr.dataset.date;
        });
}

function filterMobileCards() {
    var q = (document.getElementById("dtSearchInput")?.value || "")
        .toLowerCase()
        .trim();
    var supplier = (
        document.getElementById("modalFilterSupplier")?.value || ""
    ).toLowerCase();
    var startDate =
        document.getElementById("modalFilterStartDate")?.value || "";
    var endDate = document.getElementById("modalFilterEndDate")?.value || "";

    var anyVisible = false;

    document.querySelectorAll(".restock-list-card").forEach(function (card) {
        var code = (
            card.querySelector(".rlc-code")?.textContent || ""
        ).toLowerCase();
        var sup = (
            card.querySelector(".rlc-supplier")?.textContent || ""
        ).toLowerCase();
        var dateRaw = card.dataset.date || "";

        var visible =
            (!q || code.includes(q) || sup.includes(q)) &&
            (!supplier || sup.includes(supplier)) &&
            (!startDate || dateRaw >= startDate) &&
            (!endDate || dateRaw <= endDate);

        card.style.display = visible ? "" : "none";
        if (visible) anyVisible = true;
    });

    var empty = document.getElementById("mobileCardsEmpty");
    if (!anyVisible) {
        if (!empty) {
            empty = document.createElement("div");
            empty.id = "mobileCardsEmpty";
            empty.className = "restock-mobile-list-empty";
            empty.innerHTML =
                '<i class="bi bi-search d-block mb-2 fs-4 opacity-40"></i>Tidak ada hasil yang cocok.';
            document.getElementById("restockMobileCards")?.appendChild(empty);
        }
        empty.style.display = "";
    } else if (empty) {
        empty.style.display = "none";
    }
}

function initMobileFilterSearch() {
    document
        .getElementById("dtSearchInput")
        ?.addEventListener("input", function () {
            if (window.innerWidth < 768) filterMobileCards();
        });
    document
        .getElementById("btnApplyFilter")
        ?.addEventListener("click", function () {
            if (window.innerWidth < 768) setTimeout(filterMobileCards, 100);
        });
    document
        .getElementById("btnResetFilter")
        ?.addEventListener("click", function () {
            if (window.innerWidth < 768) setTimeout(filterMobileCards, 50);
        });
}

/* ═══════════════════════════════════════════════════════════
   INDEX — DELETE MODAL
═══════════════════════════════════════════════════════════ */

function openDeleteModal(id, code, totalQty, grandTotal) {
    document.getElementById("deleteRestockCode").textContent = code;
    document.getElementById("deleteRestockItemCount").textContent =
        totalQty + " Unit";
    document.getElementById("deleteRestockTotalValue").textContent =
        "Rp " + Math.round(grandTotal).toLocaleString("id-ID");

    var form = document.getElementById("deleteRestockForm");
    if (form) form.action = "/restock/" + id;

    var modalEl = document.getElementById("modalDeleteRestock");
    if (modalEl && typeof bootstrap !== "undefined") {
        new bootstrap.Modal(modalEl).show();
    }
}

/* ═══════════════════════════════════════════════════════════
   CREATE / EDIT — SELECT2
═══════════════════════════════════════════════════════════ */

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

/* ═══════════════════════════════════════════════════════════
   CREATE / EDIT — HELPERS
═══════════════════════════════════════════════════════════ */

function isMobileView() {
    return window.innerWidth < 768;
}

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
            <input type="number" name="items[${restockRowIndex}][quantity]" class="form-control form-control-sm font-monospace text-center rounded-2 item-qty" value="1" min="1" oninput="onItemQtyOrPriceChange(this)" required>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0">Rp</span>
                <input type="number" name="items[${restockRowIndex}][purchase_price]" class="form-control form-control-sm font-monospace text-end rounded-end-2 item-price" value="" min="0" placeholder="" oninput="onItemQtyOrPriceChange(this)" required>
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
            <select name="items[${restockRowIndex}][restock_unit_id]" class="form-select form-select-sm select2-restock-unit rounded-2" onchange="onUnitSelectChange(this)" required>
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
                    <input type="number" name="items[${restockRowIndex}][purchase_price]" class="form-control form-control-sm font-monospace text-end item-price" value="" min="0" placeholder="" oninput="onMobileItemChange(this)" required>
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

    $(div)
        .find(".select2-restock-unit")
        .on("select2:select", function () {
            onUnitSelectChange(this);
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
 * Helper reset tampilan cell informasi stok/HPP pada baris
 */
function resetRowInfo(row) {
    const stockValSpan =
        row.querySelector(".item-stock-val") ||
        row.querySelector(".mobile-stock-val");
    if (stockValSpan) stockValSpan.textContent = "-";

    const hppValSpan =
        row.querySelector(".item-hpp-val") ||
        row.querySelector(".mobile-hpp-val");
    if (hppValSpan) hppValSpan.textContent = "-";

    const subtotalCell =
        row.querySelector(".item-subtotal-cell") ||
        row.querySelector(".mobile-subtotal-val");
    if (subtotalCell) subtotalCell.textContent = "Rp 0";
}

/**
 * Event Listener saat Satuan Beli Dipilih (desktop & mobile)
 * Memfilter opsi dropdown Produk agar HANYA menampilkan produk yang memiliki unit_id sesuai satuan yang dipilih.
 */
function onUnitSelectChange(unitSelectEl) {
    const row =
        unitSelectEl.closest("tr") ||
        unitSelectEl.closest(".restock-mobile-item-card");
    if (!row) return;

    const unitId = unitSelectEl.value;
    const productSelect = row.querySelector(".select2-restock-product");
    if (!productSelect) return;

    const productsList = window.restockProductsList || [];

    // Filter produk berdasarkan unit_id jika Satuan Beli dipilih
    const filteredProducts = unitId
        ? productsList.filter((p) => p.unit_id == unitId)
        : productsList;

    let productOptionsHtml =
        '<option value="" disabled selected>Pilih Produk...</option>';
    filteredProducts.forEach((p) => {
        const uName = p.unit ? p.unit.short_name || p.unit.unit_name : "Pcs";
        const defaultCost = Number(p.unit_price || p.current_hpp || 0);
        const code = p.prod_code || "PRD-" + p.id;
        const stock = p.current_stock || 0;
        productOptionsHtml += `<option value="${p.id}" data-unit-id="${p.unit_id}" data-unit="${uName}" data-stock="${stock}" data-cost="${defaultCost}">${p.prod_name} (${code})</option>`;
    });

    const $prodSel = $(productSelect);
    $prodSel.html(productOptionsHtml);
    $prodSel.val(""); // Biarkan kosong/unselected secara default

    // Reset cell info (stok & HPP)
    resetRowInfo(row);

    // Trigger update Select2
    $prodSel.trigger("change.select2");
}

/**
 * Auto-fill info stok/HPP saat produk dipilih (desktop)
 * Catatan: HARGA BELI sengaja TIDAK diisi otomatis agar diisi manual oleh user/admin.
 */
function onProductSelectChange(selectEl) {
    const option = selectEl.options[selectEl.selectedIndex];
    const row = selectEl.closest("tr");
    if (!row) return;

    if (!option || !selectEl.value) {
        resetRowInfo(row);
        calculateRestockTotals();
        return;
    }

    const unitName = option.getAttribute("data-unit") || "Pcs";
    const stock = option.getAttribute("data-stock") || "0";
    const defaultCost = parseFloat(option.getAttribute("data-cost")) || 0;

    const stockValSpan = row.querySelector(".item-stock-val");
    if (stockValSpan) stockValSpan.textContent = stock + " " + unitName;

    const hppValSpan = row.querySelector(".item-hpp-val");
    if (hppValSpan)
        hppValSpan.textContent =
            "Rp " + Math.round(defaultCost).toLocaleString("id-ID");

    onItemQtyOrPriceChange(selectEl);
}

/**
 * Auto-fill info stok/HPP saat produk dipilih (mobile)
 * Catatan: HARGA BELI sengaja TIDAK diisi otomatis agar diisi manual oleh user/admin.
 */
function onMobileProductSelectChange(selectEl) {
    const option = selectEl.options[selectEl.selectedIndex];
    const card = selectEl.closest(".restock-mobile-item-card");
    if (!card) return;

    if (!option || !selectEl.value) {
        resetRowInfo(card);
        calculateRestockTotals();
        return;
    }

    const unitName = option.getAttribute("data-unit") || "Pcs";
    const stock = option.getAttribute("data-stock") || "0";
    const defaultCost = parseFloat(option.getAttribute("data-cost")) || 0;

    const stockVal = card.querySelector(".mobile-stock-val");
    if (stockVal) stockVal.textContent = stock + " " + unitName;

    const hppVal = card.querySelector(".mobile-hpp-val");
    if (hppVal)
        hppVal.textContent =
            "Rp " + Math.round(defaultCost).toLocaleString("id-ID");

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

    // Mobile cards
    if (isMobileView()) {
        subtotal = 0;
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
 * Simpan transaksi dengan status tertentu (DRAFT / CONFIRMED)
 */
function submitRestockAs(status) {
    const statusInput = document.getElementById("status_restock");
    if (statusInput) statusInput.value = status;

    const form = document.getElementById("formCreateRestock");
    if (form) form.submit();
}

/**
 * Buka modal konfirmasi untuk simpan CONFIRMED
 */
function openConfirmRestockModal() {
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
    const statusInput = document.getElementById("status_restock");
    if (statusInput) statusInput.value = "CONFIRMED";

    const form = document.getElementById("formCreateRestock");
    if (form) form.submit();
}
