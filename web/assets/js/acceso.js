// Login, registro e ingreso como invitado con Supabase Auth

(function () {
    const sb = mappealo.sb;
    const caja = document.getElementById('mensaje');

    function mostrar(texto, tipo = 'danger') {
        caja.className = `alert alert-${tipo}`;
        caja.textContent = texto;
    }

    function traducir(error) {
        const t = `${error?.code || ''} ${error?.message || ''}`;
        if (/invalid_credentials|Invalid login credentials/i.test(t)) return 'Correo electrónico o contraseña incorrectos.';
        if (/email_not_confirmed|Email not confirmed/i.test(t)) return 'Todavía no confirmaste tu correo. Revisá tu bandeja de entrada (y el spam).';
        if (/user_already_exists|already registered/i.test(t)) return 'Ya existe una cuenta con ese correo electrónico.';
        if (/weak_password|Password should/i.test(t)) return 'La contraseña debe tener al menos 8 caracteres.';
        if (/anonymous_provider_disabled|Anonymous sign-ins are disabled/i.test(t)) return 'El ingreso como invitado no está habilitado.';
        if (/rate limit|over_.*_rate_limit|429/i.test(t)) return 'Demasiados intentos. Esperá unos minutos y volvé a intentar.';
        if (/email_address_invalid|invalid format/i.test(t)) return 'El formato del correo electrónico no es válido.';
        return mappealo.mensajeDeError(error);
    }

    function esCorreo(texto) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(texto) && texto.length <= 150;
    }

    function bloquear(form, si) {
        form.querySelectorAll('button').forEach(b => { b.disabled = si; });
    }

    // Si ya hay sesión con cuenta, directo al mapa. Un invitado sí puede entrar acá para iniciar sesión o registrarse.
    sb.auth.getSession().then(({ data }) => {
        if (data.session && !data.session.user?.is_anonymous) window.location.replace('index.html');
    });

    const loginForm = document.getElementById('loginForm');
    loginForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (loginForm.sitio_web.value.trim() !== '') return;

        const email = loginForm.email.value.trim().toLowerCase();
        const password = loginForm.password.value;

        if (!email || !password) {
            mostrar('Ingresá tu correo electrónico y tu contraseña.');
            return;
        }

        bloquear(loginForm, true);
        const { error } = await sb.auth.signInWithPassword({ email, password });
        if (error) {
            mostrar(traducir(error));
            bloquear(loginForm, false);
            return;
        }
        window.location.replace('index.html');
    });

    document.getElementById('btnInvitado')?.addEventListener('click', async () => {
        bloquear(loginForm, true);

        // Si ya era invitado en este navegador, reusa esa sesión en vez de crear otra
        const { data: actual } = await sb.auth.getSession();
        if (actual.session?.user?.is_anonymous) {
            window.location.replace('index.html');
            return;
        }

        const { error } = await sb.auth.signInAnonymously();
        if (error) {
            mostrar(traducir(error));
            bloquear(loginForm, false);
            return;
        }
        window.location.replace('index.html');
    });

    const registerForm = document.getElementById('registerForm');
    registerForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (registerForm.sitio_web.value.trim() !== '') return;

        const email = registerForm.email.value.trim().toLowerCase();
        const password = registerForm.password.value;
        const errores = [];

        if (!esCorreo(email)) errores.push('El formato del correo electrónico no es válido.');
        if (password.length < 8 || password.length > 72) errores.push('La contraseña debe tener entre 8 y 72 caracteres.');
        if (password !== registerForm.confirmPassword.value) errores.push('Las contraseñas no coinciden.');

        if (errores.length) {
            mostrar(errores.join(' '));
            return;
        }

        bloquear(registerForm, true);
        const { data, error } = await sb.auth.signUp({
            email,
            password,
            options: { emailRedirectTo: new URL('index.html', window.location.href).href }
        });

        if (error) {
            mostrar(traducir(error));
            bloquear(registerForm, false);
            return;
        }

        // Supabase no da error si el correo ya existe: devuelve un usuario sin identidades
        if (data.user && Array.isArray(data.user.identities) && data.user.identities.length === 0) {
            mostrar('Ya existe una cuenta con ese correo electrónico.');
            bloquear(registerForm, false);
            return;
        }

        if (data.session) {
            window.location.replace('index.html');
            return;
        }

        registerForm.reset();
        mostrar('¡Listo! Te mandamos un correo para confirmar tu cuenta. Abrí el enlace y después iniciá sesión.', 'success');
        bloquear(registerForm, false);
    });
})();
