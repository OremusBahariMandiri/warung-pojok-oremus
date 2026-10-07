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

    // 3. Event listener saat Harga Bahan Utama (#unit_price) diubah di Card 1
    $(document).on("input keyup change", "#unit_price", function () {
        // Jangan sync jika mode fleksibel aktif (unit_price selalu 0)
        if (!_isFlexibleActive()) {
            syncUnitPriceToManualCards();
            calculateAllCardsHpp();
        }
    });

    // 4. Inisialisasi Selling Config Cards jika berada di Halaman Create / Edit
    if ($("#sellingConfigsContainer").length) {
        initSellingConfigs();
    }
});

let configCardIndex = 0;

/* ════════════════════════════════════════════════════════════
   FLEXIBLE PRODUCT HELPERS
   ════════════════════════════════════════════════════════════ */

/** Cek apakah toggle produk fleksibel sedang aktif */
function _isFlexibleActive() {
    const cb = document.getElementById("is_flexible_product");
    return cb ? cb.checked : false;
}

/**
 * Terapkan atau cabut state fleksibel ke satu kartu konfigurasi satuan jual.
 *
 * Ketika fleksibel ON:
 *   - Sembunyikan "Jenis HPP" & "Harga Jual"
 *   - Sembunyikan section HPP (manual maupun calculated)
 *   - Set current-hpp-input → 0
 *   - Hanya "Satuan Jual" yang terlihat
 * Ketika fleksibel OFF:
 *   - Tampilkan kembali semua sesuai metode HPP yang dipilih
 */
function _applyFlexibleStateToCard(card, isFlexible) {
    if (!card) return;

    /* ── Elemen yang dikelola ── */
    const hppMethodCol = card
        .querySelector(".hpp-method-select")
        ?.closest(".col-md-6");
    const manualSection = card.querySelector(".manual-hpp-section");
    const calcSection = card.querySelector(".calculated-hpp-section");
    const sellingPriceTop = card.querySelector(".selling-price-top");
    const sellingPriceBot = card.querySelector(".selling-price-bottom");
    const calcPreview = card
        .querySelector(".calc-preview-card")
        ?.closest(".col-12");
    const currentHppInput = card.querySelector(".current-hpp-input");

    if (isFlexible) {
        /* ── Sembunyikan kolom Jenis HPP ── */
        if (hppMethodCol) hppMethodCol.classList.add("d-none");

        /* ── Sembunyikan section komponen CALCULATED saja;
               HPP Fixed (manual) tetap tampil dengan nilai 0 ── */
        if (calcSection) calcSection.classList.add("d-none");
        if (manualSection) manualSection.classList.remove("d-none");

        /* ── Sembunyikan Harga Jual (top & bottom) ── */
        if (sellingPriceTop) {
            sellingPriceTop.classList.add("d-none");
            const input = sellingPriceTop.querySelector("input");
            if (input) {
                input.removeAttribute("required");
                input.setAttribute("disabled", "disabled");
            }
        }
        if (sellingPriceBot) {
            sellingPriceBot.classList.add("d-none");
            const input = sellingPriceBot.querySelector("input");
            if (input) {
                input.removeAttribute("required");
                input.setAttribute("disabled", "disabled");
            }
        }

        /* ── Sembunyikan preview kalkulasi margin ── */
        if (calcPreview) calcPreview.classList.add("d-none");

        /* ── Set HPP fixed = 0 (unit_price sudah di-reset ke 0) ── */
        if (currentHppInput) {
            const unitPriceVal =
                parseFloat(document.getElementById("unit_price")?.value) || 0;
            currentHppInput.value =
                unitPriceVal > 0 ? Math.round(unitPriceVal) : 0;
        }

        /* ── Visual border + badge info ── */
        card.classList.add("border-warning");
        if (!card.querySelector(".flexible-badge")) {
            const badge = document.createElement("div");
            badge.className =
                "flexible-badge alert alert-warning py-1 px-2 mb-2 small rounded-2 d-flex align-items-center gap-2";
            badge.innerHTML =
                '<i class="bi bi-info-circle-fill text-warning flex-shrink-0"></i>' +
                "<span>Mode Fleksibel — pilih satuan jual saja. Harga & HPP diisi manual saat pencatatan penjualan.</span>";
            card.insertBefore(badge, card.firstChild);
        }
    } else {
        /* ── Restore Jenis HPP ── */
        if (hppMethodCol) hppMethodCol.classList.remove("d-none");

        /* ── Restore section & harga jual sesuai metode yang dipilih ── */
        const method =
            card.querySelector(".hpp-method-select")?.value || "MANUAL";

        if (method === "CALCULATED") {
            if (manualSection) manualSection.classList.add("d-none");
            if (calcSection) calcSection.classList.remove("d-none");
            if (sellingPriceTop) {
                sellingPriceTop.classList.add("d-none");
                const input = sellingPriceTop.querySelector("input");
                if (input) {
                    input.removeAttribute("required");
                    input.setAttribute("disabled", "disabled");
                }
            }
            if (sellingPriceBot) {
                sellingPriceBot.classList.remove("d-none");
                const input = sellingPriceBot.querySelector("input");
                if (input) {
                    input.setAttribute("required", "required");
                    input.removeAttribute("disabled");
                }
            }
        } else {
            if (manualSection) manualSection.classList.remove("d-none");
            if (calcSection) calcSection.classList.add("d-none");
            if (sellingPriceTop) {
                sellingPriceTop.classList.remove("d-none");
                const input = sellingPriceTop.querySelector("input");
                if (input) {
                    input.setAttribute("required", "required");
                    input.removeAttribute("disabled");
                }
            }
            if (sellingPriceBot) {
                sellingPriceBot.classList.add("d-none");
                const input = sellingPriceBot.querySelector("input");
                if (input) {
                    input.removeAttribute("required");
                    input.setAttribute("disabled", "disabled");
                }
            }
        }

        /* ── Tampilkan kembali preview ── */
        if (calcPreview) calcPreview.classList.remove("d-none");

        /* ── Sync HPP dari unit_price ── */
        const unitPriceVal =
            parseFloat(document.getElementById("unit_price")?.value) || 0;
        if (currentHppInput && method === "MANUAL") {
            currentHppInput.value =
                unitPriceVal > 0 ? Math.round(unitPriceVal) : 0;
        }

        /* ── Hapus badge & border ── */
        card.classList.remove("border-warning");
        const badge = card.querySelector(".flexible-badge");
        if (badge) badge.remove();
    }

    calculateCardTotalHpp(card);
}

