/**
 * WARJOK — Master Satuan DataTables & CRUD JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */
$(document).ready(function () {
    // Initialize DataTables
    // Wraps ONLY the <table> in .table-responsive so horizontal scroll affects table only, not pagination or toolbar
    const dataTable = $("#unitsDataTable").DataTable({
        responsive: false,
        columnDefs: [{ targets: "no-sort", orderable: false }],
        language: {
            emptyTable:
                "Belum ada master satuan di database. Klik tombol 'Tambah Satuan' untuk membuat baru.",
            zeroRecords: "Tidak ada master satuan yang cocok dengan pencarian",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "«",
                previous: "‹",
                next: "›",
                last: "»",
            },
        },
        pagingType: "full_numbers",
        dom: '<"table-responsive"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>',
        pageLength: 10,
    });

    // Custom Search Input binding
    $("#dtSearchInput").on("keyup input", function () {
        dataTable.search(this.value).draw();
    });

    // Modal Filter Action: Apply
    $("#btnApplyFilter").on("click", function () {
        applyFilters();
    });

    // Modal Filter Action: Reset
    $("#btnResetFilter").on("click", function () {
        $("#modalFilterCategory").val("");
        $("#modalFilterUsage").val("");
        applyFilters();
    });

    function applyFilters() {
        const category = $("#modalFilterCategory").val();
        const usage = $("#modalFilterUsage").val();

        // Category Filter (col index 3)
        dataTable
            .column(3)
            .search(category ? "^" + category + "$" : "", true, false);

        // Usage Filter (col index 4)
        if (usage === "used") {
            dataTable.column(4).search("[1-9][0-9]* Produk", true, false);
        } else if (usage === "unused") {
            dataTable.column(4).search("0 Produk", true, false);
        } else {
            dataTable.column(4).search("");
        }

        dataTable.draw();

        // Active filter badge indicator
        if (category || usage) {
            $("#activeFilterBadge").removeClass("d-none");
        } else {
            $("#activeFilterBadge").addClass("d-none");
        }
    }

    // Auto dismiss alert after 5s
    setTimeout(function () {
        $(".alert-dismissible").fadeOut("slow");
    }, 5000);
});

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
