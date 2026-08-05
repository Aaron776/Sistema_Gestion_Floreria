// Elementos DOM
    const compName = document.getElementById('compName');
    const compImage = document.getElementById('compImage');
    const compAddress = document.getElementById('compAddress');
    const compDesc = document.getElementById('compDesc');
    const compPhone = document.getElementById('compPhone');
    const btnSubmit = document.getElementById('btnSubmit');
    const fileInputText = document.getElementById('fileInputText');
    const errorDiv = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const successDiv = document.getElementById('successMessage');
    const successText = document.getElementById('successText');
    const previewImg = document.getElementById('previewImg');
    const previewText = document.getElementById('previewText');

    // Previsualizar imagen seleccionada
    compImage.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            fileInputText.innerText = file.name; // Show file name
            const reader = new FileReader();
            previewText.innerText = 'Cargando...';
            previewText.style.color = '#5eead4';

            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewText.innerText = 'Imagen seleccionada correctamente';
                previewText.style.color = '#a3e635';
            };
            reader.readAsDataURL(file);
        } else {
            fileInputText.innerText = 'Seleccionar imagen...'; // Reset text
            previewImg.src = 'https://via.placeholder.com/60x60?text=Foto';
            previewText.innerText = 'Selecciona una foto para el complejo';
            previewText.style.color = '#6b95af';
        }
    });

    // Función para limpiar formulario
    function resetForm() {
        compName.value = '';
        compImage.value = '';
        compAddress.value = '';
        compDesc.value = '';
        compPhone.value = '';
        updateImagePreview();
    }