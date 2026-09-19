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
        const monthNames = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];
        const formattedLabel = isNaN(day) ? item.date : `${day} ${monthNames[d.getMonth()]}`;

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
                        titleFont: { family: "'Poppins', sans-serif", size: 13, weight: 600 },
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
                                    label += "Rp " + Math.round(context.parsed.y).toLocaleString("id-ID");
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
                                    return "Rp " + (value / 1000000).toFixed(1) + "jt";
                                } else if (value >= 1000) {
                                    return "Rp " + (value / 1000).toFixed(0) + "rb";
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
                        data: (totalHpp === 0 && totalMargin === 0) ? [1, 1] : [totalHpp, totalMargin],
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
                        titleFont: { family: "'Poppins', sans-serif", size: 13, weight: 600 },
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
                                    label += "Rp " + Math.round(context.parsed).toLocaleString("id-ID");
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
function setFilterPreset(preset) {
    const today = new Date();
    let startDate = new Date();
    let endDate = new Date();

    const formatDate = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, "0");
        const d = String(date.getDate()).padStart(2, "0");
        return `${y}-${m}-${d}`;
    };

    if (preset === "today") {
        startDate = today;
        endDate = today;
    } else if (preset === "7days") {
        startDate = new Date(today.getTime() - 6 * 24 * 60 * 60 * 1000);
        endDate = today;
    } else if (preset === "30days") {
        startDate = new Date(today.getTime() - 29 * 24 * 60 * 60 * 1000);
        endDate = today;
    } else if (preset === "thisMonth") {
        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
        endDate = today;
    } else if (preset === "lastMonth") {
        startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        endDate = new Date(today.getFullYear(), today.getMonth(), 0);
    }

    const startInput = document.getElementById("filterStartDate");
    const endInput = document.getElementById("filterEndDate");
    const form = document.getElementById("formFilterSummary");

    if (startInput) startInput.value = formatDate(startDate);
    if (endInput) endInput.value = formatDate(endDate);
    if (form) form.submit();
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
                    emptyTable: "Tidak ada data penjualan pada periode tanggal yang dipilih.",
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
