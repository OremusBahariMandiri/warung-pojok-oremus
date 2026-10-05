/**
 * WARJOK — JavaScript for Stock Opname (Index + Create/Edit)
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

// ════════════════════════════════════════════════════════════════
// INDEX PAGE — DataTables + Mobile Filter
// ════════════════════════════════════════════════════════════════
$(function () {
    // Guard: hanya jalan di index page (ada #opnameDataTable)
    if ($("#opnameDataTable").length && typeof $.fn.DataTable !== "undefined") {
        initOpnameDataTable();
        initOpnameMobileSearch();
    }

    // Alert auto-dismiss
    setTimeout(function () {
        $(".alert-dismissible").fadeOut("slow");
    }, 5000);
});

var opnameDT = null;

function initOpnameDataTable() {
    opnameDT = $("#opnameDataTable").DataTable({
        // dom: l = length dropdown | t = table | i = info | p = pagination
        dom:
            "<'row align-items-center mb-2'<'col-sm-4'l>>" +
            "t" +
            "<'row mt-2'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        responsive: false,
        autoWidth: false,
        order: [[2, "desc"]], // default sort: Tanggal Pemeriksaan desc
        language: {
            lengthMenu: "Tampilkan _MENU_ entri",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(difilter dari _MAX_ total)",
            zeroRecords: `<div class="d-flex flex-column align-items-center justify-content-center py-4 text-center text-muted">
                <i class="bi bi-search fs-3 mb-2 opacity-50"></i>
                <div class="fw-semibold small">Data tidak ditemukan</div>
                <div class="small">Coba gunakan kata kunci atau filter yang berbeda.</div>
            </div>`,
            emptyTable: `<div class="d-flex flex-column align-items-center justify-content-center py-4 text-center text-muted">
                <i class="bi bi-clipboard2-x fs-3 mb-2 opacity-50"></i>
                <div class="fw-semibold small">Belum ada riwayat transaksi Stock Opname</div>
                <div class="small">Klik <strong>+ Catat Stock Opname</strong> untuk memulai pemeriksaan baru.</div>
            </div>`,
            paginate: { first: "«", last: "»", next: "›", previous: "‹" },
        },
        // Kolom No & Aksi tidak bisa di-sort
        columnDefs: [{ targets: [0, 6], orderable: false }],
    });

    // Hubungkan custom search input ke DataTables
    $("#opnameSearchInput").on("keyup input", function () {
        opnameDT.search($(this).val()).draw();
    });

    // Reset search ketika input dikosongkan
    $("#opnameSearchInput").on("search", function () {
        if ($(this).val() === "") {
            opnameDT.search("").draw();
        }
    });
}

// ── Mobile search / filter ────────────────────────────────────
function initOpnameMobileSearch() {
    $("#opnameMobileSearch").on("keyup input", function () {
        var keyword = $(this).val().toLowerCase().trim();
        var hasResult = false;

        $(".opname-mobile-card").each(function () {
            var searchData = $(this).data("search") || "";
            var match = !keyword || searchData.indexOf(keyword) !== -1;
            $(this).toggle(match);
            if (match) hasResult = true;
        });

        var isEmpty = $(".opname-mobile-card").length === 0;
        $("#opnameMobileEmpty").toggle(isEmpty && !keyword);
        $("#opnameMobileNoResult").toggleClass("d-none", isEmpty || hasResult);
    });
}

// ════════════════════════════════════════════════════════════════
// CREATE / EDIT PAGE — Form Logic
// ════════════════════════════════════════════════════════════════
$(document).ready(function () {
    // Guard: hanya jalan di create/edit page (ada form opname)
    if (!$("#opnameItemsTable").length && !$("#formStockOpname").length) return;

    var rowIndex = $("#opnameItemsTable tbody tr.opname-row").length || 0;

    // ── READONLY MODE GUARD ───────────────────────────────────────
    // Deteksi dari data-readonly pada form (di-set oleh blade via $readonly)
    var isReadonly = $("#formStockOpname").data("readonly") === "true";

    if (isReadonly) {
        // Destroy Select2 dan render sebagai teks agar terlihat rapi
        $(".product-select").each(function () {
            if ($(this).hasClass("select2-hidden-accessible")) {
                $(this).select2("destroy");
            }
            var selectedText = $(this).find("option:selected").text();
            $(this)
                .closest("td")
                .find(".product-select")
                .replaceWith(
                    '<span class="fw-semibold small text-dark">' +
                        selectedText +
                        "</span>",
                );
        });

        // Disable semua input stok fisik (backup selain readonly di blade)
        $(".physical-stock-input").prop("readonly", true).addClass("bg-light");

        // Sembunyikan tombol hapus baris (backup selain kondisi blade)
        $(".btn-delete-row").hide();

        // Sembunyikan tombol tambah produk (backup selain kondisi blade)
        $("#btnAddRow").hide();

        // Hitung selisih awal untuk tampilan (tetap perlu meski readonly)
        $("#opnameItemsTable tbody tr.opname-row").each(function () {
            calculateRowDiffReadonly($(this));
        });

        refreshProductOptions();

        // Sync mobile cards dalam mode readonly
        if (window.innerWidth < 768) {
            syncMobileCardsReadonly();
        }

        // Stop — tidak perlu register event listener interaktif
        return;
    }
    // ─────────────────────────────────────────────────────────────

    // ── 1. Initialize Select2 ─────────────────────────────────────
    function initSelect2(element) {
        if ($(element).hasClass("select2-hidden-accessible")) {
            $(element).select2("destroy");
        }
        $(element).select2({
            theme: "bootstrap-5",
            placeholder: "-- Pilih Produk --",
            allowClear: false,
            width: "100%",
        });
    }

    $(".product-select").each(function () {
        initSelect2(this);
    });

    // ── 2. Update nomor urut ──────────────────────────────────────
    function updateRowNumbers() {
        $("#opnameItemsTable tbody tr.opname-row").each(function (idx) {
            $(this)
                .find(".row-number")
                .text(idx + 1);
        });
        $(".opname-item-card").each(function (idx) {
            $(this)
                .find(".card-num-badge")
                .text(idx + 1);
        });
    }

    function getDiffBadgeHtml(diff) {
        if (diff === 0) {
            return '<span class="diff-badge zero"><i class="bi bi-check-circle-fill"></i> 0 (Sesuai)</span>';
        } else if (diff > 0) {
            return (
                '<span class="diff-badge surplus"><i class="bi bi-arrow-up-circle-fill"></i> +' +
                diff +
                " (Lebih)</span>"
            );
        } else {
            return (
                '<span class="diff-badge deficit"><i class="bi bi-arrow-down-circle-fill"></i> ' +
                diff +
                " (Kurang)</span>"
            );
        }
    }

    // ── 3. Hitung selisih satu baris (desktop) ────────────────────
    function calculateRowDiff($row) {
        var $productSelect = $row.find(".product-select");
        var selectedOption = $productSelect.find("option:selected");
        var productId = selectedOption.val();

        if (!productId) {
            $row.find(".unit-cell").text("-");
            $row.find(".system-stock-cell").text("-");
            $row.find(".diff-cell, .diff-badge-container").text("-");
            return;
        }

        var unitName = selectedOption.data("unit") || "-";
        var systemStock = parseInt(selectedOption.data("stock") || 0, 10);
        var physicalStockRaw = $row.find(".physical-stock-input").val();
        var physicalStock =
            physicalStockRaw !== "" ? parseInt(physicalStockRaw, 10) : 0;
        var diff = physicalStock - systemStock;

        $row.find(".unit-cell").text(unitName);
        $row.find(".system-stock-cell").text(systemStock);
        $row.find(".diff-cell, .diff-badge-container").html(
            getDiffBadgeHtml(diff),
        );
    }

    // ── 4. Hitung ringkasan keseluruhan ───────────────────────────
    function calculateSummary() {
        var totalChecked = 0;
        var totalDiscrepancy = 0;
        var totalSurplus = 0;
        var totalDeficit = 0;

        $("#opnameItemsTable tbody tr.opname-row").each(function () {
            var $row = $(this);
            var productId = $row.find(".product-select").val();

            if (productId) {
                totalChecked++;
                var selectedOption = $row.find(
                    ".product-select option:selected",
                );
                var systemStock = parseInt(
                    selectedOption.data("stock") || 0,
                    10,
                );
                var physicalStockRaw = $row.find(".physical-stock-input").val();
                var physicalStock =
                    physicalStockRaw !== ""
                        ? parseInt(physicalStockRaw, 10)
                        : 0;

                var diff = physicalStock - systemStock;
                if (diff !== 0) {
                    totalDiscrepancy++;
                    if (diff > 0) totalSurplus++;
                    else totalDeficit++;
                }
            }
        });

        $("#summaryTotalChecked").text(totalChecked);
        $("#summaryTotalDiscrepancy").text(totalDiscrepancy);
        $("#summaryTotalSurplus").text(totalSurplus);
        $("#summaryTotalDeficit").text(totalDeficit);
    }

    // ── 5. Update satu mobile card dari baris tabel ───────────────
    function updateMobileCard(rowIdx) {
        var $row = $("#opnameItemsTable tbody tr.opname-row").eq(rowIdx);
        var $card = $('.opname-item-card[data-row-idx="' + rowIdx + '"]');
        if (!$card.length) return;

        var $select = $row.find(".product-select");
        var selectedOption = $select.find("option:selected");
        var productId = selectedOption.val();

        if (productId) {
            var productName = selectedOption.text();
            var unitName = selectedOption.data("unit") || "-";
            var systemStock = parseInt(selectedOption.data("stock") || 0, 10);
            var physRaw = $row.find(".physical-stock-input").val();
            var physStock = physRaw !== "" ? parseInt(physRaw, 10) : 0;
            var diff = physStock - systemStock;

            $card.find(".mobile-product-name").text(productName);
            $card.find(".mobile-unit-val").text(unitName);
            $card.find(".mobile-sys-stock-val").text(systemStock);
            $card.find(".mobile-diff-val").html(getDiffBadgeHtml(diff));
        } else {
            $card
                .find(".mobile-product-name")
                .html('<span class="text-muted">-- Belum dipilih --</span>');
            $card.find(".mobile-unit-val").text("-");
            $card.find(".mobile-sys-stock-val").text("-");
            $card.find(".mobile-diff-val").text("-");
        }
    }

    // ── 6. Sync semua mobile cards (rebuild dari tabel) ───────────
    function syncMobileCards() {
        var $container = $("#opnameMobileCards");
        $container.empty();

        $("#opnameItemsTable tbody tr.opname-row").each(function (idx) {
            var $row = $(this);
            var $select = $row.find(".product-select");
            var selectedOption = $select.find("option:selected");
            var productId = selectedOption.val();
            var productName = productId ? selectedOption.text() : "";
            var unitName = productId ? selectedOption.data("unit") || "-" : "-";
            var systemStock = productId
                ? parseInt(selectedOption.data("stock") || 0, 10)
                : "-";
            var physRaw = $row.find(".physical-stock-input").val();
            var physStock = physRaw !== "" ? parseInt(physRaw, 10) : 0;
            var diff = productId ? physStock - systemStock : 0;
            var diffBadgeHtml = productId ? getDiffBadgeHtml(diff) : "-";

            var optionsHtml = "";
            $select.find("option").each(function () {
                var val = $(this).val();
                var text = $(this).text();
                var unit = $(this).data("unit") || "";
                var stock = $(this).data("stock") || 0;
                var selected = val === productId ? " selected" : "";
                optionsHtml +=
                    '<option value="' +
                    val +
                    '" data-unit="' +
                    unit +
                    '" data-stock="' +
                    stock +
                    '"' +
                    selected +
                    ">" +
                    text +
                    "</option>";
            });

            var $card = $(
                '<div class="opname-item-card" data-row-idx="' +
                    idx +
                    '">' +
                    '<div class="card-header-row">' +
                    '<span class="card-num-badge">' +
                    (idx + 1) +
                    "</span>" +
                    '<div class="flex-grow-1 min-w-0">' +
                    '<div class="fw-semibold text-dark small mobile-product-name">' +
                    (productName
                        ? productName
                        : '<span class="text-muted">-- Belum dipilih --</span>') +
                    "</div>" +
                    '<div class="text-muted d-none" style="font-size:0.75rem;">Satuan: <span class="mobile-unit-val">' +
                    (unitName || "-") +
                    "</span></div>" +
                    "</div>" +
                    '<button type="button" class="btn-delete-row ms-2" title="Hapus"><i class="bi bi-trash3"></i></button>' +
                    "</div>" +
                    '<div class="mb-3">' +
                    '<label class="mobile-field-label">Produk <span class="text-danger">*</span></label>' +
                    '<select class="form-select form-select-sm mobile-product-select" data-row-idx="' +
                    idx +
                    '">' +
                    optionsHtml +
                    "</select>" +
                    "</div>" +
                    '<div class="row g-2">' +
                    '<div class="col-6">' +
                    '<label class="mobile-field-label">Stok Sistem</label>' +
                    '<div class="fw-bold mobile-sys-stock-val">' +
                    systemStock +
                    "</div>" +
                    "</div>" +
                    '<div class="col-6">' +
                    '<label class="mobile-field-label">Stok Fisik <span class="text-danger">*</span></label>' +
                    '<input type="number" class="form-control form-control-sm text-center fw-bold mobile-phys-input" ' +
                    'data-row-idx="' +
                    idx +
                    '" value="' +
                    physRaw +
                    '" min="0">' +
                    "</div>" +
                    "</div>" +
                    '<div class="mt-2">' +
                    '<label class="mobile-field-label">Selisih</label>' +
                    '<div class="mobile-diff-val">' +
                    diffBadgeHtml +
                    "</div>" +
                    "</div>" +
                    "</div>",
            );

            $container.append($card);
            initSelect2($card.find(".mobile-product-select"));
        });
    }

    // Hitung awal
    $("#opnameItemsTable tbody tr.opname-row").each(function () {
        calculateRowDiff($(this));
    });
    calculateSummary();

    if (window.innerWidth < 768) {
        syncMobileCards();
    }

    // ── 7. Event: perubahan produk di mobile select ───────────────
    $(document).on("change", ".mobile-product-select", function () {
        var rowIdx = parseInt($(this).data("row-idx"));
        var newVal = $(this).val();
        var $desktopSelect = $("#opnameItemsTable tbody tr.opname-row")
            .eq(rowIdx)
            .find(".product-select");
        $desktopSelect.val(newVal).trigger("change.select2");
        calculateRowDiff($("#opnameItemsTable tbody tr.opname-row").eq(rowIdx));
        calculateSummary();
        refeshProductOptions();
        updateMobileCard(rowIdx);
    });

    // ── 8. Event: perubahan produk di desktop select ──────────────
    $(document).on("change", ".product-select", function () {
        var $row = $(this).closest("tr.opname-row");
        var rowIdx = $row.index();
        calculateRowDiff($row);
        calculateSummary();
        refreshProductOptions();
        if (window.innerWidth < 768) {
            updateMobileCard(rowIdx);
            var selectedOption = $(this).find("option:selected");
            var $card = $('.opname-item-card[data-row-idx="' + rowIdx + '"]');
            if ($card.length && selectedOption.val()) {
                $card.find(".mobile-product-name").text(selectedOption.text());
                $card
                    .find(".mobile-unit-val")
                    .text(selectedOption.data("unit") || "-");
                $card
                    .find(".mobile-product-select")
                    .val(selectedOption.val())
                    .trigger("change.select2");
            }
        }
    });

    // ── 9. Event: stok fisik di desktop ──────────────────────────
    $(document).on("input change", ".physical-stock-input", function () {
        var $row = $(this).closest("tr.opname-row");
        var rowIdx = $row.index();
        calculateRowDiff($row);
        calculateSummary();
        if (window.innerWidth < 768) {
            $('.mobile-phys-input[data-row-idx="' + rowIdx + '"]').val(
                $(this).val(),
            );
            updateMobileCard(rowIdx);
        }
    });

    // ── 10. Event: stok fisik di mobile ──────────────────────────
    $(document).on("input change", ".mobile-phys-input", function () {
        var rowIdx = parseInt($(this).data("row-idx"));
        var newVal = $(this).val();
        $("#opnameItemsTable tbody tr.opname-row")
            .eq(rowIdx)
            .find(".physical-stock-input")
            .val(newVal);
        calculateRowDiff($("#opnameItemsTable tbody tr.opname-row").eq(rowIdx));
        calculateSummary();
        updateMobileCard(rowIdx);
    });

    // ── 11. Tambah baris ──────────────────────────────────────────
    $("#btnAddRow").on("click", function () {
        rowIndex++;
        var template = $("#rowTemplate").html();
        var newRowHtml = template.replace(/__INDEX__/g, rowIndex);
        var $newRow = $(newRowHtml);

        $("#opnameItemsTable tbody").append($newRow);
        initSelect2($newRow.find(".product-select"));
        updateRowNumbers();
        calculateRowDiff($newRow);
        calculateSummary();
        refreshProductOptions();

        if (window.innerWidth < 768) {
            syncMobileCards();
        }
    });

    // ── 12. Hapus baris dari desktop ─────────────────────────────
    $(document).on("click", "#opnameItemsTable .btn-delete-row", function () {
        if ($("#opnameItemsTable tbody tr.opname-row").length <= 1) {
            alert("Minimal satu baris produk harus ada.");
            return;
        }
        var $row = $(this).closest("tr.opname-row");
        $row.find(".product-select").select2("destroy");
        $row.remove();
        updateRowNumbers();
        calculateSummary();
        refreshProductOptions();
        if (window.innerWidth < 768) syncMobileCards();
    });

    // ── 13. Hapus baris dari mobile card ─────────────────────────
    $(document).on("click", "#opnameMobileCards .btn-delete-row", function () {
        if ($("#opnameItemsTable tbody tr.opname-row").length <= 1) {
            alert("Minimal satu baris produk harus ada.");
            return;
        }
        var rowIdx = parseInt(
            $(this).closest(".opname-item-card").data("row-idx"),
        );
        var $tableRow = $("#opnameItemsTable tbody tr.opname-row").eq(rowIdx);
        $tableRow.find(".product-select").select2("destroy");
        $tableRow.remove();
        updateRowNumbers();
        calculateSummary();
        refreshProductOptions();
        syncMobileCards();
    });

    // ── 14. Simpan sebagai Draft ──────────────────────────────────
    $("#btnSaveDraft").on("click", function () {
        $("#status_opname").val("DRAFT");
        var form = document.getElementById("formStockOpname");
        if (form.checkValidity()) {
            form.submit();
        } else {
            form.reportValidity();
        }
    });

    // ── 15. Simpan dan Selesaikan ─────────────────────────────────
    $("#btnOpenConfirmModal").on("click", function () {
        var form = document.getElementById("formStockOpname");
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var validItemCount = 0;
        var discrepancyCount = 0;

        $("#opnameItemsTable tbody tr.opname-row").each(function () {
            var $row = $(this);
            var prodId = $row.find(".product-select").val();
            if (prodId) {
                validItemCount++;
                var sys = parseInt(
                    $row
                        .find(".product-select option:selected")
                        .data("stock") || 0,
                    10,
                );
                var phys = parseInt(
                    $row.find(".physical-stock-input").val() || 0,
                    10,
                );
                if (phys !== sys) discrepancyCount++;
            }
        });

        if (validItemCount === 0) {
            alert(
                "Silakan pilih minimal satu produk untuk dilakukan stock opname.",
            );
            return;
        }

        $("#modalTotalChecked").text(validItemCount);
        $("#modalTotalDiff").text(discrepancyCount);

        var confirmModal = new bootstrap.Modal(
            document.getElementById("modalConfirmComplete"),
        );
        confirmModal.show();
    });

    // ── 16. Konfirmasi submit modal ───────────────────────────────
    $("#btnConfirmCompleteSubmit").on("click", function () {
        $("#status_opname").val("COMPLETED");
        $("#formStockOpname").submit();
    });
});

$("#btnSaveDraftMobile").on("click", function () {
    $("#btnSaveDraft").trigger("click");
});
$("#btnOpenConfirmModalMobile").on("click", function () {
    $("#btnOpenConfirmModal").trigger("click");
});

// ════════════════════════════════════════════════════════════════
// READONLY HELPERS — Hanya dipakai saat mode readonly (COMPLETED)
// ════════════════════════════════════════════════════════════════

/**
 * Hitung & tampilkan selisih satu baris dalam mode readonly.
 * Dipakai saat isReadonly = true karena select sudah disabled,
 * maka data diambil langsung dari system-stock-cell & physical-stock-input.
 */
