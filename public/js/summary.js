/**
 * WARJOK — Ringkasan & Margin Charts and DataTables JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

document.addEventListener("DOMContentLoaded", function () {
    // 1. Inisialisasi Chart.js jika data tersedia
    const recapData = window.summaryRecapData || {};
    const dailyRecap = recapData.daily_recap || [];

    const labels = [];
    const salesData = [];
    const hppData = [];
    const marginData = [];

    dailyRecap.forEach((item) => {
        // Format tanggal ringkas (e.g. 19 Sep)
        const d = new Date(item.date);
        const day = d.getDate();
        const monthNames = [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "Mei",
            "Jun",
            "Jul",
            "Ags",
            "Sep",
            "Okt",
            "Nov",
            "Des",
        ];
        const formattedLabel = isNaN(day)
            ? item.date
            : `${day} ${monthNames[d.getMonth()]}`;

        labels.push(formattedLabel);
        salesData.push(Number(item.total_sales || 0));
        hppData.push(Number(item.total_hpp || 0));
        marginData.push(Number(item.total_margin || 0));
    });

    // ── Chart 1: Multi-Dataset Trend Line Chart ── //
    const trendCtx = document.getElementById("marginTrendChart");
    if (trendCtx && typeof Chart !== "undefined") {
        new Chart(trendCtx, {
            type: "line",
            data: {
                labels: labels.length > 0 ? labels : ["Tidak Ada Data"],
                datasets: [
                    {
                        label: "Total Penjualan (Omset)",
                        data: salesData.length > 0 ? salesData : [0],
                        borderColor: "#3b82f6",
                        backgroundColor: "rgba(59, 130, 246, 0.08)",
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: "#3b82f6",
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: "Total HPP (Modal)",
                        data: hppData.length > 0 ? hppData : [0],
                        borderColor: "#f59e0b",
                        backgroundColor: "transparent",
                        borderWidth: 2,
                        borderDash: [4, 4],
                        tension: 0.35,
                        fill: false,
                        pointBackgroundColor: "#f59e0b",
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    },
                    {
                        label: "Margin Keuntungan (Laba)",
                        data: marginData.length > 0 ? marginData : [0],
                        borderColor: "#10b981",
                        backgroundColor: "rgba(16, 185, 129, 0.12)",
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: "#10b981",
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: "index",
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: "top",
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            font: {
                                family: "'Poppins', sans-serif",
                                size: 12,
                                weight: 500,
                            },
                            color: "#475569",
                        },
                    },
                    tooltip: {
                        backgroundColor: "rgba(15, 23, 42, 0.9)",
                        titleFont: {
                            family: "'Poppins', sans-serif",
                            size: 13,
                            weight: 600,
                        },
                        bodyFont: { family: "'Poppins', sans-serif", size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || "";
                                if (label) {
                                    label += ": ";
                                }
                                if (context.parsed.y !== null) {
                                    label +=
                                        "Rp " +
                                        Math.round(
                                            context.parsed.y,
                                        ).toLocaleString("id-ID");
                                }
                                return label;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            font: { family: "'Poppins', sans-serif", size: 11 },
                            color: "#64748b",
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "#f1f5f9",
                        },
                        ticks: {
                            font: { family: "'Poppins', sans-serif", size: 11 },
                            color: "#64748b",
                            callback: function (value) {
                                if (value >= 1000000) {
                                    return (
                                        "Rp " +
                                        (value / 1000000).toFixed(1) +
                                        "jt"
                                    );
                                } else if (value >= 1000) {
                                    return (
                                        "Rp " + (value / 1000).toFixed(0) + "rb"
                                    );
                                }
                                return "Rp " + value;
                            },
                        },
                    },
                },
            },
        });
    }

    // ── Chart 2: Komposisi Margin vs HPP (Doughnut) ── //
    const doughnutCtx = document.getElementById("marginDoughnutChart");
    if (doughnutCtx && typeof Chart !== "undefined") {
        const overall = recapData.overall || {};
        const totalHpp = Number(overall.total_hpp || 0);
        const totalMargin = Number(overall.total_margin || 0);

        new Chart(doughnutCtx, {
            type: "doughnut",
            data: {
                labels: ["HPP (Modal)", "Margin (Laba Bersih)"],
                datasets: [
                    {
                        data:
                            totalHpp === 0 && totalMargin === 0
                                ? [1, 1]
                                : [totalHpp, totalMargin],
                        backgroundColor: ["#f59e0b", "#10b981"],
                        borderWidth: 2,
                        borderColor: "#ffffff",
                        hoverOffset: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            font: {
                                family: "'Poppins', sans-serif",
                                size: 12,
                            },
                            color: "#475569",
                        },
                    },
                    tooltip: {
                        backgroundColor: "rgba(15, 23, 42, 0.9)",
                        titleFont: {
                            family: "'Poppins', sans-serif",
                            size: 13,
                            weight: 600,
                        },
                        bodyFont: { family: "'Poppins', sans-serif", size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                let label = context.label || "";
                                if (label) {
                                    label += ": ";
                                }
                                if (context.parsed !== null) {
                                    label +=
                                        "Rp " +
                                        Math.round(
                                            context.parsed,
                                        ).toLocaleString("id-ID");
                                }
                                return label;
                            },
                        },
                    },
                },
                cutout: "68%",
            },
        });
    }
});

// ── Preset Filter Dates Helper ── //
let activePreset = null;

function setFilterPreset(preset) {
    const startInput = document.getElementById("filterStartDate");
    const endInput = document.getElementById("filterEndDate");
    const buttons = document.querySelectorAll('[onclick^="setFilterPreset"]');

    if (activePreset === preset) {
        activePreset = null;

        buttons.forEach((btn) => {
            btn.classList.remove("btn-secondary", "active-preset");
            btn.classList.add("btn-outline-secondary");
        });

        // ← Redirect ke URL bersih tanpa query params
        window.location.href = window.location.pathname;
        return;
    }

    activePreset = preset;

    buttons.forEach((btn) => {
        btn.classList.remove("btn-secondary", "active-preset");
        btn.classList.add("btn-outline-secondary");
    });

    const clickedBtn = document.querySelector(
        `[onclick="setFilterPreset('${preset}')"]`,
    );
    if (clickedBtn) {
        clickedBtn.classList.remove("btn-outline-secondary");
        clickedBtn.classList.add("btn-secondary", "active-preset");
    }

    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");
    const day = String(now.getDate()).padStart(2, "0");

    let startDate, endDate;

    if (preset === "today") {
        startDate = `${year}-${month}-${day}`;
        endDate = `${year}-${month}-${day}`;
    } else if (preset === "7days") {
        const d7 = new Date(now);
        d7.setDate(d7.getDate() - 6);
        startDate = d7.toISOString().split("T")[0];
        endDate = `${year}-${month}-${day}`;
    } else if (preset === "thisMonth") {
        startDate = `${year}-${month}-01`;
        endDate = `${year}-${month}-${day}`;
    } else if (preset === "lastMonth") {
        const lm = new Date(year, now.getMonth() - 1, 1);
        const lmY = lm.getFullYear();
        const lmM = String(lm.getMonth() + 1).padStart(2, "0");
        const lmEnd = new Date(year, now.getMonth(), 0).getDate();
        startDate = `${lmY}-${lmM}-01`;
        endDate = `${lmY}-${lmM}-${String(lmEnd).padStart(2, "0")}`;
    }

    startInput.value = startDate;
    endInput.value = endDate;

    document.getElementById("formFilterSummary").submit();
}

window.setFilterPreset = setFilterPreset;

// ── DataTables Initialization ── //
if (typeof jQuery !== "undefined") {
    jQuery(document).ready(function ($) {
        if ($("#summaryRecapTable").length > 0) {
            $("#summaryRecapTable").DataTable({
                responsive: false,
                order: [[1, "desc"]], // Sort by date desc
                columnDefs: [{ targets: "no-sort", orderable: false }],
                language: {
                    emptyTable:
                        "Tidak ada data penjualan pada periode tanggal yang dipilih.",
                    zeroRecords: "Tidak ada data rekapitulasi yang cocok.",
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
        }
    });
}

// ── Restore active preset button saat page reload ── //
(function restoreActivePreset() {
    const start = document.getElementById("filterStartDate")?.value;
    const end = document.getElementById("filterEndDate")?.value;
    if (!start || !end) return;

    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");
    const day = String(now.getDate()).padStart(2, "0");
    const today = `${year}-${month}-${day}`;

    const d7 = new Date(now);
    d7.setDate(d7.getDate() - 6);
    const d7str = d7.toISOString().split("T")[0];

    const lm = new Date(year, now.getMonth() - 1, 1);
    const lmY = lm.getFullYear();
    const lmM = String(lm.getMonth() + 1).padStart(2, "0");
    const lmLast = new Date(year, now.getMonth(), 0).getDate();
    const lmFirst = `${lmY}-${lmM}-01`;
    const lmEnd = `${lmY}-${lmM}-${String(lmLast).padStart(2, "0")}`;

    // Cek apakah ini request dari user (ada ?start_date di URL)
    // Jika tidak ada query param → halaman pertama kali dibuka, tidak ada yang active
    const urlParams = new URLSearchParams(window.location.search);
    const hasFilter = urlParams.has("start_date") || urlParams.has("end_date");
    if (!hasFilter) return; // ← tidak auto-active saat buka pertama kali

    let detected = null;
    if (start === today && end === today) detected = "today";
    if (start === d7str && end === today) detected = "7days";
    // thisMonth sengaja tidak di-detect otomatis karena itu nilai default
    if (start === lmFirst && end === lmEnd) detected = "lastMonth";

    if (!detected) return;

    activePreset = detected;
    const btn = document.querySelector(
        `[onclick="setFilterPreset('${detected}')"]`,
    );
    if (btn) {
        btn.classList.remove("btn-outline-secondary");
        btn.classList.add("btn-secondary", "active-preset");
    }
})();

// ── Modal Detail Produk: Pagination + Search ── //
(function () {
    const PAGE_SIZE = 10;
    let _allProducts = [];
    let _filtered = [];
    let _currentPage = 1;

    function formatNumber(n) {
        return parseInt(n ?? 0).toLocaleString("id-ID");
    }

    function renderTable(page) {
        const tbody = document.getElementById("modalDetailProdukBody");
        const emptyState = document.getElementById("modalEmptyState");
        const table = document.getElementById("modalDetailProdukTable");
        const dtFooter = document.getElementById("modalDtFooter");
        tbody.innerHTML = "";

        if (_filtered.length === 0) {
            emptyState.classList.remove("d-none");
            table.closest(".px-3").classList.add("d-none");
            dtFooter.classList.add("d-none");
            return;
        }

        emptyState.classList.add("d-none");
        table.closest(".px-3").classList.remove("d-none");
        dtFooter.classList.remove("d-none");

        const start = (page - 1) * PAGE_SIZE;
        const end = Math.min(start + PAGE_SIZE, _filtered.length);
        const slice = _filtered.slice(start, end);

        slice.forEach(function (prod, i) {
            const sisa = parseInt(prod.stock_final ?? 0);
            const qty = parseInt(prod.quantity ?? 0);
            const absNum = start + i + 1;

            let sisaColor = "#16a34a";
            if (sisa <= 0) sisaColor = "#dc2626";
            else if (sisa <= 5) sisaColor = "#d97706";

            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td class="text-center text-muted px-3" style="font-size:.78rem;">${absNum}</td>
                <td class="font-monospace px-3" style="font-size:.78rem;color:#64748b;">${prod.product_code ?? "—"}</td>
                <td class="fw-medium text-dark px-3">${prod.product_name ?? "—"}</td>
                <td class="text-center px-3">
                    <span class="fw-semibold font-monospace" style="color:#3b82f6;">${qty}</span>
                    <span class="text-muted ms-1" style="font-size:.74rem;">unit</span>
                </td>
                <td class="text-center px-3">
                    <span class="fw-semibold font-monospace" style="color:${sisaColor};">${formatNumber(sisa)}</span>
                    <span class="text-muted ms-1" style="font-size:.74rem;">unit</span>
                </td>
            `;
            tbody.appendChild(tr);
        });

        renderDtFooter(page, start, end);
    }

    function renderDtFooter(current, start, end) {
        const totalPage = Math.ceil(_filtered.length / PAGE_SIZE);

        // Info text
        const info = document.getElementById("modalDtInfo");
        if (info) {
            info.textContent = `Menampilkan ${start + 1}–${end} dari ${_filtered.length} produk`;
        }

        // Paginate
        const pag = document.getElementById("modalDtPaginate");
        if (!pag) return;
        pag.innerHTML = "";

        const mkBtn = (label, targetPage, disabled, active) => {
            const btn = document.createElement("button");
            btn.innerHTML = label;
            btn.className =
                "modal-dt-btn" +
                (active ? " active" : "") +
                (disabled ? " disabled" : "");
            btn.disabled = disabled;
            if (!disabled && !active) {
                btn.addEventListener("click", function () {
                    _currentPage = targetPage;
                    renderTable(_currentPage);
                });
            }
            return btn;
        };

        // Selalu tampilkan «‹ dan ›»
        pag.appendChild(mkBtn("«", 1, current === 1, false));
        pag.appendChild(mkBtn("‹", current - 1, current === 1, false));

        // Nomor halaman: hanya jika totalPage > 1
        if (totalPage > 1) {
            for (let p = 1; p <= totalPage; p++) {
                pag.appendChild(mkBtn(p, p, false, p === current));
            }
        }

        pag.appendChild(
            mkBtn(
                "›",
                current + 1,
                current === totalPage || totalPage === 0,
                false,
            ),
        );
        pag.appendChild(
            mkBtn(
                "»",
                totalPage,
                current === totalPage || totalPage === 0,
                false,
            ),
        );
    }

    function applySearch(query) {
        const q = (query || "").toLowerCase().trim();
        _filtered = q
            ? _allProducts.filter(
                  (p) =>
                      (p.product_name || "").toLowerCase().includes(q) ||
                      (p.product_code || "").toLowerCase().includes(q),
              )
            : _allProducts.slice();
        _currentPage = 1;
        renderTable(_currentPage);
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".btn-detail-produk").forEach(function (btn) {
            btn.addEventListener("click", function () {
                const date = this.getAttribute("data-date");
                const products = JSON.parse(
                    this.getAttribute("data-products") || "[]",
                );

                _allProducts = products;
                _filtered = products.slice();
                _currentPage = 1;

                document.getElementById("modalDetailProdukDate").textContent =
                    date;
                document.getElementById("modalDetailProdukCount").textContent =
                    products.length + " produk terjual pada hari ini";

                const searchEl = document.getElementById("modalProductSearch");
                if (searchEl) searchEl.value = "";

                renderTable(_currentPage);
            });
        });

        const searchInput = document.getElementById("modalProductSearch");
        if (searchInput) {
            searchInput.addEventListener("input", function () {
                applySearch(this.value);
            });
        }
    });
})();

// ══════════════════════════════════════════════════════════
//  EXPORT PDF — Ringkasan & Margin Penjualan
//  Tema Kelautan Elegan Minimalis | jsPDF + AutoTable
// ══════════════════════════════════════════════════════════
(function () {
    function fmtRp(value) {
        return parseInt(value || 0).toLocaleString("id-ID");
    }

    function fmtDate(dateStr) {
        if (!dateStr) return "-";
        const d = new Date(dateStr);
        if (isNaN(d)) return dateStr;
        const months = [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "Mei",
            "Jun",
            "Jul",
            "Ags",
            "Sep",
            "Okt",
            "Nov",
            "Des",
        ];
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    }

    function exportPdf() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({
            orientation: "landscape",
            unit: "mm",
            format: "a4",
        });

        const recapData = window.summaryRecapData || {};
        const overall = window.summaryOverall || {};
        const period = window.summaryPeriod || {};
        const dailyList = recapData.daily_recap || [];

        const pageW = doc.internal.pageSize.getWidth();
        const pageH = doc.internal.pageSize.getHeight();
        const margin = 14;
        const contentW = pageW - margin * 2;

        // ── Palet Tema Kelautan Elegan Minimalis (Modern Oceanic) ──
        const C = {
            // Header & Brand Colors
            deepNavy: [8, 35, 65], // Deep Marine Navy (Teks utama, judul, & baris total)
            navy: [18, 56, 92], // Oceanic Navy (Tabel header utama & Date bar)
            midNavy: [28, 80, 128], // Marine Slate (Header tabel produk)
            ocean: [0, 122, 168], // Ocean Blue (Aksen utama & highlight)
            teal: [16, 140, 125], // Marine Teal (Margin bersih positif)
            seafoam: [20, 155, 135], // Seafoam Green (Margin kotor)
            coral: [215, 65, 65], // Coral Red (Nilai negatif / stok habis)
            amber: [225, 140, 20], // Amber (Stok menipis)
            white: [255, 255, 255],

            // Baris & Area Tabel
            rowOdd: [255, 255, 255], // Putih bersih
            rowEven: [244, 249, 253], // Soft Azure Mist (Kontras halus & elegan)
            rowTotal: [8, 35, 65], // Deep Marine Navy solid
            summaryCardBg: [246, 250, 254], // Background kartu ringkasan harian

            // Warna Teks
            textDark: [12, 30, 50], // Teks konten gelap tegas
            textLight: [230, 242, 250], // Teks terang untuk background gelap
            textMuted: [140, 175, 200], // Muted di header gelap
            textMutedDk: [90, 120, 148], // Muted elegan di latar terang

            // Border & Divider (Tegas & Rapi)
            borderGrid: [175, 200, 222], // Garis tabel slate-blue tegas
            borderHead: [8, 35, 65], // Garis header tabel gelap
        };

        // ════════════════════════════════════════════════════════
        //  HELPER: Footer Halaman Tema Kelautan (Deep Marine Navy)
        // ════════════════════════════════════════════════════════
        const now = new Date();
        const ts = `Dicetak: ${now.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" })} ${now.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" })}`;

        const addFooter = (pageNum, totalPages) => {
            // Strip footer deep ocean navy solid
            doc.setFillColor(8, 35, 65);
            doc.rect(0, pageH - 9, pageW, 9, "F");

            // Teks identitas brand (Kiri)
            doc.setFont("helvetica", "normal");
            doc.setFontSize(7.2);
            doc.setTextColor(140, 180, 205);
            doc.text(
                "WARJOK 16  •  Warung Pojok Oremus  •  PT Oremus Bahari Mandiri",
                margin,
                pageH - 3.5,
            );

            // Nomor halaman (Kanan)
            doc.text(
                `Halaman ${pageNum} / ${totalPages}`,
                pageW - margin,
                pageH - 3.5,
                { align: "right" },
            );
        };

        // ════════════════════════════════════════════════════════
        //  HALAMAN 1 — Header + Tabel Rekapitulasi Harian
        // ════════════════════════════════════════════════════════

        // ── Header Utama Deep Marine Navy ────────────────────────
        const hdrH1 = 30;
        doc.setFillColor(8, 35, 65);
        doc.rect(0, 0, pageW, hdrH1, "F");

        // Brand — Kiri
        doc.setFont("helvetica", "bold");
        doc.setFontSize(18);
        doc.setTextColor(255, 255, 255);
        doc.text("WARJOK 16", margin, 13);

        // Tagline Perusahaan
        doc.setFont("helvetica", "normal");
        doc.setFontSize(8);
        doc.setTextColor(140, 175, 205);
        doc.text("Warung Pojok 16  •  PT Oremus Bahari Mandiri", margin, 21);

        // Judul Laporan — Kanan
        doc.setFont("helvetica", "bold");
        doc.setFontSize(13);
        doc.setTextColor(255, 255, 255);
        doc.text("Ringkasan & Margin Penjualan", pageW - margin, 13, {
            align: "right",
        });

        // Periode
        doc.setFont("helvetica", "normal");
        doc.setFontSize(8.5);
        doc.setTextColor(170, 210, 240);
        doc.text(
            `Periode: ${fmtDate(period.startDate)}  —  ${fmtDate(period.endDate)}`,
            pageW - margin,
            20,
            { align: "right" },
        );

        // Timestamp Cetak
        doc.setFontSize(7.5);
        doc.setTextColor(140, 175, 205);
        doc.text(ts, pageW - margin, 26, { align: "right" });

        // ── Section Title Tabel ──────────────────────────────────
        let cy = 39;

        doc.setFont("helvetica", "bold");
        doc.setFontSize(10.5);
        doc.setTextColor(...C.deepNavy);
        doc.text("Tabel Rekapitulasi Harian", margin, cy);

        doc.setFont("helvetica", "normal");
        doc.setFontSize(7.5);
        doc.setTextColor(...C.textMutedDk);
        doc.text(
            "Rincian agregasi penjualan, kuantitas produk, HPP, margin kotor, estimasi gaji karyawan, dan margin bersih per tanggal.",
            margin,
            cy + 4.8,
        );

        cy += 9.5;

        // ── Tabel Rekapitulasi Harian (No 14mm horizontal, 8 Kolom) ──
        const summaryHead = [
            [
                { content: "No", styles: { halign: "center", cellWidth: 14 } },
                {
                    content: "Tanggal",
                    styles: { halign: "center", cellWidth: 32 },
                },
                {
                    content: "Qty Terjual",
                    styles: { halign: "center", cellWidth: 26 },
                },
                {
                    content: "Total Penjualan",
                    styles: { halign: "right", cellWidth: 41 },
                },
                {
                    content: "Total HPP",
                    styles: { halign: "right", cellWidth: 39 },
                },
                {
                    content: "Margin Kotor",
                    styles: { halign: "right", cellWidth: 39 },
                },
                {
                    content: "Gaji Karyawan",
                    styles: { halign: "right", cellWidth: 39 },
                },
                {
                    content: "Margin Bersih",
                    styles: { halign: "right", cellWidth: 39 },
                },
            ],
        ];

        const summaryBody = dailyList.map((item, idx) => {
            const daySales = parseFloat(item.total_sales || 0);
            const dayHpp = parseFloat(item.total_hpp || 0);
            const dayGross = parseFloat(
                item.total_gross_margin || daySales - dayHpp,
            );
            const dayGaji = dayGross * 0.5;
            const dayNet = dayGross - dayGaji;

            return [
                {
                    content: (idx + 1).toString(),
                    styles: {
                        halign: "center",
                        textColor: C.textMutedDk,
                        fontStyle: "normal",
                    },
                },
                {
                    content: fmtDate(item.date),
                    styles: {
                        halign: "center",
                        fontStyle: "bold",
                        textColor: C.textDark,
                    },
                },
                {
                    content: fmtRp(item.total_quantity) + " unit",
                    styles: { halign: "center", textColor: C.textDark },
                },
                {
                    content: "Rp " + fmtRp(daySales),
                    styles: {
                        halign: "right",
                        fontStyle: "bold",
                        textColor: C.textDark,
                    },
                },
                {
                    content: "Rp " + fmtRp(dayHpp),
                    styles: { halign: "right", textColor: C.textMutedDk },
                },
                {
                    content: "Rp " + fmtRp(dayGross),
                    styles: {
                        halign: "right",
                        fontStyle: "bold",
                        textColor: C.seafoam,
                    },
                },
                {
                    content: "Rp " + fmtRp(dayGaji),
                    styles: { halign: "right", textColor: C.ocean },
                },
                {
                    content: "Rp " + fmtRp(dayNet),
                    styles: {
                        halign: "right",
                        fontStyle: "bold",
                        textColor: dayNet < 0 ? C.coral : C.teal,
                    },
                },
            ];
        });

        // Baris TOTAL — Deep Ocean Navy Solid (8 Kolom)
        summaryBody.push([
            {
                content: "TOTAL",
                colSpan: 2,
                styles: {
                    halign: "right",
                    fontStyle: "bold",
                    fillColor: C.rowTotal,
                    textColor: C.textLight,
                },
            },
            {
                content: fmtRp(overall.qty) + " unit",
                styles: {
                    halign: "center",
                    fontStyle: "bold",
                    fillColor: C.rowTotal,
                    textColor: C.white,
                },
            },
            {
                content: "Rp " + fmtRp(overall.sales),
                styles: {
                    halign: "right",
                    fontStyle: "bold",
                    fillColor: C.rowTotal,
                    textColor: C.white,
                },
            },
            {
                content: "Rp " + fmtRp(overall.hpp),
                styles: {
                    halign: "right",
                    fontStyle: "bold",
                    fillColor: C.rowTotal,
                    textColor: C.textMuted,
                },
            },
            {
                content: "Rp " + fmtRp(overall.grossMargin),
                styles: {
                    halign: "right",
                    fontStyle: "bold",
                    fillColor: C.rowTotal,
                    textColor: [80, 220, 200],
                },
            },
            {
                content: "Rp " + fmtRp(overall.gaji),
                styles: {
                    halign: "right",
                    fontStyle: "bold",
                    fillColor: C.rowTotal,
                    textColor: [100, 200, 240],
                },
            },
            {
                content: "Rp " + fmtRp(overall.netMargin),
                styles: {
                    halign: "right",
                    fontStyle: "bold",
                    fillColor: C.rowTotal,
                    textColor:
                        overall.netMargin < 0
                            ? [255, 130, 110]
                            : [80, 230, 210],
                },
            },
        ]);

        doc.autoTable({
            startY: cy,
            head: summaryHead,
            body: summaryBody,
            margin: { left: margin, right: margin },
            tableWidth: contentW,
            styles: {
                font: "helvetica",
                fontSize: 7.5,
                cellPadding: { top: 3.5, right: 2, bottom: 3.5, left: 2 },
                lineColor: C.borderGrid,
                lineWidth: 0.35,
                textColor: C.textDark,
                fillColor: C.rowOdd,
                overflow: "visible",
            },
            headStyles: {
                fillColor: C.navy,
                textColor: C.white,
                fontStyle: "bold",
                fontSize: 8,
                cellPadding: { top: 4.5, right: 2, bottom: 4.5, left: 2 },
                lineColor: C.borderHead,
                lineWidth: 0.35,
            },
            alternateRowStyles: { fillColor: C.rowEven },
            didParseCell: function (data) {
                if (data.section === "body") {
                    const isLastRow = data.row.index === summaryBody.length - 1;
                    if (isLastRow) {
                        data.cell.styles.fillColor = C.rowTotal;
                    } else {
                        data.cell.styles.fillColor =
                            data.row.index % 2 === 0 ? C.rowOdd : C.rowEven;
                    }
                }
            },
        });

        // ════════════════════════════════════════════════════════
        //  HALAMAN 2+ — Detail Produk Per Tanggal
        // ════════════════════════════════════════════════════════
        const daysWithProducts = dailyList.filter(
            (d) => (d.products || []).length > 0,
        );

        if (daysWithProducts.length > 0) {
            doc.addPage();

            // ── Header Halaman Detail ────────────────────────────────
            const hdrH2 = 24;
            doc.setFillColor(8, 35, 65);
            doc.rect(0, 0, pageW, hdrH2, "F");

            // Kiri: Judul Halaman Detail
            doc.setFont("helvetica", "bold");
            doc.setFontSize(13);
            doc.setTextColor(255, 255, 255);
            doc.text("Detail Produk Terjual per Tanggal", margin, 11.5);

            // Kiri: Periode
            doc.setFont("helvetica", "normal");
            doc.setFontSize(8.5);
            doc.setTextColor(170, 210, 240);
            doc.text(
                `Periode: ${fmtDate(period.startDate)} — ${fmtDate(period.endDate)}`,
                margin,
                18.5,
            );

            // Kanan: Timestamp Cetak
            doc.setFont("helvetica", "normal");
            doc.setFontSize(7.5);
            doc.setTextColor(140, 175, 205);
            doc.text(ts, pageW - margin, 18.5, { align: "right" });

            let detailY = 32;
            const maxPageH = pageH - 12;

            daysWithProducts.forEach((dayItem) => {
                const products = dayItem.products || [];
                const daySales = parseFloat(dayItem.total_sales || 0);
                const dayHpp = parseFloat(dayItem.total_hpp || 0);
                const dayGross = parseFloat(
                    dayItem.total_gross_margin || daySales - dayHpp,
                );
                const dayGaji = dayGross * 0.5;
                const dayNet = dayGross - dayGaji;

                // Estimasi tinggi: header 8.5 + thead 8 + baris 6.5*n + cards 14 + gap 8
                const estH = 8.5 + 8 + products.length * 6.5 + 14 + 8;

                if (detailY + estH > maxPageH) {
                    doc.addPage();

                    doc.setFillColor(8, 35, 65);
                    doc.rect(0, 0, pageW, 16, "F");

                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(10);
                    doc.setTextColor(255, 255, 255);
                    doc.text("Detail Produk Terjual (lanjutan)", margin, 10.5);

                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(7.5);
                    doc.setTextColor(170, 210, 240);
                    doc.text(
                        `Periode: ${fmtDate(period.startDate)} — ${fmtDate(period.endDate)}`,
                        pageW - margin,
                        10.5,
                        { align: "right" },
                    );

                    detailY = 23;
                }

                // ── Header Tanggal — Clean Sleek Marine Bar ──
                doc.setFillColor(...C.navy);
                doc.roundedRect(margin, detailY, contentW, 8.5, 1.5, 1.5, "F");

                doc.setFont("helvetica", "bold");
                doc.setFontSize(8.5);
                doc.setTextColor(...C.white);
                doc.text(fmtDate(dayItem.date), margin + 4, detailY + 5.8);

                detailY += 11;

                // ── Tabel Produk per Tanggal (No 14mm, Total 269mm) ──
                const prodHead = [
                    [
                        {
                            content: "No",
                            styles: { halign: "center", cellWidth: 14 },
                        },
                        {
                            content: "Kode Produk",
                            styles: { halign: "left", cellWidth: 38 },
                        },
                        {
                            content: "Nama Produk",
                            styles: { halign: "left", cellWidth: 147 },
                        },
                        {
                            content: "Qty Terjual",
                            styles: { halign: "center", cellWidth: 35 },
                        },
                        {
                            content: "Sisa Stok",
                            styles: { halign: "center", cellWidth: 35 },
                        },
                    ],
                ];

                const prodBody = products.map((p, pi) => {
                    const sisa = parseInt(p.stock_final || 0);
                    const qty = parseInt(p.quantity || 0);

                    let sisaColor = C.seafoam;
                    if (sisa <= 0) sisaColor = C.coral;
                    else if (sisa <= 5) sisaColor = C.amber;

                    return [
                        {
                            content: (pi + 1).toString(),
                            styles: {
                                halign: "center",
                                textColor: C.textMutedDk,
                            },
                        },
                        {
                            content: p.product_code || "—",
                            styles: { halign: "left", textColor: C.ocean },
                        },
                        {
                            content: p.product_name || "—",
                            styles: {
                                halign: "left",
                                fontStyle: "bold",
                                textColor: C.textDark,
                            },
                        },
                        {
                            content: qty + " unit",
                            styles: {
                                halign: "center",
                                fontStyle: "bold",
                                textColor: C.midNavy,
                            },
                        },
                        {
                            content: fmtRp(sisa) + " unit",
                            styles: {
                                halign: "center",
                                fontStyle: "bold",
                                textColor: sisaColor,
                            },
                        },
                    ];
                });

                doc.autoTable({
                    startY: detailY,
                    head: prodHead,
                    body: prodBody,
                    margin: { left: margin, right: margin },
                    tableWidth: contentW,
                    styles: {
                        font: "helvetica",
                        fontSize: 7.5,
                        cellPadding: {
                            top: 3.2,
                            right: 2,
                            bottom: 3.2,
                            left: 2,
                        },
                        lineColor: C.borderGrid,
                        lineWidth: 0.35,
                        textColor: C.textDark,
                        fillColor: C.rowOdd,
                        overflow: "visible",
                    },
                    headStyles: {
                        fillColor: C.midNavy,
                        textColor: C.white,
                        fontStyle: "bold",
                        fontSize: 7.8,
                        cellPadding: { top: 4, right: 2, bottom: 4, left: 2 },
                        lineColor: C.borderHead,
                        lineWidth: 0.35,
                    },
                    alternateRowStyles: { fillColor: C.rowEven },
                    didParseCell: function (data) {
                        if (data.section === "body") {
                            data.cell.styles.fillColor =
                                data.row.index % 2 === 0 ? C.rowOdd : C.rowEven;
                        }
                    },
                });

                detailY = doc.lastAutoTable.finalY + 3.5;

                // ── 4 Mini-Cards Metrik Harian (Elegan & Bebas Garis Nyasar) ──
                const cardGap = 3;
                const cardW = (contentW - 3 * cardGap) / 4; // 65mm per card
                const cardH = 14;

                const summItems = [
                    {
                        label: "Total Penjualan",
                        value: "Rp " + fmtRp(daySales),
                        valColor: C.deepNavy,
                        accentColor: C.navy,
                    },
                    {
                        label: "Margin Kotor",
                        value: "Rp " + fmtRp(dayGross),
                        valColor: C.seafoam,
                        accentColor: C.seafoam,
                    },
                    {
                        label: "Gaji Karyawan",
                        value: "Rp " + fmtRp(dayGaji),
                        valColor: C.ocean,
                        accentColor: C.ocean,
                    },
                    {
                        label: "Margin Bersih",
                        value: "Rp " + fmtRp(dayNet),
                        valColor: dayNet < 0 ? C.coral : C.teal,
                        accentColor: dayNet < 0 ? C.coral : C.teal,
                    },
                ];

                summItems.forEach((s, si) => {
                    const cardX = margin + si * (cardW + cardGap);

                    // Background rounded card + clean border (Tanpa garis nyasar)
                    doc.setFillColor(...C.summaryCardBg);
                    doc.setDrawColor(...C.borderGrid);
                    doc.setLineWidth(0.35);
                    doc.roundedRect(
                        cardX,
                        detailY,
                        cardW,
                        cardH,
                        1.5,
                        1.5,
                        "FD",
                    );

                    // Mini pill aksen warna di kiri dalam kartu
                    doc.setFillColor(...s.accentColor);
                    doc.roundedRect(
                        cardX + 2,
                        detailY + 2.5,
                        2,
                        cardH - 5,
                        0.8,
                        0.8,
                        "F",
                    );

                    // Label metrik
                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(6.2);
                    doc.setTextColor(...C.textMutedDk);
                    doc.text(s.label, cardX + 6.5, detailY + 5.2);

                    // Nilai angka
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(8.5);
                    doc.setTextColor(...s.valColor);
                    doc.text(s.value, cardX + 6.5, detailY + 10.8);
                });

                detailY += cardH + 8;
            });
        }

        // ── Footer Semua Halaman ─────────────────────────────────
        const totalPages = doc.internal.getNumberOfPages();
        for (let p = 1; p <= totalPages; p++) {
            doc.setPage(p);
            addFooter(p, totalPages);
        }

        // ── Simpan File PDF ──────────────────────────────────────
        const startStr = (period.startDate || "").replace(/-/g, "");
        const endStr = (period.endDate || "").replace(/-/g, "");
        doc.save(`Ringkasan_Penjualan_${startStr}_${endStr}.pdf`);
    }

    document.addEventListener("DOMContentLoaded", function () {
        const btn = document.getElementById("btnExportPdf");
        if (btn) btn.addEventListener("click", exportPdf);
    });
})();
