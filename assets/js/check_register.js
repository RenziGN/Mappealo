
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');

    form.addEventListener('submit', function(event) {
        let valid = true;
        let msg = '';
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const confirmPassword = document.getElementById('confirmPassword').value.trim();

        if (!email) { valid = false; msg += 'El campo Email es obligatorio.\n'; }
        if (!isValidEmail(email)) { valid = false; msg += 'El formato del Email es inválido.\n'; }
        if (!password) { valid = false; msg += 'El campo Contraseña es obligatorio.\n'; }
        if (!isValidPassword(password)) { valid = false; msg += 'La contraseña debe tener al menos 5 caracteres.\n'; }
        if (!confirmPassword) { valid = false; msg += 'El campo Repetir Contraseña es obligatorio.\n'; }
        if (password !== confirmPassword) { valid = false; msg += 'Las contraseñas no coinciden.\n'; }

        if (!valid) {
            event.preventDefault();
            alert('Por favor, corrija los siguientes errores de validación:\n\n' + msg);
        }
    });
});

