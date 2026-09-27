/**
 * WARJOK — Manajemen Produk DataTables & CRUD JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

$(function () {
    // 1. Inisialisasi DataTables jika ada di halaman index
    if ($("#productsDataTable").length && $.fn.DataTable) {
        initProductsDataTable();
    }

    // 2. Alert auto-dismiss
    setTimeout(function () {
        $(".alert-dismissible").fadeOut("slow");
    }, 5000);

    // 3. Inisialisasi Select2 untuk Satuan Beli & Komponen HPP
    initSelect2Elements();

    // 4. Event listener saat Satuan Beli diubah
    $("#unit_id").on("change", function () {
        handleUnitChange(this);
    });

    // 5. Event listener untuk kalkulasi live HPP & Margin
    $(document).on(
        "input keyup change",
        "#unit_price, #selling_price, #current_hpp",
        function () {
            calculateTotalHpp();
        },
    );

    // 6. Trigger awal saat halaman dimuat (untuk edit form / old input)
    if ($("#unit_id").length) {
        handleUnitChange(document.getElementById("unit_id"));
        calculateTotalHpp();
    }
});

/**
 * Inisialisasi Select2 dengan theme Bootstrap 5
 */
function initSelect2Elements(context) {
    const $ctx = context ? $(context) : $(document);

    $ctx.find(".select2-unit").each(function () {
        if (!$(this).hasClass("select2-hidden-accessible")) {
            $(this).select2({
                theme: "bootstrap-5",
                width: "100%",
                placeholder: "Pilih Satuan...",
                allowClear: false,
            });
        }
    });

    $ctx.find(".select2-hpp").each(function () {
        if (!$(this).hasClass("select2-hidden-accessible")) {
            $(this).select2({
                theme: "bootstrap-5",
                width: "100%",
                placeholder: "Pilih Komponen",
                allowClear: true,
            });
        }
    });
}

/**
 * Handle perubahan Satuan Beli:
 * Jika satuan olahan / barang jadi (Gelas, Cup, Paket, Porsi, Pcs, dll) -> Tampilkan opsi metode HPP.
 * Jika satuan mentah (Sachet, Renceng, Dus, dll) -> Sembunyikan metode HPP & komponen HPP.
 */
function handleUnitChange(selectEl) {
    if (!selectEl || !selectEl.value) return;

    const selectedOption = selectEl.options[selectEl.selectedIndex];
    if (!selectedOption) return;

    const unitName = (
        selectedOption.getAttribute("data-name") ||
        selectedOption.text ||
        ""
    ).toLowerCase();
    const unitShort = (
        selectedOption.getAttribute("data-short") || ""
    ).toLowerCase();
    const unitType = (
        selectedOption.getAttribute("data-type") || ""
    ).toLowerCase();

    // Keyword satuan olahan / barang jadi / pcs
    const processedKeywords = [
        "gelas",
        "cup",
        "paket",
        "porsi",
        "olahan",
        "botol",
        "piring",
        "minuman",
        "makanan",
        "jadi",
        "pcs",
        "biji",
        "buah",
        "butir",
    ];

    const isProcessedOrPcs =
        unitType === "olahan" ||
        unitType === "processed" ||
        unitType === "finish_good" ||
        unitType === "finished" ||
        processedKeywords.some(
            (kw) => unitName.includes(kw) || unitShort.includes(kw),
        );

    const hppMethodSection = document.getElementById("hppMethodSection");
    const calcSection = document.getElementById("calculatedHppSection");
    const manualSection = document.getElementById("manualHppSection");

    if (isProcessedOrPcs) {
        if (hppMethodSection) hppMethodSection.classList.remove("d-none");
        handleHppMethodChange();
    } else {
        if (hppMethodSection) hppMethodSection.classList.add("d-none");
        if (calcSection) calcSection.classList.add("d-none");
        if (manualSection) manualSection.classList.add("d-none");
        const methodSelect = document.getElementById("hpp_method");
        if (methodSelect) methodSelect.value = "MANUAL";
    }

    calculateTotalHpp();
}

/**
 * Handle perubahan Metode HPP (Calculated vs Manual)
 */
function handleHppMethodChange() {
    const methodSelect = document.getElementById("hpp_method");
    const calcSection = document.getElementById("calculatedHppSection");
    const manualSection = document.getElementById("manualHppSection");
    if (!methodSelect) return;

    const method = methodSelect.value;
    if (method === "CALCULATED") {
        if (calcSection) calcSection.classList.remove("d-none");
        if (manualSection) manualSection.classList.add("d-none");
        const tbody = document.getElementById("hppComponentRows");
        if (tbody && tbody.querySelectorAll(".component-row").length === 0) {
            addHppComponentRow();
        } else {
            if (calcSection) initSelect2Elements(calcSection);
        }
    } else {
        if (calcSection) calcSection.classList.add("d-none");
        if (manualSection) manualSection.classList.remove("d-none");
    }

    calculateTotalHpp();
}

/**
 * Dynamic HPP Component Rows Index Counter
 */
