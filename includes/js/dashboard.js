/**
 * Dashboard specific JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if the canvas elements exist
    if (document.getElementById('campaignsChart')) {
        const campaignsChartCtx = document.getElementById('campaignsChart').getContext('2d');
        new Chart(campaignsChartCtx, {
            type: 'bar',
            data: {
                labels: ['Active', 'Inactive'],
                datasets: [{
                    label: 'Campaigns',
                    data: [
                        parseInt(document.getElementById('campaignsChart').dataset.active),
                        parseInt(document.getElementById('campaignsChart').dataset.inactive)
                    ],
                    backgroundColor: [
                        '#206bc4',
                        '#d63939'
                    ],
                    borderColor: [
                        '#206bc4',
                        '#d63939'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Campaign Status',
                        font: {
                            size: 18,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('backlinksChart')) {
        const backlinksChartCtx = document.getElementById('backlinksChart').getContext('2d');
        const chartEl = document.getElementById('backlinksChart');
        new Chart(backlinksChartCtx, {
            type: 'doughnut',
            data: {
                labels: ['Alive', 'Dead', 'Pending'],
                datasets: [{
                    data: [
                        parseInt(chartEl.dataset.alive),
                        parseInt(chartEl.dataset.dead),
                        parseInt(chartEl.dataset.pending)
                    ],
                    backgroundColor: [
                        '#4299e1',
                        '#e53e3e',
                        '#f6ad55'
                    ],
                    hoverOffset: 4,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Backlink Status Distribution',
                        font: {
                            size: 18,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
}); 