/**
 * Handle toggle Produk Fleksibel
 * ON  : sembunyikan Jenis HPP, Harga Jual, HPP Fixed; set unit_price = 0; only Satuan Jual visible
 * OFF : restore semua tampilan sesuai metode HPP
 */
function onFlexibleProductToggle(checkbox) {
    const isFlexible = checkbox.checked;

    /* ── Alert info di atas container ── */
    const alertEl = document.getElementById("flexibleProductAlert");
    if (alertEl) {
        alertEl.style.setProperty(
            "display",
            isFlexible ? "flex" : "none",
            "important",
        );
    }

    /* ── Set Harga Bahan Utama = 0 saat fleksibel aktif ── */
    const unitPriceInput = document.getElementById("unit_price");
    if (unitPriceInput) {
        if (isFlexible) {
            unitPriceInput.value = 0;
            unitPriceInput.setAttribute("readonly", "readonly");
            unitPriceInput.classList.add("bg-light", "text-muted");
        } else {
            unitPriceInput.removeAttribute("readonly");
            unitPriceInput.classList.remove("bg-light", "text-muted");
        }
    }

    /* ── Terapkan state ke seluruh kartu ── */
    document.querySelectorAll(".selling-config-card").forEach((card) => {
        _applyFlexibleStateToCard(card, isFlexible);
    });

    /* ── Jika OFF, sync ulang unit_price ke kartu MANUAL ── */
    if (!isFlexible) {
        syncUnitPriceToManualCards();
        calculateAllCardsHpp();
    }
}

/* ════════════════════════════════════════════════════════════
   INIT SELLING CONFIGS
   ════════════════════════════════════════════════════════════ */

/**
 * Inisialisasi kartu-kartu Satuan Jual (Selling Config Cards) pada halaman Form
 */
