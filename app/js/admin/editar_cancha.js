 // Elementos DOM
        const courtImage = document.getElementById('courtImage');
        const previewImg = document.getElementById('previewImg');
        const previewText = document.getElementById('previewText');
        const fileInputText = document.getElementById('fileInputText');


        // Función para obtener el ícono del deporte
        function getSportIcon(tipo) {
            const icons = {
                'futbol': '⚽',
                'futbol7': '⚽',
                'futbol11': '⚽',
                'padel': '🎾',
                'tenis': '🎾',
                'basket': '🏀',
                'voley': '🏐',
                'patinaje': '🛼',
                'natacion': '🏊',
                'multiusos': '🏟️'
            };
            return icons[tipo] || '🏟️';
        }

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
                fileInputText.innerText = '<?= !empty($cancha->imagen) ? htmlspecialchars($cancha->imagen) : "Seleccionar nueva foto..." ?>';
                previewImg.src = '<?= !empty($cancha->imagen) ? "../app/fotos_canchas/" . htmlspecialchars($cancha->imagen) : "https://via.placeholder.com/100x100?text=Sin+Foto" ?>';
                previewText.innerText = '<?= !empty($cancha->imagen) ? "Imagen actual" : "Sin imagen cargada" ?>';
                previewText.style.color = '#6b95af';
            }
        });