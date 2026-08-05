// Búsqueda en la tabla directamente desde el DOM
    document.getElementById("searchInput").addEventListener("input", (e) => {
        const val = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#tableBody tr');
        rows.forEach(row => {
            const texto = row.textContent.toLowerCase();
            row.style.display = texto.includes(val) ? '' : 'none';
        });
    });

    // Confirmación con SweetAlert2 para cancelar reserva
    document.querySelectorAll('.form-eliminar-reserva').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Previene el envío inmediato
            Swal.fire({
                title: '¿Cancelar Reserva?',
                text: "Esta acción no se puede deshacer. Perderás el turno apartado.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#4b6a88',
                confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, cancelar',
                cancelButtonText: 'Cerrar',
                background: 'rgba(18, 30, 42, 0.98)',
                color: '#eef5ff',
                backdrop: 'rgba(0, 0, 0, 0.85)'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit(); // Si confirma, envía el formulario real
                }
            });

        });
    });