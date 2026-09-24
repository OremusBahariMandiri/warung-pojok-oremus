/**
 * WARJOK — Manajemen Produk DataTables & CRUD JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

if (window.jQuery) {
    jQuery(function ($) {
        if ($("#productsDataTable").length && $.fn.DataTable) {
            initProductsDataTable($);
        }
        setTimeout(function () {
            $(".alert-dismissible").fadeOut("slow");
        }, 5000);
    });
}

function initProductsDataTable($) {
    /*
     * Mobile (< 768px): responsive: false → card layout murni via CSS
     * Desktop (≥ 768px): responsive inline child row →
     *   kolom Metode HPP (index 8) & Gambar (index 9) disembunyikan duluan
     */
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
            // Prioritas kolom — makin kecil = makin dipertahankan
            { targets: 0, responsivePriority: 1 }, // No
            { targets: 1, responsivePriority: 2 }, // Nama Produk
            { targets: 2, responsivePriority: 6 }, // SKU
            { targets: 3, responsivePriority: 7 }, // Satuan
            { targets: 4, responsivePriority: 3 }, // Harga Jual
            { targets: 5, responsivePriority: 4 }, // HPP Total
            { targets: 6, responsivePriority: 5 }, // Margin %
            { targets: 7, responsivePriority: 8 }, // Stok
            { targets: 8, responsivePriority: 11 }, // Metode HPP — disembunyikan duluan
            { targets: 9, responsivePriority: 12 }, // Gambar     — disembunyikan duluan
            { targets: 10, responsivePriority: 1 }, // Aksi       — selalu tampil
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

    // Resize handler — reinit jika berubah antara mobile dan desktop
    let resizeTimer;
    $(window).on("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            const nowMobile = window.innerWidth < 768;
            if (nowMobile !== isMobile) {
                dataTable.destroy();
                initProductsDataTable($);
            }
        }, 300);
    });

    // Custom search
    $("#dtSearchInput").on("keyup input", function () {
        dataTable.search(this.value).draw();
    });

    // Modal Filter Apply
    $("#btnApplyFilter").on("click", function () {
        applyFilters();
    });

    // Modal Filter Reset
    $("#btnResetFilter").on("click", function () {
        $("#modalFilterUnit").val("");
        $("#modalFilterHppMethod").val("");
        $("#modalFilterStock").val("");
        applyFilters();
    });

    function applyFilters() {
        const unit = $("#modalFilterUnit").val();
        const hppMethod = $("#modalFilterHppMethod").val();
        const stockStatus = $("#modalFilterStock").val();

        // Satuan — kolom index 3
        dataTable.column(3).search(unit ? "^" + unit + "$" : "", true, false);

        // Metode HPP — kolom index 8
        if (hppMethod === "calculated") {
            dataTable.column(8).search("Otomatis", true, false);
        } else if (hppMethod === "manual") {
            dataTable.column(8).search("Manual", true, false);
        } else {
            dataTable.column(8).search("");
        }

        // Stok — custom filter via data-stock attribute
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

        if (unit || hppMethod || stockStatus) {
            $("#activeFilterBadge").removeClass("d-none");
        } else {
            $("#activeFilterBadge").addClass("d-none");
        }
    }
}

function openDeleteModal(id, name) {
    document.getElementById("deleteProductName").textContent = name;
    document.getElementById("deleteProductForm").action = "/products/" + id;
    const modal = new bootstrap.Modal(
        document.getElementById("modalDeleteProduct"),
    );
    modal.show();
}

// Menentukan index awal agar tidak bentrok dengan row yang lama (old input)
let componentRowIndex = document.querySelectorAll(".component-row").length + 10;

function generateRandomSKU() {
    const rand = Math.floor(1000 + Math.random() * 9000);
    document.getElementById("sku").value = "PRD-WARJOK-" + rand;
}

function toggleHppSection() {
    const method = document.getElementById("hpp_method").value;
    const calcSection = document.getElementById("calculatedHppSection");
    const manualSection = document.getElementById("manualHppSection");

    if (method === "manual") {
        calcSection.classList.add("d-none");
        manualSection.classList.remove("d-none");
    } else {
        calcSection.classList.remove("d-none");
        manualSection.classList.add("d-none");
    }
    calculateTotalHpp();
}

function onComponentSelectChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const defaultCost = selectedOption.getAttribute("data-cost");
    const row = selectEl.closest("tr");

    if (row && defaultCost !== null) {
        const costInput = row.querySelector(".component-cost");
        if (costInput) {
            costInput.value = Math.round(parseFloat(defaultCost) || 0);
        }
    }
    calculateTotalHpp();
}

function addHppComponentRow() {
    const tbody = document.getElementById("hppComponentRows");
    // Ambil data hppMasterList dari window global (yang dilempar dari Blade)
    const masterList = window.hppMasterList || [];

    // Hapus baris "belum ada data" jika ada
    const emptyRow = tbody.querySelector("td[colspan]");
    if (emptyRow) emptyRow.closest("tr").remove();

    const tr = document.createElement("tr");
    tr.className = "component-row";

    // Looping data options
    let optionsHtml = '<option value="">Pilih Komponen</option>';
    if (masterList.length > 0) {
        masterList.forEach((hpp) => {
            optionsHtml += `<option value="${hpp.id}" data-cost="${hpp.unit_cost}">${hpp.name} (Rp ${Number(hpp.unit_cost).toLocaleString("id-ID")})</option>`;
        });
    }

    // Insert HTML
    tr.innerHTML = `
        <td>
            <select name="components[${componentRowIndex}][hpp_id]" class="form-select form-select-sm rounded-2 component-select" onchange="onComponentSelectChange(this)">
                ${optionsHtml}
            </select>
        </td>
        <td>
            <input type="number" name="components[${componentRowIndex}][cost]" class="form-control form-control-sm font-monospace text-end rounded-2 component-cost" value="0" placeholder="0" min="0" oninput="calculateTotalHpp()">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="removeHppComponentRow(this)" title="Hapus">
                <i class="bi bi-x-circle-fill"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    componentRowIndex++;
    calculateTotalHpp();
}

function removeHppComponentRow(btn) {
    const row = btn.closest("tr");
    if (row) row.remove();
    calculateTotalHpp();
}

function calculateTotalHpp() {
    const method = document.getElementById("hpp_method").value;
    const unitPrice =
        parseFloat(document.getElementById("unit_price").value) || 0;
    const sellingPrice =
        parseFloat(document.getElementById("selling_price").value) || 0;

    let totalHpp = 0;

    if (method === "calculated") {
        let componentsSum = 0;
        const costInputs = document.querySelectorAll(".component-cost");
        costInputs.forEach((input) => {
            componentsSum += parseFloat(input.value) || 0;
        });
        totalHpp = unitPrice + componentsSum;
    } else {
        const manualHppInput = document.getElementById("current_hpp");
        totalHpp = manualHppInput
            ? parseFloat(manualHppInput.value) || unitPrice
            : unitPrice;
    }

    const profit = sellingPrice - totalHpp;
    const marginPercent =
        sellingPrice > 0 ? ((profit / sellingPrice) * 100).toFixed(1) : 0.0;

    document.getElementById("displayTotalHpp").textContent =
        "Rp " + Math.round(totalHpp).toLocaleString("id-ID");
    document.getElementById("displayMarginPercent").textContent =
        marginPercent + "%";
    document.getElementById("displayProfitAmount").textContent =
        "Rp " + Math.round(profit).toLocaleString("id-ID");

    // Color coding margin
    const marginEl = document.getElementById("displayMarginPercent");
    if (marginPercent >= 40) {
        marginEl.className = "h5 fw-bold text-success mb-0";
    } else if (marginPercent >= 20) {
        marginEl.className = "h5 fw-bold text-warning mb-0";
    } else {
        marginEl.className = "h5 fw-bold text-danger mb-0";
    }
}

function previewThumbnail(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById("thumbnailPreview").src = e.target.result;
            document.getElementById("thumbnailFileName").textContent =
                input.files[0].name;
            document
                .getElementById("thumbnailPreviewContainer")
                .classList.remove("d-none");
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Inisialisasi saat pertama kali web dimuat
document.addEventListener("DOMContentLoaded", function () {
    calculateTotalHpp();
});