function initSellingConfigs() {
    const container = document.getElementById("sellingConfigsContainer");
    if (!container) return;

    container.innerHTML = "";
    const oldConfigs = window.oldSellingConfigs || [];

    if (Array.isArray(oldConfigs) && oldConfigs.length > 0) {
        oldConfigs.forEach((configData) => {
            addSellingConfigCard(configData);
        });
    } else {
        addSellingConfigCard();
    }

    syncUnitPriceToManualCards();
    calculateAllCardsHpp();

    /* ── Terapkan state fleksibel jika toggle sudah ON (misalnya halaman edit) ── */
    const flexCheckbox = document.getElementById("is_flexible_product");
    if (flexCheckbox && flexCheckbox.checked) {
        document.querySelectorAll(".selling-config-card").forEach((card) => {
            _applyFlexibleStateToCard(card, true);
        });
        const alertEl = document.getElementById("flexibleProductAlert");
        if (alertEl) alertEl.style.setProperty("display", "flex", "important");
    }
}

/* ════════════════════════════════════════════════════════════
   ADD SELLING CONFIG CARD
   ════════════════════════════════════════════════════════════ */

/**
 * Tambah Kartu Konfigurasi Satuan Jual Baru (Card Repeater)
 */
function addSellingConfigCard(configData = null) {
    const container = document.getElementById("sellingConfigsContainer");
    if (!container) return;

    const cardIdx = configCardIndex++;
    const card = document.createElement("div");
    card.className =
        "card border rounded-3 p-3 bg-white selling-config-card shadow-sm";
    card.setAttribute("data-card-idx", cardIdx);

    const unitsList = window.unitsList || [];
    let unitOptionsHtml =
        '<option value="" disabled selected>Pilih Satuan Jual...</option>';
    const selectedUnitId = configData
        ? configData.selling_unit_id || configData.unit_id
        : "";

    unitsList.forEach((u) => {
        const sel = String(u.id) === String(selectedUnitId) ? "selected" : "";
        unitOptionsHtml += `<option value="${u.id}" ${sel}>${u.unit_name} (${u.short_name})</option>`;
    });

    const hppMethod = configData ? configData.hpp_method || "MANUAL" : "MANUAL";
    const sellingPrice = configData ? configData.selling_price || 0 : "";
    const currentHpp = configData ? configData.current_hpp || 0 : "";

    card.innerHTML = `
        <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
            <span class="fw-bold text-dark small card-title-label">
                <i class="bi bi-tag-fill text-success me-1"></i> Konfigurasi Satuan Jual
            </span>
            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 text-xs btn-remove-card" onclick="removeSellingConfigCard(this)" title="Hapus Satuan Jual Ini">
                <i class="bi bi-trash-fill"></i> Hapus Satuan
            </button>
        </div>

        <div class="row g-3">
            <!-- Satuan Jual — selalu tampil -->
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-dark">Satuan Jual <span class="text-danger">*</span></label>
                <select name="selling_configs[${cardIdx}][selling_unit_id]" class="form-select select2-unit rounded-3 selling-unit-select" required>
                    ${unitOptionsHtml}
                </select>
            </div>

            <!-- Jenis HPP — disembunyikan saat mode fleksibel -->
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-dark">Jenis HPP <span class="text-danger">*</span></label>
                <select name="selling_configs[${cardIdx}][hpp_method]" class="form-select rounded-3 hpp-method-select" onchange="onCardHppMethodChange(this)">
                    <option value="MANUAL"     ${hppMethod === "MANUAL" ? "selected" : ""}>Manual (Fixed Cost / dari Bahan Utama)</option>
                    <option value="CALCULATED" ${hppMethod === "CALCULATED" ? "selected" : ""}>Otomatis (Calculated / Plus Komponen)</option>
                </select>
            </div>

            <!-- Rincian Komponen HPP (CALCULATED) — disembunyikan saat fleksibel -->
            <div class="col-12 calculated-hpp-section ${hppMethod === "CALCULATED" ? "" : "d-none"}">
                <div class="p-3 bg-light rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label small fw-semibold text-dark mb-0">Rincian HPP Produk</label>
                        <button type="button" class="btn btn-xs btn-outline-success rounded-2 py-1 px-2 text-xs d-inline-flex align-items-center gap-1" onclick="addHppComponentRowToCard(this)">
                            <i class="bi bi-plus-lg"></i> Tambah Komponen
                        </button>
                    </div>
                    <div class="table-responsive mb-2">
                        <table class="table table-sm table-borderless align-middle mb-0 hpp-components-table">
                            <thead class="table-light rounded-2">
                                <tr style="font-size: 0.75rem;">
                                    <th>Komponen HPP</th>
                                    <th class="text-end" style="width: 160px;">Biaya (Rp)</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody class="hpp-component-rows"></tbody>
                        </table>
                    </div>
                    <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded-2 border">
                        <span class="small text-muted">Total Biaya HPP Komponen Tambahan:</span>
                        <span class="fw-bold font-monospace text-dark display-component-hpp">Rp 0</span>
                    </div>
                </div>
            </div>

            <!-- HPP Fixed Readonly (MANUAL) — disembunyikan saat fleksibel -->
            <div class="col-md-6 manual-hpp-section ${hppMethod === "MANUAL" ? "" : "d-none"}">
                <label class="form-label small fw-semibold text-dark">HPP Fixed (Otomatis dari Bahan Utama)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">Rp</span>
                    <input type="number" name="selling_configs[${cardIdx}][current_hpp]" class="form-control font-monospace fw-semibold bg-light current-hpp-input" value="${currentHpp}" min="0" readonly>
                </div>
                <div class="form-text fs-xs text-muted">Nilai HPP diambil langsung dari Harga Bahan Utama.</div>
            </div>

            <!-- Harga Jual (MANUAL, posisi atas) — disembunyikan saat fleksibel -->
            <div class="col-md-6 selling-price-top ${hppMethod === "CALCULATED" ? "d-none" : ""}">
                <label class="form-label small fw-semibold text-dark">Harga Jual Produk (Rp) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">Rp</span>
                    <input type="number" name="selling_configs[${cardIdx}][selling_price]" class="form-control font-monospace fw-bold text-dark selling-price-input" value="${sellingPrice}" min="0" oninput="calculateCardTotalHpp(this.closest('.selling-config-card'))" ${hppMethod === "CALCULATED" ? "disabled" : "required"}>
                </div>
            </div>

            <!-- Preview Margin & Laba — disembunyikan saat fleksibel -->
            <div class="col-12">
                <div class="calc-preview-card text-center border rounded-3 p-3 bg-light">
                    <div class="row g-2 align-items-center">
                        <div class="col-6 border-end">
                            <div class="text-muted small" style="font-size: 0.75rem;">Total HPP Produk</div>
                            <div class="h5 fw-bold font-monospace text-dark mb-0 display-total-hpp">Rp 0</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small" style="font-size: 0.75rem;">Estimasi Margin Bersih</div>
                            <div class="h5 fw-bold text-danger mb-0 display-margin-percent">0%</div>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top text-muted small" style="font-size: 0.78rem;">
                        Estimasi Laba per Satuan: <span class="fw-bold text-dark font-monospace display-profit-amount">Rp 0</span>
                    </div>
                </div>
            </div>

            <!-- Harga Jual (CALCULATED, posisi bawah) — disembunyikan saat fleksibel -->
            <div class="col-12 selling-price-bottom ${hppMethod === "CALCULATED" ? "" : "d-none"}">
                <label class="form-label small fw-semibold text-dark">Harga Jual Produk (Rp) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">Rp</span>
                    <input type="number" name="selling_configs[${cardIdx}][selling_price]" class="form-control font-monospace fw-bold text-dark selling-price-input" value="${sellingPrice}" min="0" oninput="calculateCardTotalHpp(this.closest('.selling-config-card'))" ${hppMethod === "CALCULATED" ? "required" : "disabled"}>
                </div>
            </div>
        </div>
    `;

    container.appendChild(card);

    // Inisialisasi Select2
    initSelect2Elements(card);

    // Render komponen HPP lama jika ada (CALCULATED)
    if (
        configData &&
        Array.isArray(configData.components) &&
        configData.components.length > 0
    ) {
        configData.components.forEach((comp) => {
            addHppComponentRowToCard(
                card.querySelector(".btn-outline-success"),
                comp,
            );
        });
    }

    updateCardLabelsAndButtons();
    calculateCardTotalHpp(card);

    /* ── Terapkan state fleksibel ke kartu baru jika toggle sedang ON ── */
    if (_isFlexibleActive()) {
        _applyFlexibleStateToCard(card, true);
    }
}

