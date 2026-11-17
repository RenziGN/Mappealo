(function () {
    const URL_DATOS = 'assets/data/comisarias_ituzaingo.json';

    const btnComisarias = document.getElementById('btnFiltroComisarias');
    const panel = document.getElementById('panel-comisarias');
    const lista = document.getElementById('lista-comisarias');
    const aviso = document.getElementById('aviso-comisarias');
    const chkPostas = document.getElementById('chkIncluirPostas');
    const btnCerrar = document.getElementById('btnCerrarComisarias');

    if (!btnComisarias || !panel) return;

    let datos = null;
    let activo = false;
    const capa = L.layerGroup();
    let poligonoLimite = null;
    const marcadores = new Map();

    const iconoComisaria = L.divIcon({
        className: 'icono-comisaria',
        html: '<i class="fa-solid fa-building-shield"></i>',
        iconSize: [34, 34],
        iconAnchor: [17, 17],
        popupAnchor: [0, -18]
    });

    const iconoPosta = L.divIcon({
        className: 'icono-comisaria icono-posta',
        html: '<i class="fa-solid fa-shield-halved"></i>',
        iconSize: [26, 26],
        iconAnchor: [13, 13],
        popupAnchor: [0, -14]
    });

    function escapar(texto) {
        return String(texto ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function formatearDistancia(metros) {
        if (metros < 1000) return `${Math.round(metros / 10) * 10} m`;
        return `${(metros / 1000).toFixed(1).replace('.', ',')} km`;
    }

    function primerTelefono(telefono) {
        if (!telefono) return null;
        const primero = telefono.split(';')[0].trim();
        return { texto: primero, href: primero.replace(/[^\d+]/g, '') };
    }

    function enlaceComoLlegar(dep) {
        return `https://www.google.com/maps/dir/?api=1&destination=${dep.lat},${dep.lng}`;
    }

    function estaDentroDeItuzaingo(latlng) {
        const limite = datos?.limite;
        if (!limite) return false;
        let dentro = false;
        for (let i = 0, j = limite.length - 1; i < limite.length; j = i++) {
            const [yi, xi] = limite[i];
            const [yj, xj] = limite[j];
            if ((yi > latlng.lat) !== (yj > latlng.lat) &&
                latlng.lng < (xj - xi) * (latlng.lat - yi) / (yj - yi) + xi) {
                dentro = !dentro;
            }
        }
        return dentro;
    }

    function contenidoPopup(dep, distancia) {
        const tel = primerTelefono(dep.telefono);
        return `
            <div class="popup-comisaria">
                <span class="popup-comisaria-tipo">${dep.tipo === 'comisaria' ? 'Comisaría' : 'Posta / puesto policial'}</span>
                <h4>${escapar(dep.nombre)}</h4>
                ${dep.direccion ? `<p>📍 ${escapar(dep.direccion)}, Ituzaingó</p>` : ''}
                ${distancia != null ? `<p>🚶 A ${formatearDistancia(distancia)} de tu ubicación</p>` : ''}
                <div class="popup-comisaria-acciones">
                    ${tel ? `<a href="tel:${escapar(tel.href)}">📞 Llamar</a>` : ''}
                    <a href="${enlaceComoLlegar(dep)}" target="_blank" rel="noopener">🧭 Cómo llegar</a>
                </div>
            </div>`;
    }

    function dependenciasVisibles() {
        const incluirPostas = chkPostas?.checked;
        return datos.dependencias.filter(d => d.tipo === 'comisaria' || incluirPostas);
    }

    function renderizar() {
        if (!datos || !activo) return;

        const origen = (typeof ubicacionActual !== 'undefined' && ubicacionActual) ? ubicacionActual : null;
        const visibles = dependenciasVisibles().map(dep => ({
            dep,
            distancia: origen ? map.distance(origen, [dep.lat, dep.lng]) : null
        }));

        if (origen) {
            visibles.sort((a, b) => a.distancia - b.distancia);
        }

        marcadores.forEach(m => capa.removeLayer(m));
        marcadores.clear();
        visibles.forEach(({ dep, distancia }) => {
            const marcador = L.marker([dep.lat, dep.lng], {
                icon: dep.tipo === 'comisaria' ? iconoComisaria : iconoPosta,
                title: dep.nombre,
                zIndexOffset: 500
            }).bindPopup(contenidoPopup(dep, distancia));
            marcador.addTo(capa);
            marcadores.set(dep.id, marcador);
        });

        if (!origen) {
            aviso.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Activá tu ubicación para ver cuál te queda más cerca.';
            aviso.classList.remove('d-none');
        } else if (!estaDentroDeItuzaingo(origen)) {
            aviso.innerHTML = '<i class="fa-solid fa-circle-info"></i> Por ahora solo mostramos comisarías del Partido de Ituzaingó y tu ubicación está fuera del partido.';
            aviso.classList.remove('d-none');
        } else {
            aviso.classList.add('d-none');
        }

        lista.innerHTML = visibles.map(({ dep, distancia }, indice) => {
            const tel = primerTelefono(dep.telefono);
            const masCercana = origen && indice === 0;
            return `
                <li class="item-comisaria${masCercana ? ' mas-cercana' : ''}" data-id="${escapar(dep.id)}">
                    <div class="item-comisaria-info">
                        <strong>${escapar(dep.nombre)}</strong>
                        ${masCercana ? '<span class="badge-cercana">Más cercana</span>' : ''}
                        <small>${dep.direccion ? escapar(dep.direccion) : 'Sin dirección cargada'}</small>
                    </div>
                    <div class="item-comisaria-lado">
                        ${distancia != null ? `<span class="distancia">${formatearDistancia(distancia)}</span>` : ''}
                        <div class="item-comisaria-acciones">
                            ${tel ? `<a href="tel:${escapar(tel.href)}" title="Llamar ${escapar(tel.texto)}"><i class="fa-solid fa-phone"></i></a>` : ''}
                            <a href="${enlaceComoLlegar(dep)}" target="_blank" rel="noopener" title="Cómo llegar"><i class="fa-solid fa-route"></i></a>
                        </div>
                    </div>
                </li>`;
        }).join('');
    }

    async function cargarDatos() {
        if (datos) return datos;
        const respuesta = await fetch(URL_DATOS);
        if (!respuesta.ok) throw new Error('No se pudo cargar ' + URL_DATOS);
        datos = await respuesta.json();

        poligonoLimite = L.polygon(datos.limite, {
            color: '#1a56db',
            weight: 2,
            dashArray: '6 6',
            fillColor: '#1a56db',
            fillOpacity: 0.04,
            interactive: false
        }).addTo(capa);

        return datos;
    }

    async function activar() {
        try {
            await cargarDatos();
        } catch (e) {
            console.error(e);
            alert('No se pudieron cargar las comisarías. Intentá nuevamente.');
            return;
        }

        activo = true;
        capa.addTo(map);
        panel.classList.remove('d-none');
        btnComisarias.classList.add('activo');
        renderizar();

        const origen = (typeof ubicacionActual !== 'undefined' && ubicacionActual) ? ubicacionActual : null;
        const primera = lista.querySelector('.item-comisaria');
        if (origen && estaDentroDeItuzaingo(origen) && primera) {
            const masCercana = datos.dependencias.find(d => d.id === primera.dataset.id);
            map.fitBounds(L.latLngBounds([origen, [masCercana.lat, masCercana.lng]]), { padding: [80, 80], maxZoom: 16 });
        } else {
            map.fitBounds(poligonoLimite.getBounds(), { padding: [20, 20] });
        }
    }

    function desactivar() {
        activo = false;
        map.removeLayer(capa);
        panel.classList.add('d-none');
        btnComisarias.classList.remove('activo');
    }

    btnComisarias.addEventListener('click', () => (activo ? desactivar() : activar()));
    btnCerrar?.addEventListener('click', desactivar);
    chkPostas?.addEventListener('change', renderizar);

    lista.addEventListener('click', (e) => {
        if (e.target.closest('a')) return;
        const item = e.target.closest('.item-comisaria');
        const marcador = item && marcadores.get(item.dataset.id);
        if (!marcador) return;
        map.setView(marcador.getLatLng(), Math.max(map.getZoom(), 16));
        marcador.openPopup();
    });

    document.addEventListener('ubicacion-actualizada', renderizar);
})();
