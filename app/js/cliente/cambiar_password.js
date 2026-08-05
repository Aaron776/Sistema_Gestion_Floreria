// Elementos DOM
    const currentPassword = document.getElementById('currentPassword');
    const newPassword = document.getElementById('newPassword');
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
            strengthText = '🔴 Débil - La contraseña es muy fácil de adivinar';
            strengthColor = '#ef4444';
        } else if (strength <= 3) {
            strengthText = '🟡 Media - Mejora añadiendo mayúsculas y números';
            strengthColor = '#f59e0b';
        } else {
            strengthText = '🟢 Fuerte - Excelente contraseña';
            strengthColor = '#10b981';
        }
        passwordStrengthDiv.innerHTML = `Fortaleza: <span style="color:${strengthColor};">${strengthText}</span>`;
    }

    // Mostrar/ocultar contraseña
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Validar fortaleza en tiempo real
    newPassword.addEventListener('input', () => {
        checkPasswordStrength(newPassword.value);
    });