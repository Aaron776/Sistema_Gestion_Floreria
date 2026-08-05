// Mostrar nombre del archivo seleccionado
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            fileName.textContent = this.files[0].name;
            fileName.style.color = '#10b981';
        } else {
            fileName.textContent = 'Haz clic para subir comprobante';
            fileName.style.color = '#cbd5e6';
        }
    });

    // Mostrar/Ocultar campo de referencia según el método
    const metodoSelect = document.getElementById('metodoSelect');
    const referenciaGroup = document.getElementById('referenciaGroup');

    metodoSelect.addEventListener('change', function() {
        if (this.value === 'tarjeta' || this.value === 'transferencia') {
            referenciaGroup.style.display = 'block';
            referenciaGroup.classList.add('animate__animated', 'animate__fadeIn');
        } else {
            referenciaGroup.style.display = 'none';
        }
    });

    // Confirmación con SweetAlert2
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const monto = document.querySelector('input[name="monto"]').value;
        const metodo = document.getElementById('metodoSelect').options[document.getElementById('metodoSelect').selectedIndex].text;

        Swal.fire({
            title: '¿Registrar Pago?',
            html: `Se procesará un pago de <b>$${monto}</b> mediante <b>${metodo}</b>.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#1e293b',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Revisar',
            background: 'rgba(18, 30, 42, 0.98)',
            color: '#eef5ff',
            backdrop: 'rgba(0,0,0,0.8)'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });