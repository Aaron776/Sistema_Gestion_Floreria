 document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción desactivará al usuario del sistema. Podrás reactivarlo después si es necesario.",
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
                    this.submit();
                }
            });
        });
    });

    // Interceptor para restablecer contraseña
    document.querySelectorAll('.reset-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: '¿Restablecer Clave?',
                text: "Se generará una nueva contraseña aleatoria y se enviará automáticamente al correo electrónico del usuario.",
                icon: 'info',
                iconColor: '#7a2eff',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-paper-plane"></i> Sí, restablecer y enviar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                customClass: {
                    popup: 'swal-cyber-popup',
                    title: 'swal-cyber-title',
                    htmlContainer: 'swal-cyber-content',
                    confirmButton: 'swal-cyber-confirm-purple',
                    cancelButton: 'swal-cyber-cancel'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loader ya que el envío de correo puede tardar unos segundos
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Enviando correo de seguridad...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        },
                        customClass: {
                            popup: 'swal-cyber-popup',
                            title: 'swal-cyber-title',
                            htmlContainer: 'swal-cyber-content'
                        }
                    });
                    this.submit();
                }
            });
        });
    });