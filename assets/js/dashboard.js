/**
 * Ironwall v5.0 — Dashboard Analytics
 *
 * Dark-theme Chart.js visualizations for security events and traffic.
 */
jQuery(document).ready(function($) {
    const ctxEvents  = document.getElementById('wsg-events-chart');
    const ctxTraffic = document.getElementById('wsg-traffic-chart');

    if (!ctxEvents || !ctxTraffic) return;

    // Dark-theme defaults.
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Fetch Stats via AJAX.
    $.post(irw_data.ajax_url, {
        action: 'irw_fetch_stats',
        nonce: irw_data.nonce
    }, function(response) {
        if (response.success) {
            renderEventsChart(response.data.events);
            renderTrafficChart(response.data.traffic);
        }
    });

    function renderEventsChart(data) {
        if (!data || data.length === 0) {
            data = [];
            for (let i = 6; i >= 0; i--) {
                let d = new Date();
                d.setDate(d.getDate() - i);
                data.push({ date: d.toISOString().split('T')[0], count: 0 });
            }
        }

        const labels = data.map(item => item.date);
        const counts = data.map(item => item.count);

        const gradient = ctxEvents.getContext('2d').createLinearGradient(0, 0, 0, 220);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.25)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        new Chart(ctxEvents, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Security Events',
                    data: counts,
                    borderColor: '#818cf8',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.45,
                    pointRadius: 4,
                    pointBackgroundColor: '#818cf8',
                    pointBorderColor: '#0b0f19',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#a78bfa',
                    pointHoverBorderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        borderColor: 'rgba(148, 163, 184, 0.1)',
                        borderWidth: 1,
                        titleColor: '#e2e8f0',
                        bodyColor: '#94a3b8',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { weight: 600 },
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: 5,
                        border: { display: false },
                        grid: { color: 'rgba(148, 163, 184, 0.06)', drawTicks: false },
                        ticks: {
                            padding: 12,
                            font: { size: 11 },
                            stepSize: 1
                        }
                    },
                    x: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: {
                            padding: 8,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }

    function renderTrafficChart(data) {
        let chartData = [data.humans, data.bots];
        let bgColors = ['rgba(52, 211, 153, 0.85)', 'rgba(248, 113, 113, 0.85)'];
        let borderColors = ['rgba(52, 211, 153, 0.2)', 'rgba(248, 113, 113, 0.2)'];
        
        if (data.humans === 0 && data.bots === 0) {
            chartData = [1]; // Fake single segment
            bgColors = ['rgba(148, 163, 184, 0.15)'];
            borderColors = ['rgba(148, 163, 184, 0.05)'];
        }

        new Chart(ctxTraffic, {
            type: 'doughnut',
            data: {
                labels: ['Humans', 'Bots'],
                datasets: [{
                    data: chartData,
                    backgroundColor: bgColors,
                    borderColor: borderColors,
                    borderWidth: 2,
                    hoverOffset: 8,
                    hoverBorderWidth: 0,
                    spacing: 3,
                    borderRadius: 4
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
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 24,
                            font: { size: 12, weight: 500 },
                            color: '#94a3b8'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        borderColor: 'rgba(148, 163, 184, 0.1)',
                        borderWidth: 1,
                        titleColor: '#e2e8f0',
                        bodyColor: '#94a3b8',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true
                    }
                }
            }
        });
    }
});