/* ════════════════════════════════════════════════════════════
   REMOVE / UPDATE CARDS
   ════════════════════════════════════════════════════════════ */

function removeSellingConfigCard(btn) {
    const card = btn.closest(".selling-config-card");
    const container = document.getElementById("sellingConfigsContainer");
    if (!container || !card) return;

    if (container.querySelectorAll(".selling-config-card").length <= 1) {
        alert("Minimal satu Satuan Jual wajib ada untuk produk ini.");
        return;
    }

    $(card).find(".select2-unit, .select2-hpp").select2("destroy");
    card.remove();
    updateCardLabelsAndButtons();
}

function updateCardLabelsAndButtons() {
    const cards = document.querySelectorAll(".selling-config-card");
    cards.forEach((card, idx) => {
        const label = card.querySelector(".card-title-label");
        if (label) {
            label.innerHTML = `<i class="bi bi-tag-fill text-success me-1"></i> Konfigurasi Satuan Jual #${idx + 1}`;
        }
        const deleteBtn = card.querySelector(".btn-remove-card");
        if (deleteBtn) {
            deleteBtn.style.display =
                cards.length > 1 ? "inline-block" : "none";
        }
    });
}

/* ════════════════════════════════════════════════════════════
   HPP METHOD CHANGE
   ════════════════════════════════════════════════════════════ */

