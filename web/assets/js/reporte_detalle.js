(function () {
    const modal = document.getElementById('modal-reporte');
    const cuerpo = document.getElementById('modal-reporte-cuerpo');
    const titulo = document.getElementById('modal-reporte-titulo');
    const categoria = document.getElementById('modal-reporte-categoria');

    if (!modal || !cuerpo) return;

    const MOTIVOS = [
        'Información falsa',
        'Contenido ofensivo o discriminatorio',
        'Datos personales expuestos',
        'Spam o publicidad',
        'Reporte duplicado',
        'Otro'
    ];

    let idActual = null;
    let datosActuales = null;

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

    function estrellas(promedio) {
        const valor = Math.round(promedio || 0);
        return '★★★★★'.slice(0, valor) + '☆☆☆☆☆'.slice(0, 5 - valor);
    }

    function mostrarMensaje(texto, tipo = 'ok') {
        const caja = cuerpo.querySelector('.detalle-mensaje');
        if (!caja) return;
        caja.textContent = texto;
        caja.className = `detalle-mensaje ${tipo}`;
    }

    async function enviar(accion, datos) {
        if (accion === 'calificar') return mappealo.calificar(datos.id_reporte, datos.valor);
        if (accion === 'comentar') return mappealo.comentar(datos.id_reporte, datos.contenido);
        if (accion === 'denunciar') return mappealo.denunciar(datos.id_reporte, datos.motivo, datos.detalle);
        throw new Error('Acción desconocida.');
    }

    function bloqueInvitado() {
        return `
            <div class="detalle-invitado">
                <i class="fa-solid fa-lock"></i>
                <div>
                    <strong>Solo para usuarios registrados</strong>
                    <p>Para comentar, calificar o denunciar un reporte necesitás una cuenta.</p>
                    <div class="detalle-invitado-links">
                        <a href="login.html">Iniciar sesión</a>
                        <a href="registro.html">Crear una cuenta</a>
                    </div>
                </div>
            </div>`;
    }

    function bloqueCredibilidad(c) {
        if (!c) return '';
        const factores = (c.factores || []).map(f => `
            <li>
                <span>${escapar(f.texto)}</span>
                <strong class="${f.base ? '' : (f.puntos > 0 ? 'positivo' : 'negativo')}">${f.base ? '' : (f.puntos > 0 ? '+' : '')}${parseInt(f.puntos, 10)}</strong>
            </li>`).join('');

        return `
            <div class="detalle-seccion">
                <h3>Índice de credibilidad</h3>
                <div class="cred-medidor cred-${escapar(c.nivel.clave)}">
                    <div class="cred-medidor-cabecera">
                        <span class="cred-badge cred-${escapar(c.nivel.clave)}"><i class="fa-solid fa-shield-halved"></i> ${escapar(c.nivel.texto)}</span>
                        <strong>${parseInt(c.puntaje, 10)}<small>/100</small></strong>
                    </div>
                    <div class="cred-barra"><span style="width: ${parseInt(c.puntaje, 10)}%"></span></div>
                </div>
                <details class="cred-detalle">
                    <summary>¿Cómo se calcula?</summary>
                    <ul>${factores}</ul>
                    <p class="detalle-gris">Parte de la credibilidad de quien lo publicó (invitado o usuario registrado, y cómo calificaron o denunciaron sus otros reportes) y cambia con las calificaciones y denuncias de este reporte.</p>
                </details>
            </div>`;
    }

    function bloqueCalificacion(cal, puede) {
        const resumen = cal.cantidad > 0
            ? `<span class="detalle-estrellas">${estrellas(cal.promedio)}</span>
               <strong>${cal.promedio.toFixed(1).replace('.', ',')}</strong>
               <span class="detalle-gris">(${cal.cantidad} ${cal.cantidad === 1 ? 'calificación' : 'calificaciones'})</span>`
            : '<span class="detalle-gris">Todavía nadie calificó este reporte.</span>';

        const selector = puede ? `
            <div class="detalle-calificar">
                <span>${cal.mia ? 'Tu calificación:' : '¿Qué tan útil o confiable es este reporte?'}</span>
                <div class="selector-estrellas" role="radiogroup" aria-label="Calificar de 1 a 5 estrellas">
                    ${[1, 2, 3, 4, 5].map(n => `
                        <button type="button" class="estrella${cal.mia && n <= cal.mia ? ' activa' : ''}" data-valor="${n}"
                            role="radio" aria-checked="${cal.mia === n}" title="${n} ${n === 1 ? 'estrella' : 'estrellas'}">★</button>
                    `).join('')}
                </div>
            </div>` : '';

        return `
            <div class="detalle-seccion">
                <h3>Calificación</h3>
                <div class="detalle-resumen-calificacion">${resumen}</div>
                ${selector}
            </div>`;
    }

    function bloqueComentarios(comentarios, puede) {
        const lista = comentarios.length
            ? comentarios.map(c => `
                <li>
                    <div class="comentario-meta">
                        <strong>${escapar(c.nombre_usuario)}</strong>
                        <span>${formatearFecha(c.fecha)}</span>
                    </div>
                    <p>${escapar(c.contenido)}</p>
                </li>`).join('')
            : '<li class="detalle-gris sin-comentarios">Todavía no hay comentarios.</li>';

        const formulario = puede ? `
            <form class="form-comentario">
                <textarea maxlength="500" rows="2" placeholder="Escribí un comentario..." required></textarea>
                <div class="form-comentario-pie">
                    <span class="contador">0 / 500</span>
                    <button type="submit"><i class="fa-solid fa-paper-plane"></i> Comentar</button>
                </div>
            </form>` : '';

        return `
            <div class="detalle-seccion">
                <h3>Comentarios (${comentarios.length})</h3>
                <ul class="lista-comentarios">${lista}</ul>
                ${formulario}
            </div>`;
    }

    function bloqueDenuncia(yaDenuncie) {
        if (yaDenuncie) {
            return `
                <div class="detalle-seccion detalle-denuncia">
                    <p class="detalle-gris"><i class="fa-solid fa-flag"></i> Ya denunciaste este reporte. Un administrador lo va a revisar.</p>
                </div>`;
        }

        return `
            <div class="detalle-seccion detalle-denuncia">
                <button type="button" class="btn-abrir-denuncia"><i class="fa-solid fa-flag"></i> Denunciar reporte</button>
                <form class="form-denuncia d-none">
                    <label>Motivo de la denuncia</label>
                    <select required>
                        <option value="">Elegí un motivo</option>
                        ${MOTIVOS.map(m => `<option value="${escapar(m)}">${escapar(m)}</option>`).join('')}
                    </select>
                    <textarea maxlength="200" rows="2" placeholder="Detalle (opcional, obligatorio si elegís «Otro»)"></textarea>
                    <div class="form-denuncia-acciones">
                        <button type="button" class="btn-cancelar-denuncia">Cancelar</button>
                        <button type="submit" class="btn-enviar-denuncia">Enviar denuncia</button>
                    </div>
                </form>
            </div>`;
    }

    function renderizar(datos) {
        datosActuales = datos;
        const rep = datos.reporte;
        const esDelito = rep.categoria === 'delito';

        categoria.textContent = esDelito ? 'Delito' : (rep.categoria === 'comunitario' ? 'Comunitario' : 'Reporte');
        categoria.className = `modal-reporte-categoria ${esDelito ? 'delito' : 'comunitario'}`;
        titulo.textContent = esDelito ? (rep.tipo_robo || 'Delito') : (rep.tipo_incidente || 'Incidente comunitario');

        cuerpo.innerHTML = `
            <div class="detalle-mensaje d-none"></div>
            <p class="detalle-descripcion">${escapar(rep.descripcion)}</p>
            <ul class="detalle-meta">
                <li><i class="fa-solid fa-location-dot"></i> ${escapar(rep.direccion || 'Sin dirección')}</li>
                <li><i class="fa-solid fa-clock"></i> Sucedió: ${formatearFecha(rep.fecha_incidente)}</li>
                <li><i class="fa-solid ${rep.es_anonimo ? 'fa-user-secret' : 'fa-user'}"></i> Publicado por <b>${escapar(rep.nombre_usuario)}</b>${rep.es_anonimo && datos.es_mio ? ' (tu reporte, nadie más ve tu nombre)' : ''}</li>
            </ul>
            ${bloqueCredibilidad(datos.credibilidad)}
            ${bloqueCalificacion(datos.calificacion, datos.puede_interactuar)}
            ${bloqueComentarios(datos.comentarios, datos.puede_interactuar)}
            ${datos.puede_interactuar ? bloqueDenuncia(datos.ya_denuncie) : bloqueInvitado()}
        `;

        conectarEventos();
    }

    function conectarEventos() {
        cuerpo.querySelectorAll('.selector-estrellas .estrella').forEach(boton => {
            boton.addEventListener('mouseenter', () => resaltarEstrellas(parseInt(boton.dataset.valor, 10)));
            boton.addEventListener('click', () => calificar(parseInt(boton.dataset.valor, 10)));
        });

        cuerpo.querySelector('.selector-estrellas')?.addEventListener('mouseleave', () => {
            resaltarEstrellas(datosActuales?.calificacion?.mia || 0);
        });

        const formComentario = cuerpo.querySelector('.form-comentario');
        if (formComentario) {
            const textarea = formComentario.querySelector('textarea');
            const contador = formComentario.querySelector('.contador');
            textarea.addEventListener('input', () => {
                contador.textContent = `${textarea.value.length} / 500`;
            });
            formComentario.addEventListener('submit', comentar);
        }

        const formDenuncia = cuerpo.querySelector('.form-denuncia');
        const btnAbrir = cuerpo.querySelector('.btn-abrir-denuncia');

        btnAbrir?.addEventListener('click', () => {
            btnAbrir.classList.add('d-none');
            formDenuncia.classList.remove('d-none');
        });

        cuerpo.querySelector('.btn-cancelar-denuncia')?.addEventListener('click', () => {
            formDenuncia.reset();
            formDenuncia.classList.add('d-none');
            btnAbrir.classList.remove('d-none');
        });

        formDenuncia?.addEventListener('submit', denunciar);
    }

    function resaltarEstrellas(hasta) {
        cuerpo.querySelectorAll('.selector-estrellas .estrella').forEach(b => {
            b.classList.toggle('activa', parseInt(b.dataset.valor, 10) <= hasta);
        });
    }

    async function calificar(valor) {
        const anterior = datosActuales?.calificacion?.mia || null;
        const textoEstrellas = `${valor} ${valor === 1 ? 'estrella' : 'estrellas'}`;
        const pregunta = anterior
            ? `¿Cambiar tu calificación de ${anterior} a ${textoEstrellas}?`
            : `¿Calificar este reporte con ${textoEstrellas}?`;

        if (anterior === valor) {
            mostrarMensaje(`Ya calificaste este reporte con ${textoEstrellas}.`);
            return;
        }

        if (!confirm(pregunta)) {
            resaltarEstrellas(anterior || 0);
            return;
        }

        try {
            const respuesta = await enviar('calificar', { id_reporte: idActual, valor });
            await cargar(idActual);
            mostrarMensaje(respuesta.message);
        } catch (e) {
            mostrarMensaje(e.message, 'error');
        }
    }

    async function comentar(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const textarea = form.querySelector('textarea');
        const boton = form.querySelector('button[type="submit"]');
        const contenido = textarea.value.trim();

        if (contenido.length < 2) {
            mostrarMensaje('El comentario debe tener al menos 2 caracteres.', 'error');
            return;
        }

        boton.disabled = true;
        try {
            const respuesta = await enviar('comentar', { id_reporte: idActual, contenido });
            await cargar(idActual);
            mostrarMensaje(respuesta.message);
            const lista = cuerpo.querySelector('.lista-comentarios');
            lista?.lastElementChild?.scrollIntoView({ block: 'nearest' });
        } catch (err) {
            mostrarMensaje(err.message, 'error');
            boton.disabled = false;
        }
    }

    async function denunciar(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const motivo = form.querySelector('select').value;
        const detalle = form.querySelector('textarea').value.trim();

        if (!motivo) {
            mostrarMensaje('Elegí un motivo para la denuncia.', 'error');
            return;
        }

        if (motivo === 'Otro' && detalle.length < 5) {
            mostrarMensaje('Contanos brevemente el motivo de la denuncia.', 'error');
            return;
        }

        if (!confirm('¿Enviar la denuncia? Un administrador revisará el reporte.')) return;

        try {
            const respuesta = await enviar('denunciar', { id_reporte: idActual, motivo, detalle });
            await cargar(idActual);
            mostrarMensaje(respuesta.message);
        } catch (err) {
            mostrarMensaje(err.message, 'error');
        }
    }

    async function cargar(id) {
        const datos = await mappealo.reporteDetalle(id);
        renderizar(datos);
    }

    async function abrir(id) {
        idActual = id;
        titulo.textContent = 'Cargando...';
        categoria.textContent = '';
        cuerpo.innerHTML = '<p class="detalle-gris">Cargando reporte...</p>';
        modal.classList.remove('d-none');
        document.body.classList.add('modal-reporte-abierto');

        try {
            await cargar(id);
        } catch (e) {
            titulo.textContent = 'Reporte';
            cuerpo.innerHTML = `<p class="detalle-mensaje error">${escapar(e.message)}</p>`;
        }
    }

    function cerrar() {
        modal.classList.add('d-none');
        document.body.classList.remove('modal-reporte-abierto');
        idActual = null;
        datosActuales = null;
    }

    document.addEventListener('click', (e) => {
        const boton = e.target.closest('.btn-ver-reporte');
        if (boton) {
            e.preventDefault();
            abrir(parseInt(boton.dataset.reporteId, 10));
            return;
        }

        if (e.target.closest('[data-cerrar-detalle]')) {
            cerrar();
        }
    });

    if (typeof map !== 'undefined') {
        map.on('popupopen', (e) => {
            const boton = e.popup.getElement()?.querySelector('.btn-ver-reporte');
            if (!boton || boton.dataset.conectado) return;
            boton.dataset.conectado = '1';
            boton.addEventListener('click', (ev) => {
                ev.preventDefault();
                map.closePopup();
                abrir(parseInt(boton.dataset.reporteId, 10));
            });
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('d-none')) cerrar();
    });

    window.abrirDetalleReporte = abrir;
})();
