 document.querySelectorAll('.delete-block-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: '¿Eliminar Bloqueo?',
                text: "Esta acción liberará la cancha y permitirá nuevas reservas en ese horario. ¿Estás seguro?",
                icon: 'warning',
                iconColor: '#22D3EE',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                customClass: {
                    popup: 'swal-cyber-popup',
                    title: 'swal-cyber-title',
                    htmlContainer: 'swal-cyber-content',
                    confirmButton: 'swal-cyber-confirm',
                    cancelButton: 'swal-cyber-cancel'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Liberando cancha...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'swal-cyber-popup',
                            title: 'swal-cyber-title',
                            htmlContainer: 'swal-cyber-content'
                        },
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    this.submit();
                }
            });
        });
    });