function onCardHppMethodChange(selectEl) {
    const card = selectEl.closest(".selling-config-card");
    if (!card) return;

    // Jangan proses jika mode fleksibel aktif
    if (_isFlexibleActive()) return;

    const method = selectEl.value;
    const calcSection = card.querySelector(".calculated-hpp-section");
    const manualSection = card.querySelector(".manual-hpp-section");

    if (method === "CALCULATED") {
        if (calcSection) calcSection.classList.remove("d-none");
        if (manualSection) manualSection.classList.add("d-none");

        const tbody = card.querySelector(".hpp-component-rows");
        if (tbody && tbody.querySelectorAll(".component-row").length === 0) {
            addHppComponentRowToCard(
                card.querySelector(".calculated-hpp-section button"),
            );
        }
    } else {
        if (calcSection) calcSection.classList.add("d-none");
        if (manualSection) manualSection.classList.remove("d-none");

        const unitPriceVal =
            parseFloat(document.getElementById("unit_price")?.value) || 0;
        const currentHppInput = card.querySelector(".current-hpp-input");
        if (currentHppInput) {
            currentHppInput.value =
                unitPriceVal > 0 ? Math.round(unitPriceVal) : 0;
        }
    }

    const sellingPriceTop = card.querySelector(".selling-price-top");
    const sellingPriceBot = card.querySelector(".selling-price-bottom");

    if (method === "CALCULATED") {
        if (sellingPriceTop) {
            sellingPriceTop.classList.add("d-none");
            sellingPriceTop.querySelector("input").removeAttribute("required");
            sellingPriceTop
                .querySelector("input")
                .setAttribute("disabled", "disabled");
        }
        if (sellingPriceBot) {
            sellingPriceBot.classList.remove("d-none");
            sellingPriceBot
                .querySelector("input")
                .setAttribute("required", "required");
            sellingPriceBot.querySelector("input").removeAttribute("disabled");
        }
    } else {
        if (sellingPriceTop) {
            sellingPriceTop.classList.remove("d-none");
            sellingPriceTop
                .querySelector("input")
                .setAttribute("required", "required");
            sellingPriceTop.querySelector("input").removeAttribute("disabled");
        }
        if (sellingPriceBot) {
            sellingPriceBot.classList.add("d-none");
            sellingPriceBot.querySelector("input").removeAttribute("required");
            sellingPriceBot
                .querySelector("input")
                .setAttribute("disabled", "disabled");
        }
    }

    calculateCardTotalHpp(card);
}

/* ════════════════════════════════════════════════════════════
   HPP COMPONENT ROWS
   ════════════════════════════════════════════════════════════ */

let componentRowGlobalIndex = 100;