function calculateRowDiffReadonly($row) {
    var systemStock = parseInt(
        $row.find(".system-stock-cell").text().trim() || 0,
        10,
    );
    var physicalStockRaw = $row.find(".physical-stock-input").val();
    var physicalStock =
        physicalStockRaw !== "" ? parseInt(physicalStockRaw, 10) : 0;

    if (isNaN(systemStock)) return;

    var diff = physicalStock - systemStock;

    var badgeHtml = "";
    if (diff === 0) {
        badgeHtml =
            '<span class="diff-badge zero"><i class="bi bi-check-circle-fill"></i> 0 (Sesuai)</span>';
    } else if (diff > 0) {
        badgeHtml =
            '<span class="diff-badge surplus"><i class="bi bi-arrow-up-circle-fill"></i> +' +
            diff +
            " (Lebih)</span>";
    } else {
        badgeHtml =
            '<span class="diff-badge deficit"><i class="bi bi-arrow-down-circle-fill"></i> ' +
            diff +
            " (Kurang)</span>";
    }

    $row.find(".diff-cell, .diff-badge-container").html(badgeHtml);
}

/**
 * Render mobile cards dalam mode readonly — semua input & select disabled.
 */
function syncMobileCardsReadonly() {
    var $container = $("#opnameMobileCards");
    $container.empty();

    $("#opnameItemsTable tbody tr.opname-row").each(function (idx) {
        var $row = $(this);

        // Ambil nama produk dari hidden input atau teks yang ada
        var productName = $row
            .find("td:nth-child(2) span.fw-semibold")
            .text()
            .trim();
        if (!productName) {
            productName = $row
                .find(".product-select option:selected")
                .text()
                .trim();
        }

        var systemStock = $row.find(".system-stock-cell").text().trim() || "-";
        var physRaw = $row.find(".physical-stock-input").val();
        var physStock = physRaw !== "" ? parseInt(physRaw, 10) : 0;
        var sysInt = parseInt(systemStock, 10);
        var diff = !isNaN(sysInt) ? physStock - sysInt : null;

        var diffBadgeHtml = "-";
        if (diff !== null) {
            if (diff === 0) {
                diffBadgeHtml =
                    '<span class="diff-badge zero"><i class="bi bi-check-circle-fill"></i> 0 (Sesuai)</span>';
            } else if (diff > 0) {
                diffBadgeHtml =
                    '<span class="diff-badge surplus"><i class="bi bi-arrow-up-circle-fill"></i> +' +
                    diff +
                    " (Lebih)</span>";
            } else {
                diffBadgeHtml =
                    '<span class="diff-badge deficit"><i class="bi bi-arrow-down-circle-fill"></i> ' +
                    diff +
                    " (Kurang)</span>";
            }
        }

        var $card = $(
            '<div class="opname-item-card" data-row-idx="' +
                idx +
                '">' +
                '<div class="card-header-row">' +
                '<span class="card-num-badge">' +
                (idx + 1) +
                "</span>" +
                '<div class="flex-grow-1 min-w-0">' +
                '<div class="fw-semibold text-dark small mobile-product-name">' +
                (productName ||
                    '<span class="text-muted">-- Tidak diketahui --</span>') +
                "</div>" +
                "</div>" +
                // Tidak ada tombol hapus di readonly
                "</div>" +
                '<div class="row g-2 mt-2">' +
                '<div class="col-6">' +
                '<label class="mobile-field-label">Stok Sistem</label>' +
                '<div class="fw-bold mobile-sys-stock-val">' +
                systemStock +
                "</div>" +
                "</div>" +
                '<div class="col-6">' +
                '<label class="mobile-field-label">Stok Fisik</label>' +
                '<input type="number" class="form-control form-control-sm text-center fw-bold bg-light" ' +
                'value="' +
                physRaw +
                '" min="0" readonly>' +
                "</div>" +
                "</div>" +
                '<div class="mt-2">' +
                '<label class="mobile-field-label">Selisih</label>' +
                '<div class="mobile-diff-val">' +
                diffBadgeHtml +
                "</div>" +
                "</div>" +
                "</div>",
        );

        $container.append($card);
    });
}

