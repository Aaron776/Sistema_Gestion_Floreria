// Evento de búsqueda
    document.getElementById("searchInput").addEventListener("input", (e) => {
        const val = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#tableBody tr');
        rows.forEach(row => {
            const texto = row.textContent.toLowerCase();
            row.style.display = texto.includes(val) ? '' : 'none';
        });
    });