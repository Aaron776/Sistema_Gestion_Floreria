 // Eventos
    document.getElementById("searchInput").addEventListener("input", (e) => {
        const val = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#tableBody tr');
        rows.forEach(row => {
            const texto = row.textContent.toLowerCase();
            row.style.display = texto.includes(val) ? '' : 'none';
        });
    });




    // Gráfico de ocupación por cancha
    const ctx = document.getElementById('ocupacionChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ["Cancha Central", "Cancha Norte", "Cancha Tenis", "Pista Pádel", "Cancha Voleibol"],
            datasets: [{
                label: 'Reservas hoy',
                data: [3, 2, 1, 1, 1],
                backgroundColor: 'rgba(34, 211, 238, 0.6)',
                borderColor: '#22D3EE',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: {
                        color: '#cbd5e6',
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    backgroundColor: '#101d2b',
                    titleColor: '#90e0d0'
                }
            },
            scales: {
                y: {
                    grid: {
                        color: 'rgba(72, 187, 120, 0.15)'
                    },
                    ticks: {
                        color: '#b9d0e5',
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#b9d0e5',
                        rotation: 0,
                        font: {
                            size: 10
                        }
                    }
                }
            }
        }
    });