function addHppComponentRowToCard(btnOrElement, compData = null) {
    const card = btnOrElement.closest(".selling-config-card");
    if (!card) return;

    const cardIdx = card.getAttribute("data-card-idx");
    const tbody = card.querySelector(".hpp-component-rows");
    if (!tbody) return;

    const compIdx = componentRowGlobalIndex++;
    const masterList = window.hppMasterList || [];
    const selectedHppId = compData ? compData.hpp_id : "";
    const costVal = compData ? compData.cost : 0;

    let optionsHtml = '<option value="" selected>Pilih Komponen</option>';
    masterList.forEach((hpp) => {
        const sel = String(hpp.id) === String(selectedHppId) ? "selected" : "";
        optionsHtml += `<option value="${hpp.id}" data-cost="${hpp.unit_cost}" ${sel}>${hpp.name} (Rp ${Number(hpp.unit_cost).toLocaleString("id-ID")})</option>`;
    });

    const tr = document.createElement("tr");
    tr.className = "component-row";
    tr.innerHTML = `
        <td>
            <select name="selling_configs[${cardIdx}][components][${compIdx}][hpp_id]" class="form-select form-select-sm select2-hpp rounded-2 component-select" onchange="onCardComponentSelectChange(this)">
                ${optionsHtml}
            </select>
        </td>
        <td>
            <input type="number" name="selling_configs[${cardIdx}][components][${compIdx}][cost]" class="form-control form-control-sm font-monospace text-end rounded-2 component-cost bg-light" value="${costVal}" min="0" readonly>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="removeHppComponentRowFromCard(this)" title="Hapus"><i class="bi bi-x-circle-fill"></i></button>
        </td>
    `;

    tbody.appendChild(tr);
    initSelect2Elements(tr);

    $(tr)
        .find(".select2-hpp")
        .on("select2:select select2:clear change", function () {
            onCardComponentSelectChange(this);
        });

    calculateCardTotalHpp(card);
}

function removeHppComponentRowFromCard(btn) {
    const card = btn.closest(".selling-config-card");
    const row = btn.closest("tr");
    if (row) {
        $(row).find(".select2-hpp").select2("destroy");
        row.remove();
    }
    if (card) calculateCardTotalHpp(card);
}

function onCardComponentSelectChange(selectEl) {
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

    const card = selectEl.closest(".selling-config-card");
    if (card) calculateCardTotalHpp(card);
}

/* ════════════════════════════════════════════════════════════
   SYNC & CALCULATE HPP
   ════════════════════════════════════════════════════════════ */

function syncUnitPriceToManualCards() {
    // Jangan sync jika mode fleksibel aktif
    if (_isFlexibleActive()) return;

    const unitPriceVal =
        parseFloat(document.getElementById("unit_price")?.value) || 0;
    document.querySelectorAll(".selling-config-card").forEach((card) => {
        const methodSelect = card.querySelector(".hpp-method-select");
        if (methodSelect && methodSelect.value === "MANUAL") {
            const currentHppInput = card.querySelector(".current-hpp-input");
            if (currentHppInput) {
                currentHppInput.value =
                    unitPriceVal > 0 ? Math.round(unitPriceVal) : 0;
            }
        }
    });
}

function calculateAllCardsHpp() {
    document.querySelectorAll(".selling-config-card").forEach((card) => {
        calculateCardTotalHpp(card);
    });
}

