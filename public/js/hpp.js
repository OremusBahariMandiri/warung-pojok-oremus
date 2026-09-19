/**
 * WARJOK — Master Komponen HPP DataTables & CRUD JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

/* ════════════════════════════════════════════════════════════
   1. SEARCHABLE SELECT (custom, Select2-style)
   Ditaruh PALING ATAS & tidak bergantung pada jQuery/DataTables,
   supaya listener "klik di luar = tutup" selalu terpasang
   walaupun bagian lain file ini error di halaman create/edit.
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

// Cek apakah event terjadi DI DALAM komponen select.
// Pakai composedPath() karena lebih akurat daripada closest():
// elemen yang diklik bisa saja sudah terlepas dari DOM saat handler jalan.
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
    if (!isInsideSearchableSelect(e)) {
        closeAllSearchableSelects();
    }
}

// Expose ke window agar onclick inline bisa memanggil
window.toggleSearchableSelect = toggleSearchableSelect;
window.filterSearchableOptions = filterSearchableOptions;
window.selectSearchableOption = selectSearchableOption;
window.closeAllSearchableSelects = closeAllSearchableSelects;

// Global listeners (guard supaya hanya terpasang sekali)
if (!window.__searchableSelectInit) {
    window.__searchableSelectInit = true;

    // Fase capture (true) => jalan lebih dulu, tidak bisa "diblokir"
    // oleh stopPropagation() di elemen lain (sidebar, card, layout, dll).
    ["pointerdown", "mousedown", "touchstart", "click", "focusin"].forEach(
        (eventType) => {
            document.addEventListener(eventType, handleOutsideInteraction, {
                capture: true,
                passive: true,
            });
        },
    );

    // Tutup juga saat window kehilangan fokus (pindah tab / klik iframe)
    window.addEventListener("blur", closeAllSearchableSelects);

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeAllSearchableSelects();
        }
    });
}

/* ════════════════════════════════════════════════════════════
   2. HALAMAN INDEX: DataTables, filter, alert
   Semua dibungkus guard supaya aman dipakai di halaman create/edit
   (yang tidak punya #hppDataTable / plugin DataTables).
   ════════════════════════════════════════════════════════════ */
if (window.jQuery) {
    jQuery(function ($) {
        if ($("#hppDataTable").length && $.fn.DataTable) {
            initHppDataTable($);
        }

        // Auto dismiss alert after 5s
        setTimeout(function () {
            $(".alert-dismissible").fadeOut("slow");
        }, 5000);
    });
}

function initHppDataTable($) {
    // Initialize DataTables with dedicated table-responsive wrapper
    const dataTable = $("#hppDataTable").DataTable({
        responsive: false,
        columnDefs: [{ targets: "no-sort", orderable: false }],
        language: {
            emptyTable:
                "Belum ada master komponen HPP di database. Klik tombol 'Tambah Komponen' untuk membuat baru.",
            zeroRecords: "Tidak ada komponen HPP yang cocok dengan pencarian",
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
        $("#modalFilterUnit").val("");
        $("#modalFilterUsage").val("");
        applyFilters();
    });

    function applyFilters() {
        const unit = $("#modalFilterUnit").val();
        const usage = $("#modalFilterUsage").val();

        // Satuan Filter (col index 2: Satuan Pemakaian)
        dataTable.column(2).search(unit ? "^" + unit + "$" : "", true, false);

        // Usage Filter (col index 4: Produk Terkait)
        if (usage === "used") {
            dataTable.column(4).search("[1-9][0-9]* Produk", true, false);
        } else if (usage === "unused") {
            dataTable.column(4).search("0 Produk", true, false);
        } else {
            dataTable.column(4).search("");
        }

        dataTable.draw();

        // Active filter badge indicator
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
