 // Eventos
    document.getElementById("searchInput").addEventListener("input", (e) => renderTable(e.target.value));
    document.getElementById("btnNuevaCancha").addEventListener("click", openNewModal);
    document.getElementById("saveCourt").addEventListener("click", saveCourtHandler);
    document.getElementById("cancelModal").addEventListener("click", closeModal);
    document.getElementById("courtImage").addEventListener("input", updateModalPreview);
    function confirmDeleteCourt(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            html: `La cancha <b style="color: #22d3ee;">"${nombre}"</b> será desactivada y no podrá recibir nuevas reservas.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar',
            background: 'rgba(12, 22, 32, 0.95)',
            color: '#eef5ff',
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
                    html: 'Desactivando cancha y actualizando disponibilidad...',
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
    const styleSwal = document.createElement('style');
    styleSwal.innerHTML = `
        .swal-custom-popup { border: 1px solid rgba(34, 211, 238, 0.3) !important; border-radius: 32px !important; }
        .swal-confirm-btn { border-radius: 40px !important; padding: 12px 30px !important; font-weight: 600 !important; }
        .swal-cancel-btn { border-radius: 40px !important; padding: 12px 30px !important; font-weight: 500 !important; color: #ccc !important; }
    `;
    document.head.appendChild(styleSwal);