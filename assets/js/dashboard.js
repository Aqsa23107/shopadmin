/**
 * Dashboard Charts & Interactive Components
 * Powered by Chart.js
 */

let salesChartInstance = null;
let categoryChartInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    initSalesChart();
    initCategoryChart();

    window.addEventListener('themeChanged', () => {
        if (salesChartInstance) salesChartInstance.destroy();
        if (categoryChartInstance) categoryChartInstance.destroy();
        initSalesChart();
        initCategoryChart();
    });
});

function getThemeColors() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    return {
        textColor: isLight ? '#4b5563' : '#9ca3af',
        gridColor: isLight ? 'rgba(0, 0, 0, 0.06)' : 'rgba(255, 255, 255, 0.06)',
        tooltipBg: isLight ? '#ffffff' : '#1f2937',
        tooltipText: isLight ? '#111827' : '#f9fafb'
    };
}

function initSalesChart() {
    const canvas = document.getElementById('salesTrendChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const colors = getThemeColors();
    const ctx = canvas.getContext('2d');

    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.45)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    const labels = window.dashboardChartData?.salesLabels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const dataValues = window.dashboardChartData?.salesValues || [1250, 1890, 2340, 2100, 2900, 3420];
    const orderCounts = window.dashboardChartData?.orderCounts || [5, 8, 12, 9, 14, 18];

    salesChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Revenue ($)',
                    data: dataValues,
                    borderColor: '#6366f1',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'y'
                },
                {
                    label: 'Orders Count',
                    data: orderCounts,
                    borderColor: '#10b981',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    backgroundColor: 'transparent',
                    tension: 0.4,
                    pointRadius: 3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: colors.textColor,
                        font: { family: 'Inter', size: 12, weight: 500 },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    titleColor: colors.tooltipText,
                    bodyColor: colors.tooltipText,
                    borderColor: 'rgba(99, 102, 241, 0.3)',
                    borderWidth: 1,
                    padding: 10,
                    boxPadding: 4,
                    usePointStyle: true
                }
            },
            scales: {
                x: {
                    grid: { color: colors.gridColor },
                    ticks: { color: colors.textColor, font: { family: 'Inter' } }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: { color: colors.gridColor },
                    ticks: {
                        color: colors.textColor,
                        font: { family: 'Inter' },
                        callback: (value) => '$' + value
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: colors.textColor, font: { family: 'Inter' } }
                }
            }
        }
    });
}

function initCategoryChart() {
    const canvas = document.getElementById('categoryShareChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const colors = getThemeColors();
    const ctx = canvas.getContext('2d');

    const labels = window.dashboardChartData?.categoryLabels || ['Electronics', 'Accessories', 'Fashion', 'Home'];
    const dataValues = window.dashboardChartData?.categoryValues || [45, 25, 20, 10];

    categoryChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: dataValues,
                backgroundColor: [
                    '#6366f1',
                    '#3b82f6',
                    '#10b981',
                    '#f59e0b',
                    '#ec4899',
                    '#8b5cf6'
                ],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: colors.textColor,
                        font: { family: 'Inter', size: 11 },
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 14
                    }
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    titleColor: colors.tooltipText,
                    bodyColor: colors.tooltipText,
                    borderColor: 'rgba(99, 102, 241, 0.3)',
                    borderWidth: 1,
                    padding: 8
                }
            }
        }
    });
}