function calculateCardTotalHpp(card) {
    if (!card) return;

    // Jika mode fleksibel aktif, tampilkan Rp 0 di semua display (tidak perlu hitung)
    if (_isFlexibleActive()) {
        const displayTotalHpp = card.querySelector(".display-total-hpp");
        const marginPercentEl = card.querySelector(".display-margin-percent");
        const profitAmountEl = card.querySelector(".display-profit-amount");
        if (displayTotalHpp) displayTotalHpp.textContent = "Rp 0";
        if (marginPercentEl) marginPercentEl.textContent = "0%";
        if (profitAmountEl) profitAmountEl.textContent = "Rp 0";
        return;
    }

    const unitPriceVal =
        parseFloat(document.getElementById("unit_price")?.value) || 0;
    const methodSelect = card.querySelector(".hpp-method-select");
    const method = methodSelect ? methodSelect.value : "MANUAL";

    let totalHpp = 0;
    let componentsSum = 0;

    if (method === "CALCULATED") {
        card.querySelectorAll(".component-cost").forEach((input) => {
            componentsSum += parseFloat(input.value) || 0;
        });
        const displayCompHpp = card.querySelector(".display-component-hpp");
        if (displayCompHpp) {
            displayCompHpp.textContent =
                "Rp " + Math.round(componentsSum).toLocaleString("id-ID");
        }
        totalHpp = unitPriceVal + componentsSum;
    } else {
        totalHpp = unitPriceVal;
        const currentHppInput = card.querySelector(".current-hpp-input");
        if (currentHppInput) {
            currentHppInput.value =
                unitPriceVal > 0 ? Math.round(unitPriceVal) : 0;
        }
    }

    const displayTotalHpp = card.querySelector(".display-total-hpp");
    if (displayTotalHpp) {
        displayTotalHpp.textContent =
            "Rp " + Math.round(totalHpp).toLocaleString("id-ID");
    }

    const visiblePriceWrapper =
        method === "CALCULATED"
            ? card.querySelector(".selling-price-bottom")
            : card.querySelector(".selling-price-top");
    const sellingPriceInput = visiblePriceWrapper
        ? visiblePriceWrapper.querySelector(".selling-price-input")
        : null;
    const sellingPrice =
        parseFloat(sellingPriceInput ? sellingPriceInput.value : 0) || 0;
    const profit = sellingPrice - totalHpp;
    const marginPercent =
        sellingPrice > 0 ? ((profit / sellingPrice) * 100).toFixed(1) : 0;

    const marginPercentEl = card.querySelector(".display-margin-percent");
    if (marginPercentEl) {
        marginPercentEl.textContent = marginPercent + "%";
        const pct = parseFloat(marginPercent);
        marginPercentEl.className =
            pct >= 40
                ? "h5 fw-bold text-success mb-0 display-margin-percent"
                : pct >= 20
                  ? "h5 fw-bold text-warning mb-0 display-margin-percent"
                  : "h5 fw-bold text-danger mb-0 display-margin-percent";
    }

    const profitAmountEl = card.querySelector(".display-profit-amount");
    if (profitAmountEl) {
        profitAmountEl.textContent =
            "Rp " + Math.round(profit).toLocaleString("id-ID");
    }
}

/* ════════════════════════════════════════════════════════════
   SELECT2 INIT
   ════════════════════════════════════════════════════════════ */

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

/* ════════════════════════════════════════════════════════════
   THUMBNAIL PREVIEW
   ════════════════════════════════════════════════════════════ */

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

/* ════════════════════════════════════════════════════════════
   MODAL DELETE PRODUCT
   ════════════════════════════════════════════════════════════ */

function openDeleteModal(id, name) {
    const nameEl = document.getElementById("deleteProductName");
    const formEl = document.getElementById("deleteProductForm");
    if (nameEl) nameEl.textContent = name;
    if (formEl) formEl.action = "/products/" + id;
    const modalEl = document.getElementById("modalDeleteProduct");
    if (modalEl) new bootstrap.Modal(modalEl).show();
}

/* ════════════════════════════════════════════════════════════
   DATATABLES PRODUCTS
   ════════════════════════════════════════════════════════════ */

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
            { targets: 0, responsivePriority: 1 },
            { targets: 1, responsivePriority: 5 },
            { targets: 2, responsivePriority: 2 },
            { targets: 3, responsivePriority: 6 },
            { targets: 4, responsivePriority: 3 },
            { targets: 5, responsivePriority: 4 },
            { targets: 6, responsivePriority: 4 },
            { targets: 7, responsivePriority: 7 },
            { targets: 8, responsivePriority: 11 },
            { targets: 9, responsivePriority: 12 },
            { targets: 10, responsivePriority: 1 },
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
            paginate: { first: "«", previous: "‹", next: "›", last: "»" },
        },
        pagingType: "full_numbers",
        dom: '<"table-responsive-wrapper"t><"d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-2 bg-white"ip>',
        pageLength: 10,
    });

    let resizeTimer;
    $(window).on("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (window.innerWidth < 768 !== isMobile) {
                dataTable.destroy();
                initProductsDataTable();
            }
        }, 300);
    });

    $("#dtSearchInput").on("keyup input", function () {
        dataTable.search(this.value).draw();
    });
}
