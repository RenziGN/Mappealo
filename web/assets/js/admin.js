// Panel de administración: cada pestaña pide sus datos a Supabase y se dibuja acá.

(function () {
    const contenido = document.getElementById('contenido');
    const mensajes = document.getElementById('mensajes');

    // ---------- Ayudantes de formato ----------
    const e = texto => String(texto ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function fecha(texto) {
        if (!texto) return '';
        const [d, h] = String(texto).split(' ');
        const [a, m, dia] = d.split('-');
        return `${dia}/${m}/${a} ${(h || '').slice(0, 5)}`;
    }

    function estrellas(valor) {
        const n = Math.max(0, Math.min(5, Math.round(Number(valor) || 0)));
        return '★'.repeat(n) + '☆'.repeat(5 - n);
    }

    function nivel(puntaje) {
        if (puntaje >= 70) return { clave: 'alta', texto: 'Alta' };
        if (puntaje >= 40) return { clave: 'media', texto: 'Media' };
        return { clave: 'baja', texto: 'Baja' };
    }

    function credibilidad(cred, conTexto = true) {
        if (cred === null || cred === undefined) return '<span class="text-muted small">-</span>';
        if (typeof cred === 'number') cred = { puntaje: cred, nivel: nivel(cred) };
        const clase = { alta: 'text-bg-success', media: 'text-bg-warning', baja: 'text-bg-danger' }[cred.nivel.clave] || 'text-bg-secondary';
        return `<span class="badge ${clase}" title="Índice de credibilidad (0 a 100)"><i class="fa-solid fa-shield-halved me-1"></i>${conTexto ? e(cred.nivel.texto) + ' · ' : ''}${parseInt(cred.puntaje, 10)}</span>`;
    }

    function estado(valor) {
        const datos = {
            verificado: ['text-bg-success', 'fa-circle-check', 'Verificado'],
            rechazado: ['text-bg-danger', 'fa-ban', 'Rechazado'],
            pendiente: ['text-bg-warning', 'fa-hourglass-half', 'Pendiente']
        };
        const [clase, icono, texto] = datos[String(valor).toLowerCase()] || datos.pendiente;
        return `<span class="badge ${clase}"><i class="fa-solid ${icono} me-1"></i>${texto}</span>`;
    }

    const categoriaTexto = c => (c || '').charAt(0).toUpperCase() + (c || '').slice(1);

    // Botón que dispara una acción de moderación (pide confirmación)
    function accion(nombre, id, html, clase, confirmacion, extra = '') {
        return `<button type="button" class="${clase}" data-accion="${e(nombre)}" data-id="${e(id)}" data-extra="${e(extra)}" data-confirmar="${e(confirmacion)}">${html}</button>`;
    }

    function mostrarMensaje(texto, tipo = 'success') {
        mensajes.innerHTML = `<div class="alert alert-${tipo === 'success' ? 'success' : 'danger'}">${e(texto)}</div>`;
        mensajes.scrollIntoView({ block: 'nearest' });
    }

    // ---------- Pestaña Reportes ----------
    const FILTROS = { todos: 'Todos', pendientes: 'Pendientes', denunciados: 'Con denuncias', baja_credibilidad: 'Baja credibilidad', delito: 'Delitos', comunitario: 'Comunitarios', rechazados: 'Rechazados' };
    let filtroActual = 'todos';
    let busquedaActual = '';

    async function vistaReportes() {
        const todos = await mappealo.rpc('admin_reportes');
        const q = busquedaActual.toLowerCase();

        let lista = todos.filter(r => {
            if (filtroActual === 'pendientes' && r.estado !== 'pendiente') return false;
            if (filtroActual === 'rechazados' && r.estado !== 'rechazado') return false;
            if (filtroActual === 'denunciados' && !(r.cantidad_denuncias > 0)) return false;
            if (filtroActual === 'baja_credibilidad' && r.credibilidad_nivel !== 'baja') return false;
            if (filtroActual === 'delito' && r.categoria !== 'delito') return false;
            if (filtroActual === 'comunitario' && r.categoria !== 'comunitario') return false;
            if (q && ![r.descripcion, r.nombre_usuario, r.tipo, r.direccion].some(t => String(t || '').toLowerCase().includes(q))) return false;
            return true;
        });
        lista.sort((a, b) => (b.cantidad_denuncias - a.cantidad_denuncias) || String(b.fecha_reporte).localeCompare(String(a.fecha_reporte)));

        const stats = {
            total: todos.length,
            denunciados: todos.filter(r => r.cantidad_denuncias > 0).length,
            baja: todos.filter(r => r.credibilidad_nivel === 'baja').length,
            sinCalificar: todos.filter(r => !(r.cantidad_calificaciones > 0)).length
        };

        contenido.innerHTML = `
            <form class="row g-2 align-items-center mb-3" id="formBusqueda">
                <div class="col-12 col-md-auto">
                    <div class="btn-group flex-wrap" role="group">
                        ${Object.entries(FILTROS).map(([clave, texto]) => `<button type="button" data-filtro="${clave}" class="btn btn-sm ${filtroActual === clave ? 'btn-dark' : 'btn-outline-dark'}">${texto}</button>`).join('')}
                    </div>
                </div>
                <div class="col">
                    <input type="search" name="q" value="${e(busquedaActual)}" class="form-control form-control-sm" placeholder="Buscar por descripción, usuario, tipo de reporte o dirección">
                </div>
                <div class="col-auto"><button class="btn btn-sm btn-primary">Buscar</button></div>
            </form>

            <div class="admin-stats">
                <button type="button" data-filtro="todos" class="admin-stat"><span class="admin-stat-icono azul"><i class="fa-solid fa-map-location-dot"></i></span><span><strong>${stats.total}</strong><small>Reportes</small></span></button>
                <button type="button" data-filtro="denunciados" class="admin-stat"><span class="admin-stat-icono rojo"><i class="fa-solid fa-flag"></i></span><span><strong>${stats.denunciados}</strong><small>Con denuncias</small></span></button>
                <button type="button" data-filtro="baja_credibilidad" class="admin-stat"><span class="admin-stat-icono naranja"><i class="fa-solid fa-shield-halved"></i></span><span><strong>${stats.baja}</strong><small>Baja credibilidad</small></span></button>
                <div class="admin-stat"><span class="admin-stat-icono gris"><i class="fa-regular fa-star"></i></span><span><strong>${stats.sinCalificar}</strong><small>Sin calificar</small></span></div>
            </div>

            <p class="text-muted small mb-2">${lista.length} reporte(s) en esta vista. Los que tienen denuncias aparecen primero.</p>

            <div class="table-responsive">
                <table class="table table-hover align-middle admin-tabla-reportes">
                    <thead><tr><th>Reporte</th><th>Autor</th><th class="text-center">Credibilidad</th><th class="text-center">Calificación</th><th class="text-center">Actividad</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    ${lista.length ? '' : '<tr><td colspan="6" class="text-center text-muted py-4">No hay reportes para mostrar.</td></tr>'}
                    ${lista.map(r => `
                        <tr class="${r.cantidad_denuncias > 0 ? 'fila-denunciada' : ''}">
                            <td class="celda-reporte">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="admin-id">#${parseInt(r.id_reporte, 10)}</span>
                                    ${estado(r.estado)}
                                    <span class="badge ${r.categoria === 'delito' ? 'text-bg-danger' : 'text-bg-success'}">${e(categoriaTexto(r.categoria))}</span>
                                </div>
                                <strong class="d-block">${e(r.tipo || '-')}</strong>
                                <div class="small text-muted text-truncate" title="${e(r.descripcion)}">${e(r.descripcion)}</div>
                            </td>
                            <td class="small text-nowrap"><span class="fw-semibold">${e(r.nombre_usuario)}</span>${r.es_anonimo ? ' <span class="badge text-bg-secondary" title="El público ve este reporte como anónimo"><i class="fa-solid fa-user-secret"></i> Anónimo</span>' : ''}<br><span class="text-muted">${fecha(r.fecha_reporte)}</span></td>
                            <td class="text-center">${credibilidad(r.credibilidad)}</td>
                            <td class="text-center text-nowrap">${r.cantidad_calificaciones > 0
                                ? `<span class="text-warning">${estrellas(r.promedio_calificacion)}</span><div class="small text-muted">${e(r.promedio_calificacion)} (${parseInt(r.cantidad_calificaciones, 10)})</div>`
                                : '<span class="small text-muted">Sin calificar</span>'}</td>
                            <td class="text-center text-nowrap">
                                <span class="admin-chip" title="Comentarios"><i class="fa-regular fa-comment"></i> ${parseInt(r.cantidad_comentarios, 10)}</span>
                                <span class="admin-chip ${r.cantidad_denuncias > 0 ? 'rojo' : ''}" title="Denuncias"><i class="fa-solid fa-flag"></i> ${parseInt(r.cantidad_denuncias, 10)}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="#reporte/${parseInt(r.id_reporte, 10)}" class="btn btn-sm btn-outline-primary btn-icono" title="Ver detalle" aria-label="Ver detalle del reporte #${parseInt(r.id_reporte, 10)}"><i class="fa-solid fa-eye"></i></a>
                                ${accion('borrar_reporte', r.id_reporte, '<i class="fa-solid fa-trash"></i>', 'btn btn-sm btn-outline-danger btn-icono',
                                    `¿Borrar el reporte #${r.id_reporte}? También se borran sus comentarios, calificaciones y denuncias. No se puede deshacer.`)}
                            </td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>`;

        contenido.querySelectorAll('[data-filtro]').forEach(b => b.addEventListener('click', () => {
            filtroActual = b.dataset.filtro;
            vistaReportes();
        }));
        contenido.querySelector('#formBusqueda').addEventListener('submit', ev => {
            ev.preventDefault();
            busquedaActual = ev.currentTarget.q.value.trim();
            vistaReportes();
        });
    }

    // ---------- Detalle de un reporte ----------
    async function vistaReporte(id) {
        const d = await mappealo.rpc('admin_reporte', { p_id: Number(id) });
        const r = d.reporte;
        const c = d.credibilidad || { factores: [], puntaje: 0 };
        const siNo = v => v === null || v === undefined ? '-' : (v ? 'Sí' : 'No');
        const promedio = d.calificaciones.length ? Math.round(d.calificaciones.reduce((s, v) => s + v.valor, 0) / d.calificaciones.length * 10) / 10 : null;
        const est = String(r.estado).toLowerCase();

        contenido.innerHTML = `
            <a href="#reportes" class="small text-decoration-none">&larr; Volver a reportes</a>

            <div class="card shadow-sm border-0 my-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <span class="badge ${r.categoria === 'delito' ? 'text-bg-danger' : 'text-bg-success'}">${e(categoriaTexto(r.categoria))}</span>
                            <h2 class="h4 mt-1 mb-0">#${parseInt(r.id_reporte, 10)} · ${e(r.tipo || '-')}</h2>
                        </div>
                        <div>${accion('borrar_reporte', r.id_reporte, 'Borrar reporte', 'btn btn-danger',
                            `¿Borrar el reporte #${r.id_reporte}? También se borran sus comentarios, calificaciones y denuncias. No se puede deshacer.`)}</div>
                    </div>
                    <div class="admin-revision mt-3">
                        <div><span class="small text-muted d-block">Revisión del reporte</span>${estado(est)}</div>
                        <div class="d-flex flex-wrap gap-2">
                            ${est !== 'verificado' ? accion('cambiar_estado', r.id_reporte, '<i class="fa-solid fa-circle-check"></i> Verificar', 'btn btn-sm btn-success', `¿Marcar el reporte #${r.id_reporte} como verificado?`, 'verificado') : ''}
                            ${est !== 'rechazado' ? accion('cambiar_estado', r.id_reporte, '<i class="fa-solid fa-ban"></i> Rechazar', 'btn btn-sm btn-outline-danger', `¿Rechazar el reporte #${r.id_reporte}? Deja de mostrarse en el mapa, pero no se borra.`, 'rechazado') : ''}
                            ${est !== 'pendiente' ? accion('cambiar_estado', r.id_reporte, '<i class="fa-solid fa-rotate-left"></i> Volver a pendiente', 'btn btn-sm btn-outline-primary', `¿Volver a poner el reporte #${r.id_reporte} como pendiente?`, 'pendiente') : ''}
                        </div>
                    </div>
                    <p class="mt-3 mb-3" style="white-space: pre-line;">${e(r.descripcion)}</p>
                    <dl class="row small mb-0">
                        <dt class="col-sm-3">Publicado por</dt>
                        <dd class="col-sm-9">${e(r.nombre_usuario)} ${r.email ? `<span class="text-muted">(${e(r.email)})</span>` : ''}${r.es_anonimo ? ' <span class="badge text-bg-secondary"><i class="fa-solid fa-user-secret"></i> Publicado como anónimo</span>' : ''}</dd>
                        <dt class="col-sm-3">Fecha del incidente</dt><dd class="col-sm-9">${fecha(r.fecha_incidente)}</dd>
                        <dt class="col-sm-3">Fecha de publicación</dt><dd class="col-sm-9">${fecha(r.fecha_reporte)}</dd>
                        <dt class="col-sm-3">Ubicación</dt>
                        <dd class="col-sm-9">${e(r.direccion)}
                            <a href="https://www.openstreetmap.org/?mlat=${Number(r.latitud)}&mlon=${Number(r.longitud)}#map=18/${Number(r.latitud)}/${Number(r.longitud)}" target="_blank" rel="noopener">(ver en el mapa)</a></dd>
                        ${r.categoria === 'delito' ? `<dt class="col-sm-3">Gravedad</dt><dd class="col-sm-9">Violencia: ${siNo(r.hubo_violencia)} · Arma de fuego: ${siNo(r.hubo_arma)} · 2 o más delincuentes: ${siNo(r.multiples_delincuentes)}</dd>` : ''}
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <strong>Índice de credibilidad</strong>${credibilidad(d.credibilidad)}
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <div class="small text-muted mb-1">Cómo se calcula para este reporte</div>
                            <ul class="list-unstyled small mb-0">
                                ${(c.factores || []).map(f => `
                                    <li class="d-flex justify-content-between border-bottom py-1">
                                        <span>${e(f.texto)}</span>
                                        <strong class="${f.base ? '' : (f.puntos > 0 ? 'text-success' : 'text-danger')}">${f.base ? '' : (f.puntos > 0 ? '+' : '')}${parseInt(f.puntos, 10)}</strong>
                                    </li>`).join('')}
                                <li class="d-flex justify-content-between pt-1"><span>Total (entre 0 y 100)</span><strong>${parseInt(c.puntaje || 0, 10)}</strong></li>
                            </ul>
                        </div>
                        <div class="col-md-5">
                            <div class="small text-muted mb-1">Credibilidad actual del autor</div>
                            ${d.credibilidad_autor
                                ? `${credibilidad(d.credibilidad_autor)}<div class="small text-muted mt-1">${e(r.nombre_usuario)}: es el punto de partida de sus próximos reportes.</div>`
                                : '<span class="small text-muted">Invitado (anónimo): base fija de 25.</span>'}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3 ${d.denuncias.length ? 'border-start border-danger border-4' : ''}">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <strong>Denuncias (${d.denuncias.length})</strong>
                    ${d.denuncias.length ? accion('descartar_denuncias', r.id_reporte, 'Descartar todas (el reporte es válido)', 'btn btn-sm btn-outline-secondary', `¿Descartar las ${d.denuncias.length} denuncia(s)? El reporte sigue publicado.`) : ''}
                </div>
                <ul class="list-group list-group-flush">
                    ${d.denuncias.length ? '' : '<li class="list-group-item text-muted small">Este reporte no tiene denuncias.</li>'}
                    ${d.denuncias.map(x => `
                        <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                            <div><div>${e(x.motivo)}</div><div class="small text-muted">${e(x.nombre_usuario)}${x.email ? ` (${e(x.email)})` : ''} · ${fecha(x.fecha)}</div></div>
                            ${accion('borrar_denuncia', x.id_advertencia, 'Descartar', 'btn btn-sm btn-outline-secondary', '¿Descartar esta denuncia?')}
                        </li>`).join('')}
                </ul>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white"><strong>Comentarios (${d.comentarios.length})</strong></div>
                        <ul class="list-group list-group-flush">
                            ${d.comentarios.length ? '' : '<li class="list-group-item text-muted small">Sin comentarios.</li>'}
                            ${d.comentarios.map(x => `
                                <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="small"><strong>${e(x.nombre_usuario)}</strong> <span class="text-muted">· ${fecha(x.fecha)}</span></div>
                                        <div style="white-space: pre-line; word-break: break-word;">${e(x.contenido)}</div>
                                    </div>
                                    ${accion('borrar_comentario', x.id_comentario, 'Borrar', 'btn btn-sm btn-outline-danger', '¿Borrar este comentario? No se puede deshacer.')}
                                </li>`).join('')}
                        </ul>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white">
                            <strong>Calificaciones (${d.calificaciones.length})</strong>
                            ${promedio !== null ? `<span class="ms-2 text-warning">${estrellas(promedio)}</span> <span class="small text-muted">${promedio}</span>` : ''}
                        </div>
                        <ul class="list-group list-group-flush">
                            ${d.calificaciones.length ? '' : '<li class="list-group-item text-muted small">Sin calificaciones.</li>'}
                            ${d.calificaciones.map(v => `
                                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                    <div><span class="text-warning">${estrellas(v.valor)}</span><div class="small text-muted">${e(v.nombre_usuario)} · ${fecha(v.fecha)}</div></div>
                                    ${accion('borrar_calificacion', v.id_valoracion, 'Borrar', 'btn btn-sm btn-outline-danger', '¿Borrar esta calificación?')}
                                </li>`).join('')}
                        </ul>
                    </div>
                </div>
            </div>`;
    }

    // ---------- Denuncias ----------
    async function vistaDenuncias() {
        const filas = await mappealo.rpc('admin_denuncias');
        const porReporte = new Map();
        filas.forEach(f => {
            if (!porReporte.has(f.id_reporte)) {
                porReporte.set(f.id_reporte, { id: f.id_reporte, descripcion: f.descripcion, autor: f.autor_reporte, categoria: f.categoria, tipo: f.tipo, ultima: f.fecha, denuncias: [] });
            }
            porReporte.get(f.id_reporte).denuncias.push(f);
        });
        const grupos = [...porReporte.values()].sort((a, b) => (b.denuncias.length - a.denuncias.length) || String(b.ultima).localeCompare(String(a.ultima)));

        contenido.innerHTML = `
            <p class="text-muted small">
                ${grupos.length} reporte(s) denunciado(s), con ${filas.length} denuncia(s) en total.
                Para cada uno podés <strong>borrar el reporte</strong> si la denuncia es correcta, o <strong>descartar las denuncias</strong> si el reporte es válido.
            </p>
            ${grupos.length ? '' : '<div class="alert alert-success">No hay denuncias pendientes. 🎉</div>'}
            ${grupos.map(rep => `
                <div class="card shadow-sm border-0 border-start border-danger border-4 mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div>
                                <span class="badge text-bg-danger">${rep.denuncias.length} denuncia(s)</span>
                                <span class="badge ${rep.categoria === 'delito' ? 'text-bg-dark' : 'text-bg-success'}">${e(categoriaTexto(rep.categoria))}</span>
                                <h2 class="h6 mt-2 mb-1">#${parseInt(rep.id, 10)} · ${e(rep.tipo)} <span class="text-muted fw-normal small">por ${e(rep.autor)}</span></h2>
                                <p class="small mb-0" style="white-space: pre-line;">${e(rep.descripcion)}</p>
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="#reporte/${parseInt(rep.id, 10)}" class="btn btn-sm btn-outline-primary">Ver detalle</a>
                                ${accion('descartar_denuncias', rep.id, 'Descartar denuncias', 'btn btn-sm btn-outline-secondary', `¿Descartar las denuncias del reporte #${rep.id}? El reporte sigue publicado.`)}
                                ${accion('borrar_reporte', rep.id, 'Borrar reporte', 'btn btn-sm btn-danger', `¿Borrar el reporte #${rep.id}? También se borran sus comentarios, calificaciones y denuncias. No se puede deshacer.`)}
                            </div>
                        </div>
                        <ul class="list-unstyled small mt-3 mb-0 border-top pt-2">
                            ${rep.denuncias.map(d => `<li class="mb-1"><i class="text-danger">⚑</i> ${e(d.motivo)} <span class="text-muted">— ${e(d.nombre_usuario)}, ${fecha(d.fecha)}</span></li>`).join('')}
                        </ul>
                    </div>
                </div>`).join('')}`;
    }

    // ---------- Comentarios ----------
    let busquedaComentarios = '';

    async function vistaComentarios() {
        const todos = await mappealo.rpc('admin_comentarios');
        const q = busquedaComentarios.toLowerCase();
        const lista = q ? todos.filter(c => `${c.contenido} ${c.nombre_usuario}`.toLowerCase().includes(q)) : todos;

        contenido.innerHTML = `
            <form class="d-flex gap-2 mb-3" id="formComentarios">
                <input type="search" name="q" value="${e(busquedaComentarios)}" class="form-control form-control-sm" placeholder="Buscar por texto o usuario">
                <button class="btn btn-sm btn-primary">Buscar</button>
            </form>
            <p class="text-muted small">${lista.length} comentario(s), los más recientes primero.</p>
            <div class="table-responsive bg-white rounded shadow-sm">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Comentario</th><th>Usuario</th><th>Reporte</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    ${lista.length ? '' : '<tr><td colspan="4" class="text-center text-muted py-4">No hay comentarios.</td></tr>'}
                    ${lista.map(c => `
                        <tr>
                            <td style="min-width: 260px; white-space: pre-line; word-break: break-word;">${e(c.contenido)}</td>
                            <td class="small text-nowrap">${e(c.nombre_usuario)}<br><span class="text-muted">${fecha(c.fecha)}</span></td>
                            <td class="small"><a href="#reporte/${parseInt(c.id_reporte, 10)}">#${parseInt(c.id_reporte, 10)}</a>
                                <div class="text-muted text-truncate" style="max-width: 220px;">${e(c.descripcion)}</div></td>
                            <td class="text-end">${accion('borrar_comentario', c.id_comentario, 'Borrar', 'btn btn-sm btn-outline-danger', '¿Borrar este comentario? No se puede deshacer.')}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>`;

        contenido.querySelector('#formComentarios').addEventListener('submit', ev => {
            ev.preventDefault();
            busquedaComentarios = ev.currentTarget.q.value.trim();
            vistaComentarios();
        });
    }

    // ---------- Calificaciones ----------
    async function vistaCalificaciones() {
        const d = await mappealo.rpc('admin_calificaciones');
        const res = d.resumen;
        const total = parseInt(res.total, 10) || 0;

        contenido.innerHTML = `
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card shadow-sm border-0 h-100"><div class="card-body">
                    <div class="text-muted small">Promedio general</div>
                    <div class="display-6">${total ? e(res.promedio) : '-'}</div>
                    <div class="text-warning">${total ? estrellas(res.promedio) : ''}</div>
                    <div class="small text-muted">${total} calificación(es)</div>
                </div></div></div>
                <div class="col-md-4"><div class="card shadow-sm border-0 h-100"><div class="card-body">
                    <div class="text-muted small mb-2">Distribución</div>
                    ${[5, 4, 3, 2, 1].map(n => {
                        const cantidad = parseInt(res['v' + n], 10) || 0;
                        const porcentaje = total ? Math.round(cantidad * 100 / total) : 0;
                        return `<div class="d-flex align-items-center gap-2 small mb-1">
                            <span class="text-nowrap" style="width: 2.5rem;">${n} ★</span>
                            <div class="progress flex-grow-1" style="height: 8px;" role="progressbar" aria-valuenow="${porcentaje}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-warning" style="width: ${porcentaje}%"></div></div>
                            <span class="text-muted" style="width: 2rem;">${cantidad}</span></div>`;
                    }).join('')}
                </div></div></div>
                <div class="col-md-4"><div class="card shadow-sm border-0 h-100"><div class="card-body">
                    <div class="text-muted small mb-2">Reportes peor calificados (promedio ≤ 2)</div>
                    ${d.peor_calificados.length ? '' : '<div class="small text-muted">Ninguno.</div>'}
                    ${d.peor_calificados.map(p => `<div class="small mb-1 text-truncate"><a href="#reporte/${parseInt(p.id_reporte, 10)}">#${parseInt(p.id_reporte, 10)}</a>
                        <span class="text-warning">${estrellas(p.promedio)}</span> <span class="text-muted">(${parseInt(p.cantidad, 10)})</span> ${e(p.descripcion)}</div>`).join('')}
                </div></div></div>
            </div>
            <div class="table-responsive bg-white rounded shadow-sm">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Calificación</th><th>Usuario</th><th>Reporte</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    ${d.calificaciones.length ? '' : '<tr><td colspan="4" class="text-center text-muted py-4">No hay calificaciones.</td></tr>'}
                    ${d.calificaciones.map(v => `
                        <tr>
                            <td class="text-warning text-nowrap">${estrellas(v.valor)}</td>
                            <td class="small text-nowrap">${e(v.nombre_usuario)}<br><span class="text-muted">${fecha(v.fecha)}</span></td>
                            <td class="small"><a href="#reporte/${parseInt(v.id_reporte, 10)}">#${parseInt(v.id_reporte, 10)}</a>
                                <div class="text-muted text-truncate" style="max-width: 260px;">${e(v.descripcion)}</div></td>
                            <td class="text-end">${accion('borrar_calificacion', v.id_valoracion, 'Borrar', 'btn btn-sm btn-outline-danger', '¿Borrar esta calificación?')}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>`;
    }

    // ---------- Usuarios ----------
    async function vistaUsuarios() {
        const usuarios = await mappealo.rpc('admin_usuarios');

        contenido.innerHTML = `
            <p class="text-muted small">${usuarios.length} usuario(s) registrado(s). El correo y la contraseña los administra Supabase Auth.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Usuario</th><th>Rol</th><th>Credibilidad</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    ${usuarios.map(u => `
                        <tr>
                            <td><strong>${e(u.nombre_usuario)}</strong><div class="small text-muted">${e(u.email)}</div></td>
                            <td>${u.es_admin
                                ? '<span class="badge text-bg-dark"><i class="fa-solid fa-shield-halved me-1"></i>Administrador</span>'
                                : '<span class="badge text-bg-success">Usuario</span>'}</td>
                            <td>${credibilidad(u.credibilidad)}</td>
                            <td class="text-end text-nowrap">
                                ${u.es_yo ? '<span class="small text-muted">Sos vos</span>' : `
                                    ${accion('cambiar_rol', u.id_usuario, u.es_admin ? '<i class="fa-solid fa-user"></i> Quitar admin' : '<i class="fa-solid fa-shield-halved"></i> Hacer admin',
                                        'btn btn-warning btn-sm', u.es_admin ? `¿Quitarle el rol de administrador a ${u.nombre_usuario}?` : `¿Darle el rol de administrador a ${u.nombre_usuario}?`, u.es_admin ? 'no' : 'si')}
                                    ${accion('borrar_usuario', u.id_usuario, '<i class="fa-solid fa-trash"></i> Eliminar', 'btn btn-danger btn-sm',
                                        `¿Eliminar al usuario ${u.email}? También se borran sus reportes, comentarios y calificaciones.`)}`}
                            </td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>`;
    }

    // ---------- Navegación entre pestañas ----------
    async function actualizarContador() {
        try {
            const { denunciados } = await mappealo.rpc('admin_resumen');
            const badge = document.getElementById('contadorDenuncias');
            badge.textContent = denunciados;
            badge.classList.toggle('d-none', !denunciados);
        } catch (err) { }
    }

    async function mostrarVista() {
        const [pestana, id] = (location.hash.slice(1) || 'reportes').split('/');
        const activa = pestana === 'reporte' ? 'reportes' : pestana;

        document.querySelectorAll('#pestanas a').forEach(a => {
            const es = a.dataset.pestana === activa;
            a.classList.toggle('active', es);
            if (es) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
        });

        const vistas = { reportes: vistaReportes, denuncias: vistaDenuncias, comentarios: vistaComentarios, calificaciones: vistaCalificaciones, usuarios: vistaUsuarios };
        try {
            if (pestana === 'reporte' && id) {
                await vistaReporte(id);
            } else {
                await (vistas[pestana] || vistaReportes)();
            }
        } catch (err) {
            contenido.innerHTML = `<div class="alert alert-danger">${e(err.message)}</div>`;
        }
        actualizarContador();
    }

    contenido.addEventListener('click', async ev => {
        const boton = ev.target.closest('[data-accion]');
        if (!boton || !confirm(boton.dataset.confirmar)) return;

        const { accion: nombre, id, extra } = boton.dataset;
        boton.disabled = true;
        try {
            let r;
            if (nombre === 'cambiar_rol') {
                r = await mappealo.rpc('admin_cambiar_rol', { p_usuario: id, p_es_admin: extra === 'si' });
            } else if (nombre === 'borrar_usuario') {
                r = await mappealo.rpc('admin_borrar_usuario', { p_usuario: id });
            } else {
                r = await mappealo.rpc('admin_accion', { p_accion: nombre, p_id: Number(id), p_estado: extra || null });
            }
            mostrarMensaje(r.message);
            if (nombre === 'borrar_reporte' && location.hash.startsWith('#reporte/')) {
                location.hash = '#reportes';
                return;
            }
            await mostrarVista();
        } catch (err) {
            mostrarMensaje(err.message, 'error');
            boton.disabled = false;
        }
    });

    window.addEventListener('hashchange', () => {
        mensajes.innerHTML = '';
        mostrarVista();
    });

    document.getElementById('btnSalir').addEventListener('click', ev => {
        ev.preventDefault();
        mappealo.salir('../login.html');
    });

    // Solo administradores
    (async () => {
        const sesion = await mappealo.requerirSesion('../login.html');
        const perfil = await mappealo.perfil().catch(() => null);
        if (!perfil || !perfil.es_admin) {
            window.location.replace('../index.html');
            return;
        }
        document.getElementById('adminUsuario').textContent = sesion.user.email || perfil.nombre_usuario;
        mostrarVista();
    })();
})();