let componentRowIndex = document.querySelectorAll(".component-row").length + 20;

/**
 * Tambah Baris Komponen HPP Baru (Menggunakan Select2 & Biaya Readonly)
 */
function addHppComponentRow() {
    const tbody = document.getElementById("hppComponentRows");
    if (!tbody) return;

    const masterList = window.hppMasterList || [];
    const tr = document.createElement("tr");
    tr.className = "component-row";

    let optionsHtml = '<option value="">Pilih Komponen</option>';
    if (masterList.length > 0) {
        masterList.forEach((hpp) => {
            optionsHtml += `<option value="${hpp.id}" data-cost="${hpp.unit_cost}">${hpp.name} (Rp ${Number(hpp.unit_cost).toLocaleString("id-ID")})</option>`;
        });
    }

    tr.innerHTML = `
        <td>
            <select name="components[${componentRowIndex}][hpp_id]" class="form-select form-select-sm select2-hpp rounded-2 component-select" onchange="onComponentSelectChange(this)">
                ${optionsHtml}
            </select>
        </td>
        <td>
            <input type="number" name="components[${componentRowIndex}][cost]" class="form-control form-control-sm font-monospace text-end rounded-2 component-cost bg-light" min="0" readonly>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="removeHppComponentRow(this)" title="Hapus"><i class="bi bi-x-circle-fill"></i></button>
        </td>
    `;

    tbody.appendChild(tr);
    initSelect2Elements(tr);

    // Event binding untuk select2 change
    $(tr)
        .find(".select2-hpp")
        .on("select2:select select2:clear change", function () {
            onComponentSelectChange(this);
        });

    componentRowIndex++;
    calculateTotalHpp();
}

/**
 * Hapus Baris Komponen HPP
 */
function removeHppComponentRow(btn) {
    const row = btn.closest("tr");
    if (row) {
        $(row).find(".select2-hpp").select2("destroy");
        row.remove();
    }
    calculateTotalHpp();
}

/**
 * Auto-fill biaya komponen saat komponen dipilih dari Select2
 */
function onComponentSelectChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const defaultCost = selectedOption
        ? selectedOption.getAttribute("data-cost")
        : 0;
    const row = selectEl.closest("tr");

    if (row && defaultCost !== null && defaultCost !== undefined) {
        const costInput = row.querySelector(".component-cost");
        if (costInput) {
            costInput.value = Math.round(parseFloat(defaultCost) || 0);
        }
    }
    calculateTotalHpp();
}

/**
 * Kalkulasi Total Biaya HPP Komponen, Total HPP Produk, dan Estimasi Live Margin
 */
function calculateTotalHpp() {
    const calcSection = document.getElementById("calculatedHppSection");
    const manualSection = document.getElementById("manualHppSection");
    const methodSelect = document.getElementById("hpp_method");
    const method = methodSelect ? methodSelect.value : "MANUAL";

    let unitPrice = 0;
    const unitPriceInput = document.getElementById("unit_price");
    if (unitPriceInput) {
        unitPrice = parseFloat(unitPriceInput.value) || 0;
    }

    let totalHpp = 0;
    const currentHppInput = document.getElementById("current_hpp");

    if (
        calcSection &&
        !calcSection.classList.contains("d-none") &&
        method === "CALCULATED"
    ) {
        let componentsSum = 0;
        const costInputs = document.querySelectorAll(".component-cost");
        costInputs.forEach((input) => {
            componentsSum += parseFloat(input.value) || 0;
        });

        const displayComponentHpp = document.getElementById(
            "displayComponentHpp",
        );
        if (displayComponentHpp) {
            displayComponentHpp.textContent =
                "Rp " + Math.round(componentsSum).toLocaleString("id-ID");
        }

        totalHpp = unitPrice + componentsSum;
        if (currentHppInput) {
            currentHppInput.value = totalHpp > 0 ? Math.round(totalHpp) : "";
        }
    } else if (manualSection && !manualSection.classList.contains("d-none")) {
        const manualHppVal =
            parseFloat(currentHppInput ? currentHppInput.value : 0) || 0;
        totalHpp = manualHppVal > 0 ? manualHppVal : unitPrice;
    } else {
        totalHpp = unitPrice;
        if (currentHppInput) {
            currentHppInput.value = Math.round(totalHpp);
        }
    }

    const displayTotalHpp = document.getElementById("displayTotalHpp");
    if (displayTotalHpp) {
        displayTotalHpp.textContent =
            "Rp " + Math.round(totalHpp).toLocaleString("id-ID");
    }

    // Hitung Live Margin & Profit
    const sellingPriceInput = document.getElementById("selling_price");
    const sellingPrice =
        parseFloat(sellingPriceInput ? sellingPriceInput.value : 0) || 0;
    const profit = sellingPrice - totalHpp;
    const marginPercent =
        sellingPrice > 0 ? ((profit / sellingPrice) * 100).toFixed(1) : 0;

    const marginPercentEl = document.getElementById("displayMarginPercent");
    if (marginPercentEl) {
        marginPercentEl.textContent = marginPercent + "%";
        if (profit < 0 || marginPercent <= 0) {
            marginPercentEl.className = "h5 fw-bold text-danger mb-0";
        } else if (parseFloat(marginPercent) >= 40) {
            marginPercentEl.className = "h5 fw-bold text-success mb-0";
        } else if (parseFloat(marginPercent) >= 20) {
            marginPercentEl.className = "h5 fw-bold text-warning mb-0";
        } else {
            marginPercentEl.className = "h5 fw-bold text-danger mb-0";
        }
    }

    const profitAmountEl = document.getElementById("displayProfitAmount");
    if (profitAmountEl) {
        profitAmountEl.textContent =
            "Rp " + Math.round(profit).toLocaleString("id-ID");
    }
}

