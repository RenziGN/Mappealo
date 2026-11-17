(function () {
    const modal = document.getElementById('modal-historial');
    const cuerpo = document.getElementById('modal-historial-cuerpo');

    if (!modal || !cuerpo) return;

    let reportes = [];
    let miCredibilidad = null;
    let csrf = '';
    let filtro = 'todos';
    let resaltado = null;

    function escapar(texto) {
        return String(texto ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function formatearFecha(fecha) {
        if (!fecha) return '';
        const d = new Date(String(fecha).replace(' ', 'T'));
        if (isNaN(d)) return escapar(fecha);
        return d.toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function etiquetaEstado(estado) {
        const valor = String(estado || 'pendiente').toLowerCase();
        const textos = { pendiente: 'Pendiente', verificado: 'Verificado', rechazado: 'Rechazado' };
        return `<span class="historial-estado ${escapar(valor)}">${escapar(textos[valor] || estado)}</span>`;
    }

    function bloqueInvitado() {
        return `
            <div class="detalle-invitado">
                <i class="fa-solid fa-lock"></i>
                <div>
                    <strong>Historial solo para usuarios registrados</strong>
                    <p>Como invitado, tus reportes se publican de forma anónima y no quedan asociados a vos. Creá una cuenta o iniciá sesión para ver tu historial y seguir el estado de tus alertas.</p>
                    <div class="detalle-invitado-links">
                        <a href="login.html">Iniciar sesión</a>
                        <a href="registro.html">Crear una cuenta</a>
                    </div>
                </div>
            </div>`;
    }

    function bloqueMiCredibilidad() {
        if (!miCredibilidad) return '';
        const c = miCredibilidad;
        const factores = (c.factores || []).map(f => `
            <li>
                <span>${escapar(f.texto)}</span>
                <strong class="${f.base ? '' : (f.puntos > 0 ? 'positivo' : 'negativo')}">${f.base ? '' : (f.puntos > 0 ? '+' : '')}${parseInt(f.puntos, 10)}</strong>
            </li>`).join('');

        return `
            <div class="cred-medidor cred-${escapar(c.nivel.clave)} mb-3">
                <div class="cred-medidor-cabecera">
                    <div>
                        <span class="detalle-gris">Tu índice de credibilidad</span>
                        <span class="cred-badge cred-${escapar(c.nivel.clave)} ms-1"><i class="fa-solid fa-shield-halved"></i> ${escapar(c.nivel.texto)}</span>
                    </div>
                    <strong>${parseInt(c.puntaje, 10)}<small>/100</small></strong>
                </div>
                <div class="cred-barra"><span style="width: ${parseInt(c.puntaje, 10)}%"></span></div>
                <details class="cred-detalle">
                    <summary>¿Cómo se calcula?</summary>
                    <ul>${factores}</ul>
                    <p class="detalle-gris">Sube cuando califican bien tus reportes y baja si los denuncian o si publicás muchos en poco tiempo. Es el punto de partida de la credibilidad de cada reporte nuevo que publiques.</p>
                </details>
            </div>`;
    }

    function renderizar() {
        const delitos = reportes.filter(r => r.categoria === 'delito').length;
        const comunitarios = reportes.filter(r => r.categoria === 'comunitario').length;
        const comentarios = reportes.reduce((t, r) => t + (parseInt(r.cantidad_comentarios, 10) || 0), 0);

        if (!reportes.length) {
            cuerpo.innerHTML = bloqueMiCredibilidad() + `
                <div class="historial-vacio">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <strong>Todavía no publicaste reportes</strong>
                    <p>Cuando cargues un delito o un incidente del barrio, lo vas a ver acá con sus calificaciones y comentarios.</p>
                    <button type="button" class="btn-historial-nuevo">Cargar mi primer reporte</button>
                </div>`;
            cuerpo.querySelector('.btn-historial-nuevo')?.addEventListener('click', () => {
                cerrar();
                document.querySelector('.report-box button')?.click();
            });
            return;
        }

        const visibles = reportes.filter(r => filtro === 'todos' || r.categoria === filtro);

        cuerpo.innerHTML = bloqueMiCredibilidad() + `
            <div class="historial-resumen">
                <div><strong>${reportes.length}</strong><span>Reportes</span></div>
                <div><strong>${delitos}</strong><span>Delitos</span></div>
                <div><strong>${comunitarios}</strong><span>Comunitarios</span></div>
                <div><strong>${comentarios}</strong><span>Comentarios</span></div>
            </div>
            <div class="historial-filtros" role="tablist">
                ${[['todos', 'Todos'], ['delito', 'Delitos'], ['comunitario', 'Comunitarios']].map(([clave, texto]) => `
                    <button type="button" class="${filtro === clave ? 'activo' : ''}" data-filtro="${clave}" role="tab" aria-selected="${filtro === clave}">${texto}</button>
                `).join('')}
            </div>
            <ul class="historial-lista">
                ${visibles.length ? visibles.map(r => {
                    const esDelito = r.categoria === 'delito';
                    const cantCal = parseInt(r.cantidad_calificaciones, 10) || 0;
                    return `
                    <li class="historial-item ${esDelito ? 'delito' : 'comunitario'}">
                        <div class="historial-item-cabecera">
                            <span class="historial-categoria">${esDelito ? 'Delito' : 'Comunitario'}</span>
                            <span class="d-flex gap-1">
                                ${r.credibilidad !== null && r.credibilidad !== undefined ? `<span class="cred-badge cred-${escapar(r.credibilidad_nivel)}" title="Credibilidad del reporte"><i class="fa-solid fa-shield-halved"></i> ${parseInt(r.credibilidad, 10)}</span>` : ''}
                                ${etiquetaEstado(r.estado)}
                            </span>
                        </div>
                        <strong class="historial-tipo">${escapar(r.tipo || (esDelito ? 'Delito' : 'Incidente comunitario'))}</strong>
                        <p class="historial-descripcion">${escapar(r.descripcion)}</p>
                        <div class="historial-meta">
                            <span><i class="fa-solid fa-calendar"></i> ${formatearFecha(r.fecha_reporte)}</span>
                            <span><i class="fa-solid fa-location-dot"></i> ${escapar(r.direccion || 'Sin dirección')}</span>
                            ${r.es_anonimo ? '<span><i class="fa-solid fa-user-secret"></i> Publicado como anónimo</span>' : ''}
                        </div>
                        <div class="historial-pie">
                            <span>⭐ ${cantCal ? `${escapar(r.promedio_calificacion)} (${cantCal})` : 'Sin calificar'}</span>
                            <span>💬 ${parseInt(r.cantidad_comentarios, 10) || 0}</span>
                            <div class="historial-acciones">
                                <button type="button" class="btn-historial-mapa" data-id="${parseInt(r.id_reporte, 10)}"><i class="fa-solid fa-map-pin"></i> Ver en el mapa</button>
                                <button type="button" class="btn-historial-detalle" data-id="${parseInt(r.id_reporte, 10)}"><i class="fa-solid fa-comments"></i> Detalle</button>
                                <button type="button" class="btn-historial-borrar" data-id="${parseInt(r.id_reporte, 10)}" title="Borrar este reporte" aria-label="Borrar este reporte"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                    </li>`;
                }).join('') : '<li class="detalle-gris">No hay reportes de este tipo.</li>'}
            </ul>`;

        cuerpo.querySelectorAll('.historial-filtros button').forEach(b => {
            b.addEventListener('click', () => {
                filtro = b.dataset.filtro;
                renderizar();
            });
        });

        cuerpo.querySelectorAll('.btn-historial-mapa').forEach(b => {
            b.addEventListener('click', () => verEnMapa(parseInt(b.dataset.id, 10)));
        });

        cuerpo.querySelectorAll('.btn-historial-detalle').forEach(b => {
            b.addEventListener('click', () => {
                cerrar();
                window.abrirDetalleReporte?.(parseInt(b.dataset.id, 10));
            });
        });

        cuerpo.querySelectorAll('.btn-historial-borrar').forEach(b => {
            b.addEventListener('click', () => borrar(parseInt(b.dataset.id, 10), b));
        });
    }

    async function borrar(id, boton) {
        if (!confirm('¿Borrar este reporte? También se borran sus comentarios, calificaciones y denuncias. No se puede deshacer.')) return;

        boton.disabled = true;
        try {
            await mappealo.borrarReporte(id);

            window.quitarReporteDelMapa?.(id);
            await abrir();
        } catch (e) {
            alert(e.message);
            boton.disabled = false;
        }
    }

    function verEnMapa(id) {
        const rep = reportes.find(r => parseInt(r.id_reporte, 10) === id);
        if (!rep || typeof map === 'undefined') return;

        const punto = [parseFloat(rep.latitud), parseFloat(rep.longitud)];
        cerrar();
        map.setView(punto, 18);

        if (resaltado) map.removeLayer(resaltado);
        resaltado = L.circleMarker(punto, {
            radius: 28,
            color: '#0d6efd',
            weight: 3,
            fillColor: '#0d6efd',
            fillOpacity: 0.15,
            className: 'historial-resaltado',
            interactive: false
        }).addTo(map);

        setTimeout(() => {
            if (resaltado) {
                map.removeLayer(resaltado);
                resaltado = null;
            }
        }, 4000);
    }

    async function abrir() {
        filtro = 'todos';
        cuerpo.innerHTML = '<p class="detalle-gris">Cargando tu historial...</p>';
        modal.classList.remove('d-none');
        document.body.classList.add('modal-reporte-abierto');

        try {
            if (window.mappealoPerfil?.es_invitado) {
                cuerpo.innerHTML = bloqueInvitado();
                return;
            }

            const datos = await mappealo.misReportes();

            reportes = Array.isArray(datos.data) ? datos.data : [];
            miCredibilidad = datos.mi_credibilidad || null;
            renderizar();
        } catch (e) {
            cuerpo.innerHTML = `<p class="detalle-mensaje error">${escapar(e.message)}</p>`;
        }
    }

    function cerrar() {
        modal.classList.add('d-none');
        document.body.classList.remove('modal-reporte-abierto');
    }

    modal.addEventListener('click', (e) => {
        if (e.target.closest('[data-cerrar-historial]')) cerrar();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('d-none')) cerrar();
    });

    window.abrirHistorialReportes = abrir;
})();
