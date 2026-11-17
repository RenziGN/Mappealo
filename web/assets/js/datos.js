// Conexión con Supabase: sesión, funciones de la base y búsqueda de direcciones.

(function () {
    const config = window.MAPPEALO_CONFIG || {};
    const sb = window.supabase.createClient(config.supabaseUrl, config.supabaseAnonKey, {
        auth: { persistSession: true, autoRefreshToken: true }
    });

    function mensajeDeError(error) {
        const texto = error?.message || '';
        if (/Failed to fetch|NetworkError|Load failed/i.test(texto)) {
            return 'No hay conexión. Revisá tu internet e intentá de nuevo.';
        }
        if (/JWT|token/i.test(texto)) {
            return 'Tu sesión expiró. Volvé a iniciar sesión.';
        }
        return texto || 'No se pudo completar la acción.';
    }

    async function rpc(nombre, argumentos) {
        const { data, error } = await sb.rpc(nombre, argumentos);
        if (error) {
            throw new Error(mensajeDeError(error));
        }
        return data;
    }

    // Direcciones con Nominatim (OpenStreetMap): máximo 1 consulta por segundo y caché local
    const cacheDirecciones = new Map();
    let ultimaConsulta = 0;

    async function direccion(lat, lng) {
        const clave = `${lat.toFixed(5)},${lng.toFixed(5)}`;
        if (cacheDirecciones.has(clave)) {
            return cacheDirecciones.get(clave);
        }

        const espera = 1000 - (Date.now() - ultimaConsulta);
        if (espera > 0) {
            await new Promise(resolver => setTimeout(resolver, espera));
        }
        ultimaConsulta = Date.now();

        const parametros = new URLSearchParams({
            format: 'jsonv2', lat: lat.toFixed(6), lon: lng.toFixed(6),
            zoom: '18', addressdetails: '1', 'accept-language': 'es'
        });
        const respuesta = await fetch(`https://nominatim.openstreetmap.org/reverse?${parametros}`, {
            headers: { Accept: 'application/json' }
        });
        if (!respuesta.ok) {
            return null;
        }

        const a = (await respuesta.json())?.address;
        if (!a) {
            return null;
        }

        const calle = a.road || a.pedestrian || a.footway || null;
        const localidad = a.city || a.town || a.village || a.suburb || null;
        let texto = localidad;

        if (calle) {
            texto = a.house_number ? `${calle} ${a.house_number}` : calle;
            if (localidad) texto += `, ${localidad}`;
        }

        texto = texto ? texto.slice(0, 255) : null;
        cacheDirecciones.set(clave, texto);
        if (cacheDirecciones.size > 50) {
            cacheDirecciones.delete(cacheDirecciones.keys().next().value);
        }
        return texto;
    }

    // Manda al login si no hay sesión. Devuelve la sesión.
    async function requerirSesion(rutaLogin = 'login.html') {
        const { data } = await sb.auth.getSession();
        if (!data.session) {
            window.location.replace(rutaLogin);
            return new Promise(() => {});
        }
        return data.session;
    }

    async function salir(rutaLogin = 'login.html') {
        await sb.auth.signOut();
        window.location.replace(rutaLogin);
    }

    window.mappealo = {
        sb,
        rpc,
        requerirSesion,
        salir,
        direccion,
        mensajeDeError,
        perfil: () => rpc('mi_perfil'),
        reportesMapa: () => rpc('reportes_mapa'),
        reporteDetalle: id => rpc('reporte_detalle', { p_id: Number(id) }),
        misReportes: () => rpc('mis_reportes'),
        crearReporte: datos => rpc('crear_reporte', { p: datos }),
        comentar: (id, contenido) => rpc('comentar', { p_id: Number(id), p_contenido: contenido }),
        calificar: (id, valor) => rpc('calificar', { p_id: Number(id), p_valor: Number(valor) }),
        denunciar: (id, motivo, detalle) => rpc('denunciar', { p_id: Number(id), p_motivo: motivo, p_detalle: detalle || '' }),
        borrarReporte: id => rpc('borrar_mi_reporte', { p_id: Number(id) })
    };
})();