// ════════════════════════════════════════════════════════════════
// ROLLBACK COUNTDOWN — Live timer untuk batas waktu rollback
// ════════════════════════════════════════════════════════════════
(function () {
    // Ambil deadline dari atribut data-deadline pada tag <script> ini sendiri
    var scriptTag = document.currentScript;
    var deadline = scriptTag
        ? parseInt(scriptTag.getAttribute("data-deadline") || 0, 10)
        : 0;

    // Tidak ada deadline atau bukan halaman rollback — stop
    if (!deadline) return;

    var elCountdownBanner = document.getElementById("rollbackCountdown");
    var elCountdownModal = document.getElementById("rollbackCountdownModal");
    var elInfoAvailable = document.getElementById("rollbackInfoAvailable");
    var elInfoExpired = document.getElementById("rollbackInfoExpired");
    var elBtnDesktop = document.getElementById("btnRollbackDesktop");
    var elBtnMobile = document.getElementById("btnRollbackMobile");

    // ── Format sisa waktu ke string ─────────────────────────────
    function formatCountdown(msLeft) {
        var totalSec = Math.floor(msLeft / 1000);
        var h = Math.floor(totalSec / 3600);
        var m = Math.floor((totalSec % 3600) / 60);
        var s = totalSec % 60;
        var parts = [];
        if (h > 0) parts.push(h + " jam");
        if (m > 0 || h > 0) parts.push(m + " menit");
        parts.push(s + " detik");
        return parts.join(" ") + " lagi";
    }

    // ── Nonaktifkan tombol rollback saat waktu habis ─────────────
    function disableRollbackButtons() {
        [elBtnDesktop, elBtnMobile].forEach(function (btn) {
            if (!btn) return;
            btn.disabled = true;
            btn.classList.add("disabled");
            btn.removeAttribute("data-bs-toggle");
            btn.removeAttribute("data-bs-target");
            btn.setAttribute("title", "Batas waktu rollback sudah habis");
        });

        // Banner: sembunyikan info "tersedia", tampilkan info "expired"
        if (elInfoAvailable) elInfoAvailable.style.display = "none";
        if (elInfoExpired) elInfoExpired.style.display = "flex";
    }

    // ── Tick: update tiap detik ──────────────────────────────────
    function tick() {
        var msLeft = deadline - Date.now();

        if (msLeft <= 0) {
            if (elCountdownBanner) elCountdownBanner.textContent = "0 detik";
            if (elCountdownModal) elCountdownModal.textContent = "0 detik";
            disableRollbackButtons();
            clearInterval(timer);
            return;
        }

        var text = formatCountdown(msLeft);
        if (elCountdownBanner) elCountdownBanner.textContent = text;
        if (elCountdownModal) elCountdownModal.textContent = text;
    }

    // Jalankan langsung lalu tiap detik
    tick();
    var timer = setInterval(tick, 1000);
})();

// Disable opsi produk yang sudah dipilih di baris lain ──
function refreshProductOptions() {
    var usedProductIds = [];

    // Kumpulkan semua product_id yang sudah dipilih
    $("#opnameItemsTable tbody tr.opname-row").each(function () {
        var val = $(this).find(".product-select").val();
        if (val) usedProductIds.push(val);
    });

    // Loop tiap baris: disable opsi yang dipakai baris LAIN
    $("#opnameItemsTable tbody tr.opname-row").each(function () {
        var $row = $(this);
        var thisVal = $row.find(".product-select").val();

        $row.find(".product-select option").each(function () {
            var optVal = $(this).val();
            if (!optVal) return; // skip placeholder
            var isUsedByOther =
                usedProductIds.includes(optVal) && optVal !== thisVal;
            $(this).prop("disabled", isUsedByOther);
        });

        // Refresh tampilan Select2 agar perubahan disabled teraplikasi
        if (
            $row.find(".product-select").hasClass("select2-hidden-accessible")
        ) {
            $row.find(".product-select").trigger("change.select2");
        }
    });
}
