 // Función para confirmar eliminación con SweetAlert2
    function confirmDeleteComplex(id, nombre) {
        Swal.fire({
            title: '<span style="color: #eef5ff; font-family: \'Outfit\', sans-serif;">¿Eliminar Complejo?</span>',
            html: `<div style="color: #cbd5e6; font-size: 0.95rem;">
                    ¿Estás seguro de que deseas desactivar el complejo <b>"${nombre}"</b>?<br><br>
                    <div style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; padding: 12px; border-radius: 8px; color: #f87171; text-align: left; font-size: 0.85rem;">
                        <i class="fas fa-exclamation-triangle"></i> <b>Atención:</b> Esta acción también desactivará todas las canchas asociadas a este complejo.
                    </div>
                   </div>`,
            icon: 'warning',
            iconColor: '#ef4444',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
            confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
            cancelButtonText: 'Cancelar',
            background: 'rgba(12, 22, 32, 0.95)',
            backdrop: `rgba(0,0,0,0.8) blur(4px)`,
            customClass: {
                popup: 'swal-custom-popup',
                confirmButton: 'swal-confirm-btn',
                cancelButton: 'swal-cancel-btn'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loader antes de enviar
                Swal.fire({
                    title: 'Procesando...',
                    html: 'Desactivando complejo y dependencias...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    background: 'rgba(12, 22, 32, 0.95)',
                    color: '#eef5ff'
                });
                document.getElementById('form-delete-' + id).submit();
            }
        });
    }

    // Estilos extra para SweetAlert2 (inyectados para asegurar consistencia)
    const style = document.createElement('style');
    style.innerHTML = `
        .swal-custom-popup { border: 1px solid rgba(34, 211, 238, 0.3) !important; border-radius: 32px !important; }
        .swal-confirm-btn { border-radius: 40px !important; padding: 12px 30px !important; font-weight: 600 !important; }
        .swal-cancel-btn { border-radius: 40px !important; padding: 12px 30px !important; font-weight: 500 !important; color: #ccc !important; }
    `;
    document.head.appendChild(style);

    // Búsqueda en tabla
    document.getElementById("searchInput")?.addEventListener("input", (e) => {
        const val = e.target.value;
        const rows = document.querySelectorAll('#tableBody tr');
        rows.forEach(row => {
            const txt = row.textContent.toLowerCase();
            row.style.display = txt.includes(val.toLowerCase()) ? '' : 'none';
        });
    });