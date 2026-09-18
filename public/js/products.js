/**
 * WARJOK — Manajemen Produk & HPP DataTables & CRUD JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */
$(document).ready(function () {
    // Initialize DataTables with dedicated table-responsive wrapper
    const dataTable = $('#productsDataTable').DataTable({
        responsive: false,
        columnDefs: [
            { targets: 'no-sort', orderable: false }
        ],
        language: {
            emptyTable: "Belum ada produk di database. Klik tombol 'Tambah Produk' untuk membuat baru.",
            zeroRecords: "Tidak ada produk yang cocok dengan pencarian",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "«",
                previous: "‹",
                next: "›",
                last: "»"
            }
        },
        pagingType: "full_numbers",
        dom: '<"table-responsive"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 border-top gap-2 bg-white"ip>',
        pageLength: 10
    });

    // Custom Search Input binding
    $('#dtSearchInput').on('keyup input', function () {
        dataTable.search(this.value).draw();
    });

    // Modal Filter Action: Apply
    $('#btnApplyFilter').on('click', function () {
        applyFilters();
    });

    // Modal Filter Action: Reset
    $('#btnResetFilter').on('click', function () {
        $('#modalFilterUnit').val('');
        $('#modalFilterHppMethod').val('');
        $('#modalFilterStock').val('');
        applyFilters();
    });

    function applyFilters() {
        const unit = $('#modalFilterUnit').val();
        const hppMethod = $('#modalFilterHppMethod').val();
        const stockStatus = $('#modalFilterStock').val();

        // Satuan Filter (col index 2: Satuan)
        dataTable.column(2).search(unit ? '^' + unit + '$' : '', true, false);

        // HPP Method Filter (col index 7: Metode HPP)
        if (hppMethod === 'calculated') {
            dataTable.column(7).search('Otomatis', true, false);
        } else if (hppMethod === 'manual') {
            dataTable.column(7).search('Manual', true, false);
        } else {
            dataTable.column(7).search('');
        }

        // Custom search extension for Stock status
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(fn => fn.name !== 'productStockFilter');
        if (stockStatus) {
            const productStockFilter = function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'productsDataTable') return true;
                const rowNode = dataTable.row(dataIndex).node();
                const rowStock = $(rowNode).attr('data-stock');
                return rowStock === stockStatus;
            };
            $.fn.dataTable.ext.search.push(productStockFilter);
        }

        dataTable.draw();

        // Active filter badge indicator
        if (unit || hppMethod || stockStatus) {
            $('#activeFilterBadge').removeClass('d-none');
        } else {
            $('#activeFilterBadge').addClass('d-none');
        }
    }

    // Auto dismiss alert after 5s
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);
});

function openDeleteModal(id, name) {
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('deleteProductForm').action = "/products/" + id;
    const modal = new bootstrap.Modal(document.getElementById('modalDeleteProduct'));
    modal.show();
}
