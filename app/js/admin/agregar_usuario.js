// Elementos DOM
    const userPassword = document.getElementById('userPassword');
    const userConfirmPassword = document.getElementById('userConfirmPassword');
    const userRole = document.getElementById('userRole');
    const btnSubmit = document.getElementById('btnSubmit');
    const passwordStrengthDiv = document.getElementById('passwordStrength');

    // Evaluar fortaleza de contraseña
    function checkPasswordStrength(password) {
        if (password.length === 0) {
            passwordStrengthDiv.innerHTML = '';
            return;
        }
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        let strengthText = '';
        let strengthColor = '';
        if (strength <= 1) {
            strengthText = '🔴 Débil';
            strengthColor = '#ef4444';
        } else if (strength <= 3) {
            strengthText = '🟡 Media';
            strengthColor = '#f59e0b';
        } else {
            strengthText = '🟢 Fuerte';
            strengthColor = '#10b981';
        }
        passwordStrengthDiv.innerHTML = `Fortaleza: <span style="color:${strengthColor};">${strengthText}</span>`;
    }

    // Evento para evaluar fortaleza de contraseña
    userPassword.addEventListener('input', () => {
        checkPasswordStrength(userPassword.value);
    });

    // Función para limpiar formulario
    function resetForm() {
        userName.value = '';
        userPhone.value = '';
        userEmail.value = '';
        userPassword.value = '';
        userConfirmPassword.value = '';
        userRole.value = 'recepcionista';
        passwordStrengthDiv.innerHTML = '';
    }