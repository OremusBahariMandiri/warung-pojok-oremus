/**
 * WARJOK — JavaScript for Stock Opname Create / Edit Form
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

$(document).ready(function () {
    var rowIndex = $("#opnameItemsTable tbody tr.opname-row").length || 0;

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

    // ── 3. Hitung selisih satu baris (desktop) ────────────────────
    function calculateRowDiff($row) {
        var $productSelect = $row.find(".product-select");
        var selectedOption = $productSelect.find("option:selected");
        var productId = selectedOption.val();

        if (!productId) {
            $row.find(".unit-cell").text("-");
            $row.find(".system-stock-cell").text("-");
            $row.find(".diff-cell").text("-");
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

        // Tampilkan selisih sebagai angka biasa
        var diffText = diff === 0 ? "0" : diff > 0 ? "+" + diff : String(diff);
        var diffClass =
            diff === 0
                ? "text-secondary"
                : diff > 0
                  ? "text-success fw-bold"
                  : "text-danger fw-bold";
        $row.find(".diff-cell").html(
            '<span class="' + diffClass + '">' + diffText + "</span>",
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
            var diffText =
                diff === 0 ? "0" : diff > 0 ? "+" + diff : String(diff);
            var diffClass =
                diff === 0
                    ? "text-secondary"
                    : diff > 0
                      ? "text-success fw-bold"
                      : "text-danger fw-bold";

            $card.find(".mobile-product-name").text(productName);
            $card.find(".mobile-unit-val").text(unitName);
            $card.find(".mobile-sys-stock-val").text(systemStock);
            $card
                .find(".mobile-diff-val")
                .html(
                    '<span class="' + diffClass + '">' + diffText + "</span>",
                );
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
            var diffText =
                diff === 0 ? "0" : diff > 0 ? "+" + diff : String(diff);
            var diffClass =
                diff === 0
                    ? "text-secondary"
                    : diff > 0
                      ? "text-success fw-bold"
                      : "text-danger fw-bold";

            // Bangun opsi untuk select di mobile — clone dari select desktop
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
                    '<div class="text-muted" style="font-size:0.75rem;">Satuan: <span class="mobile-unit-val">' +
                    (unitName || "-") +
                    "</span></div>" +
                    "</div>" +
                    '<button type="button" class="btn-delete-row ms-2" title="Hapus"><i class="bi bi-trash3"></i></button>' +
                    "</div>" +
                    "<!-- Dropdown produk di mobile -->" +
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
                    '<div class="mobile-diff-val"><span class="' +
                    diffClass +
                    '">' +
                    diffText +
                    "</span></div>" +
                    "</div>" +
                    "</div>",
            );

            $container.append($card);

            // Init Select2 pada dropdown mobile
            initSelect2($card.find(".mobile-product-select"));
        });
    }

    // Hitung awal
    $("#opnameItemsTable tbody tr.opname-row").each(function () {
        calculateRowDiff($(this));
    });
    calculateSummary();

    // Init mobile cards
    if (window.innerWidth < 768) {
        syncMobileCards();
    }

    // ── 7. Event: perubahan produk di mobile select ───────────────
    $(document).on("change", ".mobile-product-select", function () {
        var rowIdx = parseInt($(this).data("row-idx"));
        var newVal = $(this).val();

        // Sync ke select desktop (tanpa memicu event ini lagi)
        var $desktopSelect = $("#opnameItemsTable tbody tr.opname-row")
            .eq(rowIdx)
            .find(".product-select");
        $desktopSelect.val(newVal).trigger("change.select2");

        // Hitung selisih baris desktop
        calculateRowDiff($("#opnameItemsTable tbody tr.opname-row").eq(rowIdx));
        calculateSummary();
        updateMobileCard(rowIdx);
    });

    // ── 8. Event: perubahan produk di desktop select ──────────────
    $(document).on("change", ".product-select", function () {
        var $row = $(this).closest("tr.opname-row");
        var rowIdx = $row.index();
        calculateRowDiff($row);
        calculateSummary();
        if (window.innerWidth < 768) {
            updateMobileCard(rowIdx);
            // Update juga nama produk & unit di card header
            var selectedOption = $(this).find("option:selected");
            var $card = $('.opname-item-card[data-row-idx="' + rowIdx + '"]');
            if ($card.length && selectedOption.val()) {
                $card.find(".mobile-product-name").text(selectedOption.text());
                $card
                    .find(".mobile-unit-val")
                    .text(selectedOption.data("unit") || "-");
                // Sync mobile select val
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
            // Sync ke mobile input
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
        // Sync ke input desktop (tanpa re-trigger syncMobileCards)
        $("#opnameItemsTable tbody tr.opname-row")
            .eq(rowIdx)
            .find(".physical-stock-input")
            .val(newVal);
        // Hitung ulang
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
