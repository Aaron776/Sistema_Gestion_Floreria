// Elementos DOM
    const courtImage = document.getElementById('courtImage');
    const courtName = document.getElementById('courtName'); // Corregido: Definir courtName
    const previewImg = document.getElementById('previewImg');
    const previewText = document.getElementById('previewText');
    const fileInputText = document.getElementById('fileInputText');

    // Previsualizar imagen seleccionada
    courtImage.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            fileInputText.innerText = file.name;
            const reader = new FileReader();
            previewText.innerText = 'Cargando...';
            previewText.style.color = '#5eead4';

            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewText.innerText = '✅ Imagen seleccionada correctamente';
                previewText.style.color = '#a3e635';
            };
            reader.readAsDataURL(file);
        } else {
            fileInputText.innerText = 'Seleccionar foto de la cancha...';
            previewImg.src = 'https://via.placeholder.com/100x100?text=Foto';
            previewText.innerText = 'Vista previa de la imagen';
            previewText.style.color = '#6b95af';
        }
    });

    // Validación visual en tiempo real
    courtName.addEventListener('input', () => {
        if (courtName.value.trim() !== '' && courtName.value.trim().length < 3) {
            courtName.style.borderColor = '#f59e0b';
        } else if (courtName.value.trim() !== '') {
            courtName.style.borderColor = 'rgba(34, 211, 238, 0.6)';
        } else if (courtName.value.trim() === '') { // Añadido: Si está vacío, vuelve al color original
            courtName.style.borderColor = 'rgba(34, 211, 238, 0.3)';
        } else {
            courtName.style.borderColor = 'rgba(34, 211, 238, 0.3)';
        }
    });