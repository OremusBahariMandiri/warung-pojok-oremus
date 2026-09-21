/**
 * WARJOK — Master Komponen HPP DataTables & CRUD JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

/* ════════════════════════════════════════════════════════════
   1. SEARCHABLE SELECT
   ════════════════════════════════════════════════════════════ */

function toggleSearchableSelect(triggerEl, e) {
    if (e) e.stopPropagation();
    const wrapper = triggerEl.closest(".searchable-select-wrapper");
    if (!wrapper) return;
    const dropdown = wrapper.querySelector(".searchable-select-dropdown");
    const searchInput = wrapper.querySelector(".searchable-select-search");
    if (!dropdown) return;

    const isOpen = dropdown.classList.contains("show");
    closeAllSearchableSelects();

    if (!isOpen) {
        dropdown.classList.add("show");
        triggerEl.classList.add("active");
        if (searchInput) {
            searchInput.value = "";
            filterSearchableOptions(searchInput);
            setTimeout(() => searchInput.focus(), 50);
        }
    }
}

function filterSearchableOptions(inputEl) {
    const wrapper = inputEl.closest(".searchable-select-wrapper");
    if (!wrapper) return;
    const q = inputEl.value.toLowerCase().trim();
    const options = wrapper.querySelectorAll(".searchable-select-option");
    const optionsContainer = wrapper.querySelector(
        ".searchable-select-options",
    );
    let hasResult = false;

    options.forEach((opt) => {
        const label = (opt.dataset.label || opt.textContent).toLowerCase();
        const match = label.includes(q);
        opt.style.display = match ? "" : "none";
        if (match) hasResult = true;
    });

    let noResult = optionsContainer.querySelector(
        ".searchable-select-no-results",
    );
    if (!hasResult) {
        if (!noResult) {
            noResult = document.createElement("div");
            noResult.className = "searchable-select-no-results";
            noResult.textContent = "Satuan tidak ditemukan";
            optionsContainer.appendChild(noResult);
        }
        noResult.style.display = "";
    } else if (noResult) {
        noResult.style.display = "none";
    }
}

function selectSearchableOption(optionEl) {
    const wrapper = optionEl.closest(".searchable-select-wrapper");
    if (!wrapper) return;
    const val = optionEl.dataset.value;
    const label = optionEl.dataset.label || optionEl.textContent;
    const hiddenSel =
        wrapper.parentElement.querySelector("select") ||
        document.getElementById("unit");
    const trigger = wrapper.querySelector(".searchable-select-trigger");
    const dropdown = wrapper.querySelector(".searchable-select-dropdown");

    if (hiddenSel) {
        hiddenSel.value = val;
        hiddenSel.dispatchEvent(new Event("change"));
    }

    if (trigger) {
        trigger.innerHTML = '<span class="selected-text">' + label + "</span>";
        trigger.classList.remove("active", "is-invalid");
    }

    const options = wrapper.querySelectorAll(".searchable-select-option");
    options.forEach((o) => o.classList.remove("selected"));
    optionEl.classList.add("selected");

    if (dropdown) dropdown.classList.remove("show");
}

function closeAllSearchableSelects() {
    document
        .querySelectorAll(".searchable-select-dropdown.show")
        .forEach((d) => d.classList.remove("show"));
    document
        .querySelectorAll(".searchable-select-trigger.active")
        .forEach((t) => t.classList.remove("active"));
}

function isInsideSearchableSelect(e) {
    const path = typeof e.composedPath === "function" ? e.composedPath() : [];
    if (path.length) {
        return path.some(
            (node) =>
                node &&
                node.classList &&
                node.classList.contains("searchable-select-wrapper"),
        );
    }
    const t = e.target;
    return !!(t && t.closest && t.closest(".searchable-select-wrapper"));
}

function handleOutsideInteraction(e) {
    if (!isInsideSearchableSelect(e)) closeAllSearchableSelects();
}

window.toggleSearchableSelect = toggleSearchableSelect;
window.filterSearchableOptions = filterSearchableOptions;
window.selectSearchableOption = selectSearchableOption;
window.closeAllSearchableSelects = closeAllSearchableSelects;

if (!window.__searchableSelectInit) {
    window.__searchableSelectInit = true;
    ["pointerdown", "mousedown", "touchstart", "click", "focusin"].forEach(
        (ev) =>
            document.addEventListener(ev, handleOutsideInteraction, {
                capture: true,
                passive: true,
            }),
    );
    window.addEventListener("blur", closeAllSearchableSelects);
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeAllSearchableSelects();
    });
}

/* ════════════════════════════════════════════════════════════
   2. DATATABLES
   ════════════════════════════════════════════════════════════ */
if (window.jQuery) {
    jQuery(function ($) {
        if ($("#hppDataTable").length && $.fn.DataTable) {
            initHppDataTable($);
        }
        setTimeout(function () {
            $(".alert-dismissible").fadeOut("slow");
        }, 5000);
    });
}

function initHppDataTable($) {
    /*
     * Deteksi apakah layar saat ini adalah mobile (< 768px).
     * Responsive child row HANYA aktif di desktop/tablet (>= 768px).
     * Di mobile, card layout CSS yang bekerja — DataTables tidak perlu
     * menyembunyikan kolom maupun membuat child row.
     */
    const isMobile = window.innerWidth < 768;

    const dataTable = $("#hppDataTable").DataTable({
        responsive: isMobile
            ? false // ← mobile: matikan responsive DataTables sepenuhnya
            : {
                  details: {
                      type: "inline", // child row di bawah baris, bukan modal
                      target: "tr", // klik seluruh baris untuk expand
                      renderer:
                          $.fn.dataTable.Responsive.renderer.listHiddenNodes(),
                  },
              },

        columnDefs: [
            { targets: "no-sort", orderable: false },
            // Prioritas kolom (makin kecil = makin dipertahankan saat layar sempit)
            // Hanya berlaku di desktop karena mobile responsive: false
            { targets: 0, responsivePriority: 1 }, // No
            { targets: 1, responsivePriority: 2 }, // Nama
            { targets: 2, responsivePriority: 4 }, // Satuan
            { targets: 3, responsivePriority: 3 }, // Unit Cost
            { targets: 4, responsivePriority: 5 }, // Produk
            { targets: 5, responsivePriority: 10 }, // Dibuat — disembunyikan duluan
            { targets: 6, responsivePriority: 1 }, // Aksi — selalu tampil
        ],

        scrollX: false,
        autoWidth: false,

        language: {
            emptyTable:
                "Belum ada master komponen HPP di database. Klik tombol 'Tambah Komponen' untuk membuat baru.",
            zeroRecords: "Tidak ada komponen HPP yang cocok dengan pencarian",
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

    // Jika ukuran window berubah (rotate device, resize browser),
    // reinit agar mode responsive menyesuaikan
    let resizeTimer;
    $(window).on("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            const nowMobile = window.innerWidth < 768;
            // Hanya reinit jika mode berubah (mobile <-> desktop)
            if (nowMobile !== isMobile) {
                dataTable.destroy();
                initHppDataTable($);
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
        $("#modalFilterUnit").val("");
        $("#modalFilterUsage").val("");
        applyFilters();
    });

    function applyFilters() {
        const unit = $("#modalFilterUnit").val();
        const usage = $("#modalFilterUsage").val();

        dataTable.column(2).search(unit ? unit : "", false, false);

        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
            (fn) => fn.name !== "hppUsageFilter",
        );

        if (usage === "used") {
            function hppUsageFilter(settings, data) {
                if (settings.nTable.id !== "hppDataTable") return true;
                const count = parseInt(data[4] || "");
                return !isNaN(count) && count > 0;
            }
            $.fn.dataTable.ext.search.push(hppUsageFilter);
        } else if (usage === "unused") {
            function hppUsageFilter(settings, data) {
                if (settings.nTable.id !== "hppDataTable") return true;
                const count = parseInt(data[4] || "");
                return !isNaN(count) && count === 0;
            }
            $.fn.dataTable.ext.search.push(hppUsageFilter);
        }

        dataTable.draw();

        if (unit || usage) {
            $("#activeFilterBadge").removeClass("d-none");
        } else {
            $("#activeFilterBadge").addClass("d-none");
        }
    }
}

function openDeleteModal(id, name, productCount) {
    document.getElementById("deleteHppName").textContent = name;
    document.getElementById("deleteHppForm").action = "/hpp/" + id;

    const warningBox = document.getElementById("deleteWarningProductCount");
    const countSpan = document.getElementById("warningProductCount");
    if (productCount > 0) {
        countSpan.textContent = productCount;
        warningBox.classList.remove("d-none");
    } else {
        warningBox.classList.add("d-none");
    }

    const modal = new bootstrap.Modal(
        document.getElementById("modalDeleteHpp"),
    );
    modal.show();
}
