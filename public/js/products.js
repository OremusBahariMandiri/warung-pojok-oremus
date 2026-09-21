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
