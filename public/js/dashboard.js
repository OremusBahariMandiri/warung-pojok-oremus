/**
 * WARJOK — Homepage Dashboard Live Summary & Bar Chart JS
 * Warung Pojok Oremus | PT Oremus Bahari Mandiri
 */

document.addEventListener('DOMContentLoaded', function () {
    const chartCanvas = document.getElementById('salesBarChart');
    if (!chartCanvas || typeof Chart === 'undefined') return;

    const rawData = window.dashboardChartData || {};
    const last7Days = rawData.last7Days || [];
    const last30Days = rawData.last30Days || [];

    // Currency Formatter Helper
    const formatRp = (num) => {
        return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
    };

    // Prepare Initial Dataset (7 Days)
    const getChartPayload = (dataArray) => {
        return {
            labels: dataArray.map(item => item.label),
            datasets: [
                {
                    label: 'Total Omset Penjualan',
                    data: dataArray.map(item => item.sales),
                    backgroundColor: 'rgba(34, 197, 94, 0.85)',
                    hoverBackgroundColor: 'rgba(22, 163, 74, 1)',
                    borderColor: '#22c55e',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.65,
                    categoryPercentage: 0.7,
                },
                {
                    label: 'Margin Keuntungan',
                    data: dataArray.map(item => item.margin),
                    backgroundColor: 'rgba(59, 130, 246, 0.85)',
                    hoverBackgroundColor: 'rgba(37, 99, 235, 1)',
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.65,
                    categoryPercentage: 0.7,
                }
            ]
        };
    };

    const initialPayload = getChartPayload(last7Days);

    const salesChart = new Chart(chartCanvas, {
        type: 'bar',
        data: initialPayload,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 12,
                        boxHeight: 12,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            family: "'Poppins', sans-serif",
                            size: 11,
                            weight: '500'
                        },
                        color: '#475569'
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#ffffff',
                    bodyColor: '#e2e8f0',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        family: "'Poppins', sans-serif",
                        size: 12,
                        weight: '700'
                    },
                    bodyFont: {
                        family: "'Poppins', sans-serif",
                        size: 11
                    },
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== null) {
                                label += formatRp(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            family: "'Poppins', sans-serif",
                            size: 11
                        },
                        color: '#64748b'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            family: "'Poppins', sans-serif",
                            size: 10
                        },
                        color: '#94a3b8',
                        callback: function (value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + ' Jt';
                            }
                            if (value >= 1000) {
                                return (value / 1000).toFixed(0) + ' Rb';
                            }
                            return value;
                        }
                    }
                }
            }
        }
    });

    // Period Switcher Listener
    const selectPeriod = document.getElementById('selectSalesPeriod');
    if (selectPeriod) {
        selectPeriod.addEventListener('change', function () {
            const selectedVal = this.value;
            const targetData = selectedVal === '30days' ? last30Days : last7Days;
            const updatedPayload = getChartPayload(targetData);

            salesChart.data.labels = updatedPayload.labels;
            salesChart.data.datasets[0].data = updatedPayload.datasets[0].data;
            salesChart.data.datasets[1].data = updatedPayload.datasets[1].data;

            const chartTitleEl = document.getElementById('salesChartTitle');
            if (chartTitleEl) {
                chartTitleEl.textContent = selectedVal === '30days' 
                    ? 'Penjualan 30 Hari Terakhir' 
                    : 'Penjualan 7 Hari Terakhir';
            }

            salesChart.update();
        });
    }
});