/**
 * Image Thumbnail Preview
 */
function previewThumbnail(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const previewEl = document.getElementById("thumbnailPreview");
            const nameEl = document.getElementById("thumbnailFileName");
            const containerEl = document.getElementById(
                "thumbnailPreviewContainer",
            );

            if (previewEl) previewEl.src = e.target.result;
            if (nameEl) nameEl.textContent = input.files[0].name;
            if (containerEl) containerEl.classList.remove("d-none");
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Modal Delete Product
 */
function openDeleteModal(id, name) {
    const nameEl = document.getElementById("deleteProductName");
    const formEl = document.getElementById("deleteProductForm");
    if (nameEl) nameEl.textContent = name;
    if (formEl) formEl.action = "/products/" + id;

    const modalEl = document.getElementById("modalDeleteProduct");
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

/**
 * Inisialisasi DataTables Products:
 * Kolom 0: No
 * Kolom 1: Kode Produk (prod_code) -> Sebelum Nama Produk!
 * Kolom 2: Nama Produk
 * Kolom 3: Satuan
 * Kolom 4: Harga Jual
 * Kolom 5: HPP Total
 * Kolom 6: Margin %
 * Kolom 7: Stok
 * Kolom 8: Metode HPP
 * Kolom 9: Gambar
 * Kolom 10: Aksi
 */
function initProductsDataTable() {
    const isMobile = window.innerWidth < 768;

    const dataTable = $("#productsDataTable").DataTable({
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

        columnDefs: [
            { targets: "no-sort", orderable: false },
            { targets: 0, responsivePriority: 1 }, // No
            { targets: 1, responsivePriority: 5 }, // Kode Produk
            { targets: 2, responsivePriority: 2 }, // Nama Produk
            { targets: 3, responsivePriority: 6 }, // Satuan
            { targets: 4, responsivePriority: 3 }, // Harga Jual
            { targets: 5, responsivePriority: 4 }, // HPP Total
            { targets: 6, responsivePriority: 4 }, // Margin %
            { targets: 7, responsivePriority: 7 }, // Stok
            { targets: 8, responsivePriority: 11 }, // Metode HPP
            { targets: 9, responsivePriority: 12 }, // Gambar
            { targets: 10, responsivePriority: 1 }, // Aksi
        ],

        scrollX: false,
        autoWidth: false,

        language: {
            emptyTable:
                "Belum ada produk di database. Klik tombol 'Tambah Produk' untuk membuat baru.",
            zeroRecords: "Tidak ada produk yang cocok dengan pencarian",
            info: "Menampilkan _START_–_END_ dari _TOTAL_ data",
            infoEmpty: "Tidak ada data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                first: "«",
                previous: "‹",
                next: "›",
                last: "»",
            },
        },
        pagingType: "full_numbers",
        dom: '<"table-responsive-wrapper"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>',
        pageLength: 10,
    });

    let resizeTimer;
    $(window).on("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            const nowMobile = window.innerWidth < 768;
            if (nowMobile !== isMobile) {
                dataTable.destroy();
                initProductsDataTable();
            }
        }, 300);
    });

    $("#dtSearchInput").on("keyup input", function () {
        dataTable.search(this.value).draw();
    });

    $("#btnApplyFilter").on("click", function () {
        applyFilters();
    });

    $("#btnResetFilter").on("click", function () {
        $("#modalFilterUnit").val("");
        $("#modalFilterStock").val("");
        applyFilters();
    });

    function applyFilters() {
        const unit = $("#modalFilterUnit").val();
        const stockStatus = $("#modalFilterStock").val();

        dataTable.column(3).search(unit ? "^" + unit + "$" : "", true, false);

        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
            (fn) => fn.name !== "productStockFilter",
        );
        if (stockStatus) {
            const productStockFilter = function (settings, data, dataIndex) {
                if (settings.nTable.id !== "productsDataTable") return true;
                const rowNode = dataTable.row(dataIndex).node();
                return $(rowNode).attr("data-stock") === stockStatus;
            };
            $.fn.dataTable.ext.search.push(productStockFilter);
        }

        dataTable.draw();

        if (unit || stockStatus) {
            $("#activeFilterBadge").removeClass("d-none");
        } else {
            $("#activeFilterBadge").addClass("d-none");
        }
    }
}
