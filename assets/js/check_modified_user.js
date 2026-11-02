
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editUserForm');

    form.addEventListener('submit', function(event) {
        let valid = true;
        let msg = '';
        const email = document.getElementById('email').value.trim();

        if (!email) { valid = false; msg += 'El campo Email es obligatorio.\n'; }
        if (!isValidEmail(email)) { valid = false; msg += 'El formato del Email es inválido.\n'; }
                
        if (!valid) {
            event.preventDefault();
            alert('Por favor, corrija los siguientes errores de validación:\n\n' + msg);
        }
    });
});