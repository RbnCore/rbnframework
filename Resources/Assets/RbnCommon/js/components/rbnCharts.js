/**
 * rbnCharts.js — Native RBN Framework Visualization & Map Engine (Pure JS) 📊🗺️⚡
 * Custom dependency-free charting & interactive mapping using HTML5 Canvas & SVG.
 * Part of the RBN Framework Architecture.
 */

const RbnCharts = (function () {
    'use strict';

    /**
     * Core utility to handle high-DPI displays
     */
    function setupCanvas(canvas) {
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        return { ctx, width: rect.width, height: rect.height };
    }

    /**
     * Theme & CSS Variable Token Resolver 🎨⚡
     * Reads dynamic variables from document root (:root) or computed panel styles.
     */
    function getCssVar(varName, fallback = '') {
        const val = getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
        return val || fallback;
    }

    function getThemeTokens() {
        const primary = getCssVar('--rbn-craft-russet', getCssVar('--rbn-primary', '#c56a3c'));
        const blue = getCssVar('--rbn-craft-blue', '#1d4ed8');
        const amber = getCssVar('--rbn-craft-mustard', '#d97706');
        const green = getCssVar('--rbn-craft-sage', '#16a34a');
        const taupe = getCssVar('--rbn-craft-taupe', '#a89f91');
        const gridColor = getCssVar('--rbn-admin-border-subtle', 'rgba(120, 113, 108, 0.12)');
        const textColor = getCssVar('--rbn-admin-text-muted', '#79716b');

        return {
            primary,
            blue,
            amber,
            green,
            taupe,
            gridColor,
            textColor,
            deviceColors: [blue, primary, amber, green, taupe],
            qualityColors: [green, taupe]
        };
    }

    /**
     * Universal Floating Tooltip Helper 💬✨
     */
    let sharedTooltipEl = null;

    function getOrCreateTooltip() {
        if (!sharedTooltipEl || !document.body.contains(sharedTooltipEl)) {
            sharedTooltipEl = document.createElement('div');
            sharedTooltipEl.className = 'rbn-chart-tooltip';
            document.body.appendChild(sharedTooltipEl);
        }
        return sharedTooltipEl;
    }

    function showTooltip(x, y, title, value, badgeColor = null) {
        const tooltip = getOrCreateTooltip();
        let badgeHtml = badgeColor ? `<span class="tooltip-badge" style="background: ${badgeColor};"></span>` : '';
        tooltip.innerHTML = `
            ${title ? `<div class="tooltip-title">${title}</div>` : ''}
            <div class="tooltip-value">${badgeHtml}${value}</div>
        `;
        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
        tooltip.style.display = 'block';
    }

    function hideTooltip() {
        if (sharedTooltipEl) {
            sharedTooltipEl.style.display = 'none';
        }
    }

    return {
        /**
         * 1. Native Line Chart Implementation with Hover Tooltip 📈✨
         */
        initLineChart: function (canvasId, labels, data, options = {}) {
            const canvas = typeof canvasId === 'string' ? document.getElementById(canvasId) : canvasId;
            if (!canvas) return;

            let currentPoints = [];
            let hoveredIndex = -1;

            const render = () => {
                const { ctx, width, height } = setupCanvas(canvas);
                const padding = { top: 20, right: 20, bottom: 35, left: 45 };
                const chartWidth = width - padding.left - padding.right;
                const chartHeight = height - padding.top - padding.bottom;

                const maxVal = Math.max(...data, 10);
                const gridSteps = 5;

                ctx.clearRect(0, 0, width, height);

                // 1. Draw Grid
                ctx.strokeStyle = options.gridColor || 'rgba(120, 113, 108, 0.12)';
                ctx.lineWidth = 1;
                ctx.font = '11px "JetBrains Mono", monospace';
                ctx.fillStyle = options.textColor || '#78716c';

                for (let i = 0; i <= gridSteps; i++) {
                    const y = padding.top + chartHeight - (i * (chartHeight / gridSteps));
                    ctx.beginPath();
                    ctx.moveTo(padding.left, y);
                    ctx.lineTo(padding.left + chartWidth, y);
                    ctx.stroke();

                    const label = Math.round((maxVal / gridSteps) * i);
                    ctx.fillText(label, 10, y + 4);
                }

                // 2. Draw Labels
                labels.forEach((label, i) => {
                    const x = padding.left + (i * (chartWidth / (labels.length - 1 || 1)));
                    ctx.save();
                    ctx.translate(x, padding.top + chartHeight + 20);
                    ctx.textAlign = 'center';
                    ctx.fillText(label, 0, 0);
                    ctx.restore();
                });

                if (data.length === 0) return;

                // 3. Draw Line with Gradient
                currentPoints = data.map((val, i) => ({
                    x: padding.left + (i * (chartWidth / (labels.length - 1 || 1))),
                    y: padding.top + chartHeight - (val * (chartHeight / maxVal)),
                    val: val,
                    label: labels[i] || ''
                }));

                // Gradient Path
                const gradient = ctx.createLinearGradient(0, padding.top, 0, padding.top + chartHeight);
                gradient.addColorStop(0, options.gradientStart || 'rgba(197, 106, 60, 0.25)');
                gradient.addColorStop(1, options.gradientEnd || 'rgba(197, 106, 60, 0.01)');

                ctx.beginPath();
                ctx.moveTo(currentPoints[0].x, padding.top + chartHeight);
                currentPoints.forEach((p, i) => {
                    if (i === 0) ctx.lineTo(p.x, p.y);
                    else {
                        const cp = (currentPoints[i - 1].x + p.x) / 2;
                        ctx.bezierCurveTo(cp, currentPoints[i - 1].y, cp, p.y, p.x, p.y);
                    }
                });
                ctx.lineTo(currentPoints[currentPoints.length - 1].x, padding.top + chartHeight);
                ctx.closePath();
                ctx.fillStyle = gradient;
                ctx.fill();

                // Stroke Line
                ctx.beginPath();
                currentPoints.forEach((p, i) => {
                    if (i === 0) ctx.moveTo(p.x, p.y);
                    else {
                        const cp = (currentPoints[i - 1].x + p.x) / 2;
                        ctx.bezierCurveTo(cp, currentPoints[i - 1].y, cp, p.y, p.x, p.y);
                    }
                });
                ctx.strokeStyle = options.borderColor || '#c56a3c';
                ctx.lineWidth = 2.5;
                ctx.stroke();

                // Points Handling
                currentPoints.forEach((p, i) => {
                    const isHovered = (i === hoveredIndex);
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, isHovered ? 6 : 4, 0, Math.PI * 2);
                    ctx.fillStyle = isHovered ? (options.borderColor || '#c56a3c') : '#ffffff';
                    ctx.fill();
                    ctx.strokeStyle = options.borderColor || '#c56a3c';
                    ctx.lineWidth = isHovered ? 3 : 2;
                    ctx.stroke();
                });
            };

            // Event Listeners for Hover and Tooltip
            canvas.addEventListener('mousemove', function (e) {
                const rect = canvas.getBoundingClientRect();
                const mouseX = e.clientX - rect.left;
                const mouseY = e.clientY - rect.top;

                let foundIndex = -1;
                let minDist = 25; // Algılama yarıçapı

                currentPoints.forEach((p, i) => {
                    const dist = Math.hypot(p.x - mouseX, p.y - mouseY);
                    if (dist < minDist) {
                        foundIndex = i;
                        minDist = dist;
                    }
                });

                if (foundIndex !== -1) {
                    if (hoveredIndex !== foundIndex) {
                        hoveredIndex = foundIndex;
                        render();
                    }
                    const point = currentPoints[foundIndex];
                    showTooltip(
                        e.clientX,
                        e.clientY,
                        point.label,
                        `${point.val.toLocaleString()} Ziyaretçi`,
                        options.borderColor || '#c56a3c'
                    );
                } else if (hoveredIndex !== -1) {
                    hoveredIndex = -1;
                    hideTooltip();
                    render();
                }
            });

            canvas.addEventListener('mouseleave', function () {
                if (hoveredIndex !== -1) {
                    hoveredIndex = -1;
                    hideTooltip();
                    render();
                }
            });

            window.addEventListener('resize', render);
            render();
        },

        /**
         * 2. Native Doughnut Chart Implementation with Hover Tooltip 🍩✨
         */
        initDoughnutChart: function (canvasId, labels, data, options = {}) {
            const canvas = typeof canvasId === 'string' ? document.getElementById(canvasId) : canvasId;
            if (!canvas) return;

            let currentSlices = [];
            let hoveredIndex = -1;

            const render = () => {
                const { ctx, width, height } = setupCanvas(canvas);
                const centerX = width / 2;
                const centerY = height / 2;
                const baseRadius = Math.min(centerX, centerY) * 0.85;
                const innerRadius = baseRadius * 0.65;

                let total = data.reduce((a, b) => a + b, 0);
                if (total === 0) total = 1; // Avoid divide by zero
                let startAngle = -Math.PI / 2;

                ctx.clearRect(0, 0, width, height);
                currentSlices = [];

                data.forEach((val, i) => {
                    const sliceAngle = (val / total) * (Math.PI * 2);
                    const colors = options.colors || ['#1a365d', '#c56a3c', '#d97706', '#16a34a', '#a89f91'];
                    const isHovered = (i === hoveredIndex);
                    const radius = isHovered ? baseRadius * 1.05 : baseRadius;

                    ctx.beginPath();
                    ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                    ctx.arc(centerX, centerY, innerRadius, startAngle + sliceAngle, startAngle, true);
                    ctx.closePath();

                    ctx.fillStyle = colors[i % colors.length];
                    ctx.fill();

                    if (isHovered) {
                        ctx.strokeStyle = '#ffffff';
                        ctx.lineWidth = 2;
                        ctx.stroke();
                    }

                    currentSlices.push({
                        startAngle,
                        endAngle: startAngle + sliceAngle,
                        innerRadius,
                        radius,
                        val,
                        label: labels[i] || '',
                        color: colors[i % colors.length],
                        percentage: Math.round((val / total) * 100)
                    });

                    startAngle += sliceAngle;
                });
            };

            canvas.addEventListener('mousemove', function (e) {
                const rect = canvas.getBoundingClientRect();
                const mouseX = e.clientX - rect.left;
                const mouseY = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const dx = mouseX - centerX;
                const dy = mouseY - centerY;
                const dist = Math.hypot(dx, dy);

                let angle = Math.atan2(dy, dx);
                // Açıyı [-PI/2, 3PI/2] aralığına normalize et
                if (angle < -Math.PI / 2) {
                    angle += Math.PI * 2;
                }

                let foundIndex = -1;
                currentSlices.forEach((slice, i) => {
                    if (dist >= slice.innerRadius && dist <= slice.radius * 1.08) {
                        if (angle >= slice.startAngle && angle <= slice.endAngle) {
                            foundIndex = i;
                        }
                    }
                });

                if (foundIndex !== -1) {
                    if (hoveredIndex !== foundIndex) {
                        hoveredIndex = foundIndex;
                        render();
                    }
                    const slice = currentSlices[foundIndex];
                    showTooltip(
                        e.clientX,
                        e.clientY,
                        slice.label,
                        `${slice.val.toLocaleString()} (${slice.percentage}%)`,
                        slice.color
                    );
                } else if (hoveredIndex !== -1) {
                    hoveredIndex = -1;
                    hideTooltip();
                    render();
                }
            });

            canvas.addEventListener('mouseleave', function () {
                if (hoveredIndex !== -1) {
                    hoveredIndex = -1;
                    hideTooltip();
                    render();
                }
            });

            window.addEventListener('resize', render);
            render();
        },

        /**
         * 3. Interactive Turkey SVG Heatmap Engine 🗺️🇹🇷
         */
        initTurkeyMap: function (containerSelector = '#svg-turkiye-haritasi', dataElementId = 'webtraffic-map-data') {
            const mapEl = typeof dataElementId === 'string' ? document.getElementById(dataElementId) : dataElementId;
            const svgElement = document.querySelector(containerSelector);
            if (!mapEl || !svgElement) return;

            const cityStats = JSON.parse(mapEl.dataset.cityStats || '{}');
            const maxUsers = parseInt(mapEl.dataset.maxUsers || 1);

            // Apply visual densities on map paths
            Object.keys(cityStats).forEach(slug => {
                const group = document.getElementById(slug);
                if (group) {
                    const users = cityStats[slug];
                    const ratio = users / maxUsers;
                    let opacity = 0.20 + ratio * 0.80;
                    
                    const paths = group.querySelectorAll('path');
                    paths.forEach(p => {
                        p.style.fill = `rgba(197, 106, 60, ${opacity})`;
                    });
                    
                    group.setAttribute('data-active-users', users);
                    group.querySelectorAll('g').forEach(sub => sub.setAttribute('data-active-users', users));
                }
            });

            // Map Tooltip
            const info = document.querySelector('.ra-traffic-map-tooltip') || document.querySelector('.il-isimleri');
            if (info) {
                svgElement.addEventListener('mouseover', function (event) {
                    const path = event.target;
                    if (path.tagName === 'path') {
                        const group = path.closest('g[data-iladi]') || path.parentNode;
                        if (group && group.id !== 'guney-kibris') {
                            let cityName = group.getAttribute('data-iladi') || '';
                            if (cityName.includes(' (')) cityName = cityName.split(' (')[0];
                            const users = group.getAttribute('data-active-users') || (cityStats[group.id] ?? 0);
                            
                            info.innerHTML = `
                                <div class="tooltip-city">${cityName}</div>
                                <div class="tooltip-hits"><i class="ri-user-heart-line me-1"></i>${parseInt(users).toLocaleString()} Aktif Ziyaretçi</div>
                            `;
                            info.style.display = 'block';
                        }
                    }
                });

                svgElement.addEventListener('mousemove', function (event) {
                    const cardElement = document.querySelector('.ra-traffic-map-card') || document.querySelector('.map-card-wrapper') || svgElement;
                    if (cardElement) {
                        const cardRect = cardElement.getBoundingClientRect();
                        info.style.top = (event.clientY - cardRect.top - 15) + 'px';
                        info.style.left = (event.clientX - cardRect.left) + 'px';
                    }
                });

                svgElement.addEventListener('mouseout', function () {
                    info.style.display = 'none';
                });
            }
        },

        /**
         * 4. Evrensel Otonom DOM Tarayıcısı (Auto-Init Engine) 🤖⚡
         * Tarayıcıdaki tüm veri konteynerlarını ve [data-rbn-chart] elementlerini otomatik bulur ve çizer.
         */
        autoInit: function () {
            const tokens = getThemeTokens();

            // A. WebTraffic Dashboard Charts (index.rbn.php)
            const webtrafficDash = document.getElementById('webtraffic-data');
            if (webtrafficDash) {
                const trendLabels = JSON.parse(webtrafficDash.dataset.trendLabels || '[]');
                const trendValues = JSON.parse(webtrafficDash.dataset.trendValues || '[]');
                
                this.initLineChart('trafficTrendChart', trendLabels, trendValues, {
                    borderColor: tokens.primary,
                    gradientStart: 'rgba(197, 106, 60, 0.25)',
                    gradientEnd: 'rgba(197, 106, 60, 0.01)',
                    gridColor: tokens.gridColor,
                    textColor: tokens.textColor
                });

                const deviceData = [
                    parseInt(webtrafficDash.dataset.pc || 0),
                    parseInt(webtrafficDash.dataset.mobile || 0),
                    parseInt(webtrafficDash.dataset.tablet || 0)
                ];
                this.initDoughnutChart('deviceDistributionChart', ['PC', 'Mobil', 'Tablet'], deviceData, {
                    colors: tokens.deviceColors
                });

                const botData = [
                    parseInt(webtrafficDash.dataset.organic || 0),
                    parseInt(webtrafficDash.dataset.bot || 0)
                ];
                this.initDoughnutChart('botDistributionChart', ['Organik', 'Bot'], botData, {
                    colors: tokens.qualityColors
                });
            }

            // B. WebTraffic Report Charts (report.rbn.php)
            const webtrafficReport = document.getElementById('webtraffic-range-data');
            if (webtrafficReport) {
                const trendLabels = JSON.parse(webtrafficReport.dataset.trendLabels || '[]');
                const trendValues = JSON.parse(webtrafficReport.dataset.trendValues || '[]');
                
                this.initLineChart('rangeTrendChart', trendLabels, trendValues, {
                    borderColor: tokens.primary,
                    gradientStart: 'rgba(197, 106, 60, 0.25)',
                    gradientEnd: 'rgba(197, 106, 60, 0.01)',
                    gridColor: tokens.gridColor,
                    textColor: tokens.textColor
                });

                const deviceData = [
                    parseInt(webtrafficReport.dataset.pc || 0),
                    parseInt(webtrafficReport.dataset.mobile || 0),
                    parseInt(webtrafficReport.dataset.tablet || 0)
                ];
                this.initDoughnutChart('rangeDeviceChart', ['Masaüstü', 'Mobil', 'Tablet'], deviceData, {
                    colors: tokens.deviceColors
                });

                const botData = [
                    parseInt(webtrafficReport.dataset.organic || 0),
                    parseInt(webtrafficReport.dataset.bot || 0)
                ];
                this.initDoughnutChart('rangeBotChart', ['Organik Trafik', 'Bot Ziyaret'], botData, {
                    colors: tokens.qualityColors
                });
            }

            // C. Turkey Interactive Heatmap (google_map.rbn.php)
            if (document.getElementById('webtraffic-map-data')) {
                this.initTurkeyMap();
            }

            // D. Declarative HTML Element Charts ([data-rbn-chart])
            document.querySelectorAll('[data-rbn-chart]').forEach(el => {
                const type = el.getAttribute('data-rbn-chart');
                const labels = JSON.parse(el.getAttribute('data-labels') || '[]');
                const values = JSON.parse(el.getAttribute('data-values') || '[]');
                const options = JSON.parse(el.getAttribute('data-options') || '{}');

                if (type === 'line') {
                    this.initLineChart(el, labels, values, options);
                } else if (type === 'doughnut' || type === 'donut') {
                    this.initDoughnutChart(el, labels, values, options);
                }
            });
        }
    };
})();

// Evrensel Quick Range Tarih Filtresi Yardımcısı
window.setQuickRange = function (days) {
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    if (!startInput || !endInput) return;
    
    const today = new Date();
    const endDateStr = today.toISOString().split('T')[0];
    
    let startDateStr;
    if (days === 0) {
        startDateStr = endDateStr;
    } else {
        const start = new Date();
        start.setDate(today.getDate() - (days - 1));
        startDateStr = start.toISOString().split('T')[0];
    }
    
    startInput.value = startDateStr;
    endInput.value = endDateStr;
    
    const form = document.getElementById('report-filter-form');
    if (form) form.submit();
};

window.RbnCharts = RbnCharts;

// DOM yüklendiğinde otomatik çalıştır
if (typeof rbnReady === 'function') {
    rbnReady(function () {
        RbnCharts.autoInit();
    });
} else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        RbnCharts.autoInit();
    });
} else {
    RbnCharts.autoInit();
}
