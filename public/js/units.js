/**
 * WARJOK — Master Satuan DataTables & CRUD JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

$(document).ready(function () {
    initUnitsDataTable(jQuery);

    setTimeout(function () {
        $(".alert-dismissible").fadeOut("slow");
    }, 5000);
});

function initUnitsDataTable($) {
    /*
     * Tiga zona layar:
     *   mobile  < 768px  : card layout CSS, responsive DT dimatikan
     *   tablet  768–991px: tabel dengan expand row aktif
     *   desktop ≥ 992px  : semua kolom tampil, arrow disembunyikan via CSS
     */
    const screenW = window.innerWidth;
    const isMobile = screenW < 768;
    const isDesktop = screenW >= 992;
    const currentZone = isMobile ? "mobile" : isDesktop ? "desktop" : "tablet";

    const dataTable = $("#unitsDataTable").DataTable({
        responsive: isMobile
            ? false
            : isDesktop
              ? {
                    details: {
                        type: "column",
                        target: 0,
                    },
                }
              : {
                    // tablet (768–991px): expand row aktif
                    details: {
                        type: "inline",
                        target: "tr",
                        renderer:
                            $.fn.dataTable.Responsive.renderer.listHiddenNodes(),
                    },
                },

        columnDefs: [
            { targets: "no-sort", orderable: false },
            { targets: 0, responsivePriority: 1 }, // No — selalu tampil
            { targets: 1, responsivePriority: 2 }, // Nama Satuan — selalu tampil
            { targets: 2, responsivePriority: 6 }, // Singkatan — sembunyikan duluan
            { targets: 3, responsivePriority: 3 }, // Produk Terkait
            { targets: 4, responsivePriority: 7 }, // Tanggal Dibuat — sembunyikan duluan
            { targets: 5, responsivePriority: 1 }, // Aksi — selalu tampil
        ],

        scrollX: false,
        autoWidth: false,

        language: {
            emptyTable:
                "Belum ada master satuan di database. Klik tombol 'Tambah Satuan' untuk membuat baru.",
            zeroRecords: "Tidak ada master satuan yang cocok dengan pencarian",
            info: "Menampilkan _START_–_END_ dari _TOTAL_ data",
            infoEmpty: "Tidak ada data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: { first: "«", previous: "‹", next: "›", last: "»" },
        },
        pagingType: "full_numbers",
        dom: '<"table-responsive-wrapper"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>',
        pageLength: 10,
    });

    // Reinit hanya jika zona berubah
    let resizeTimer;
    $(window).on("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            const w = window.innerWidth;
            const newZone =
                w < 768 ? "mobile" : w >= 992 ? "desktop" : "tablet";
            if (newZone !== currentZone) {
                dataTable.destroy();
                initUnitsDataTable($);
            }
        }, 300);
    });

    // Custom search
    $("#dtSearchInput").on("keyup input", function () {
        dataTable.search(this.value).draw();
    });

    // Modal Filter
    $("#btnApplyFilter").on("click", function () {
        applyFilters();
    });

    $("#btnResetFilter").on("click", function () {
        $("#modalFilterUsage").val("");
        applyFilters();
    });

    function applyFilters() {
        const usage = $("#modalFilterUsage").val();

        if (usage === "used") {
            dataTable.column(3).search("[1-9][0-9]* Produk", true, false);
        } else if (usage === "unused") {
            dataTable.column(3).search("0 Produk", true, false);
        } else {
            dataTable.column(3).search("");
        }

        dataTable.draw();

        if (usage) {
            $("#activeFilterBadge").removeClass("d-none");
        } else {
            $("#activeFilterBadge").addClass("d-none");
        }
    }
}

function openDeleteModal(id, name, productCount) {
    document.getElementById("deleteUnitName").textContent = name;
    document.getElementById("deleteUnitForm").action = "/units/" + id;

    const warningBox = document.getElementById("deleteWarningProductCount");
    const countSpan = document.getElementById("warningProductCount");
    if (productCount > 0) {
        countSpan.textContent = productCount;
        warningBox.classList.remove("d-none");
    } else {
        warningBox.classList.add("d-none");
    }

    const modal = new bootstrap.Modal(
        document.getElementById("modalDeleteUnit"),
    );
    modal.show();
}
