// assets/js/map.js

const map = L.map('map').setView([-34.665864, -58.664922], 14);

let calorActivo = false;
let circulosCalor = [];
let zonasBaseCalor = [];
let animacionCalor = null;
let secuenciaAnimacionCalor = 0;

const RADIO_CALOR = 300;
const DISTANCIA_UNION_CALOR = 80;
const DURACION_TRANSICION_CALOR = 350;

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap'
}).addTo(map);

// Icono personalizado para delito / robo
const ladronIcon = L.icon({
    iconUrl: 'assets/Img/ladron-icon.png',
    iconSize: [36, 36],
    iconAnchor: [18, 36],
    popupAnchor: [0, -36]
});

// Icono personalizado para incidente comunitario
const comunidadIcon = L.icon({
    iconUrl: 'assets/Img/comunidad.png',
    iconSize: [36, 36],
    iconAnchor: [18, 36],
    popupAnchor: [0, -36]
});

function crearIconoRobo(claseIcono, claseColor) {
    return L.divIcon({
        className: `marcador-robo ${claseColor}`,
        html: `<span><i class="fa-solid ${claseIcono}"></i></span>`,
        iconSize: [36, 44],
        iconAnchor: [18, 44],
        popupAnchor: [0, -42]
    });
}

const iconosRobo = {
    'Robo a mano armada': crearIconoRobo('fa-gun', 'robo-armado'),
    'Hurto / Arrebato en vía pública': crearIconoRobo('fa-person-running', 'robo-hurto'),
    'Robo de vehículo / Motochorros': crearIconoRobo('fa-motorcycle', 'robo-vehiculo')
};

const iconosComunitarios = {
    'Luminaria rota': crearIconoRobo('fa-lightbulb', 'comunitario-luminaria'),
    'Bache / Calle anegada': crearIconoRobo('fa-road', 'comunitario-bache'),
    'Basura acumulada': crearIconoRobo('fa-trash-can', 'comunitario-basura')
};

const gruposReportes = new Map();

function obtenerGrupoReporte(clave, tipoReporte, esDelito) {
    if (!gruposReportes.has(clave)) {
        const grupo = L.markerClusterGroup({
            maxClusterRadius: 55,
            showCoverageOnHover: false,
            iconCreateFunction: cluster => {
                const cantidad = cluster.getChildCount();
                return L.divIcon({
                    html: `<span>${cantidad}</span>`,
                    className: `cluster-reportes-icon ${esDelito ? 'cluster-delito' : 'cluster-comunitario'}`,
                    iconSize: L.point(46, 46),
                    title: `${tipoReporte}: ${cantidad} reportes`
                });
            }
        }).addTo(map);

        gruposReportes.set(clave, grupo);
    }

    return gruposReportes.get(clave);
}

// Marcador temporal de selección
let seleccionMarker = null;
window.coordenadasSeleccionadas = null;

function fijarUbicacionReporte(latlng, origen = 'Coordenadas fijadas') {
    window.coordenadasSeleccionadas = {
        lat: latlng.lat,
        lng: latlng.lng
    };

    if (seleccionMarker) {
        seleccionMarker.setLatLng(latlng);
    } else {
        seleccionMarker = L.marker(latlng).addTo(map);
    }

    // Actualizar el texto del formulario para que el usuario vea que se fijó la ubicación
    const cajasUbicacion = document.querySelectorAll('.location-box p');
    cajasUbicacion.forEach(p => {
        p.innerHTML = `<span style="color: #27ae60; font-weight: bold;">✓ ${origen}:</span> Lat: ${latlng.lat.toFixed(4)}, Lng: ${latlng.lng.toFixed(4)}`;
    });
}


let modoSeleccionMapa = false;
const bannerSeleccion = document.getElementById('banner-seleccion');

function abrirFormularioReporte() {
    popup.style.display = "block";
    overlay.style.display = "block";
}

function iniciarSeleccionEnMapa() {
    modoSeleccionMapa = true;
    popup.style.display = "none";
    overlay.style.display = "none";
    map.getContainer().classList.add('modo-seleccion');
    bannerSeleccion?.classList.remove('d-none');
}

function terminarSeleccionEnMapa() {
    modoSeleccionMapa = false;
    map.getContainer().classList.remove('modo-seleccion');
    bannerSeleccion?.classList.add('d-none');
    abrirFormularioReporte();
}

window.iniciarSeleccionEnMapa = iniciarSeleccionEnMapa;

document.getElementById('btnCancelarSeleccion')?.addEventListener('click', terminarSeleccionEnMapa);
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modoSeleccionMapa) terminarSeleccionEnMapa();
});

// Clic en el mapa para marcar ubicación
map.on('click', function(e) {
    fijarUbicacionReporte(e.latlng, modoSeleccionMapa ? 'Punto elegido en el mapa' : 'Coordenadas fijadas');

    if (modoSeleccionMapa) {
        terminarSeleccionEnMapa();
    } else {
        abrirFormularioReporte();
    }
});


let ubicacionActual = null;
let marcadorUsuario = null;
let circuloPrecision = null;
let seguimientoId = null;
let centradoInicial = false;
let ultimaUbicacionAvisada = null;

const MENSAJES_ERROR_UBICACION = {
    1: 'No diste permiso para acceder a tu ubicación. Podés habilitarlo desde el ícono del candado en la barra de direcciones del navegador.',
    2: 'No se pudo determinar tu ubicación. Revisá que el GPS o la ubicación del dispositivo estén activados.',
    3: 'Se agotó el tiempo para obtener tu ubicación. Intentá nuevamente.'
};

function actualizarMarcadorUsuario(posicion) {
    const { latitude, longitude, accuracy } = posicion.coords;
    ubicacionActual = L.latLng(latitude, longitude);

    if (!marcadorUsuario) {
        circuloPrecision = L.circle(ubicacionActual, {
            radius: accuracy,
            color: '#1a73e8',
            weight: 1,
            fillColor: '#1a73e8',
            fillOpacity: 0.12,
            interactive: false
        }).addTo(map);

        marcadorUsuario = L.circleMarker(ubicacionActual, {
            radius: 8,
            color: '#ffffff',
            weight: 3,
            fillColor: '#1a73e8',
            fillOpacity: 1
        }).addTo(map).bindPopup('<b>Estás aquí</b>');
    } else {
        marcadorUsuario.setLatLng(ubicacionActual);
        circuloPrecision.setLatLng(ubicacionActual).setRadius(accuracy);
    }

    if (!centradoInicial) {
        centradoInicial = true;
        map.setView(ubicacionActual, Math.max(map.getZoom(), 16));
    }

    if (!ultimaUbicacionAvisada || ultimaUbicacionAvisada.distanceTo(ubicacionActual) > 30) {
        ultimaUbicacionAvisada = ubicacionActual;
        document.dispatchEvent(new CustomEvent('ubicacion-actualizada', { detail: ubicacionActual }));
    }
}

function iniciarGeolocalizacion(mostrarErrores = false) {
    return new Promise((resolve, reject) => {
        if (!('geolocation' in navigator)) {
            const msg = 'Tu navegador no permite obtener la ubicación.';
            if (mostrarErrores) alert(msg);
            reject(new Error(msg));
            return;
        }

        if (!window.isSecureContext) {
            const msg = 'La ubicación solo funciona si la página se abre por HTTPS o desde localhost.';
            if (mostrarErrores) alert(msg);
            reject(new Error(msg));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            posicion => {
                actualizarMarcadorUsuario(posicion);

                if (seguimientoId === null) {
                    seguimientoId = navigator.geolocation.watchPosition(
                        actualizarMarcadorUsuario,
                        err => console.warn('Seguimiento de ubicación:', err.message),
                        { enableHighAccuracy: true, maximumAge: 10000 }
                    );
                }

                resolve(ubicacionActual);
            },
            error => {
                if (mostrarErrores) {
                    alert(MENSAJES_ERROR_UBICACION[error.code] || 'No se pudo obtener tu ubicación.');
                }
                reject(error);
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });
}

async function centrarEnUsuario() {
    try {
        const latlng = ubicacionActual || await iniciarGeolocalizacion(true);
        map.setView(latlng, Math.max(map.getZoom(), 16));
        marcadorUsuario?.openPopup();
    } catch (e) { }
}

async function usarUbicacionActualEnReporte() {
    try {
        const latlng = ubicacionActual || await iniciarGeolocalizacion(true);
        fijarUbicacionReporte(latlng, 'Tu ubicación actual');
        map.setView(latlng, Math.max(map.getZoom(), 16));
    } catch (e) { }
}

window.usarUbicacionActualEnReporte = usarUbicacionActualEnReporte;

document.getElementById('btnCentrar')?.addEventListener('click', centrarEnUsuario);

iniciarGeolocalizacion(false).catch(err => console.info('Ubicación no disponible:', err.message));

function textoNivelCredibilidad(nivel) {
    return { alta: 'alta', media: 'media', baja: 'baja' }[nivel] || '';
}

function escaparHtml(texto) {
    return String(texto ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

// Cargar reportes existentes desde la base de datos
function cargarReportesEnMapa() {
    fetch('api/get_reportes.php')
        .then(res => res.json())
        .then(response => {
            if (response.status === 'success') {
                window.reportesMapa = Array.isArray(response.data) ? response.data : [];

                response.data.forEach(rep => {
                    const esDelito = rep.categoria === 'delito';
                    const icono = esDelito
                        ? (iconosRobo[rep.tipo_robo] || ladronIcon)
                        : (iconosComunitarios[rep.tipo_incidente] || comunidadIcon);
                    const titulo = esDelito 
                        ? (rep.tipo_robo || 'Delito') 
                        : (rep.tipo_incidente || 'Incidente Comunitario');
                    const tipoReporte = esDelito
                        ? titulo
                        : (rep.categoria === 'comunitario' ? titulo : (rep.categoria || 'Otro'));
                    const claveGrupo = `${rep.categoria || 'otro'}:${tipoReporte}`;

                    const marker = L.marker([parseFloat(rep.latitud), parseFloat(rep.longitud)], { icon: icono });
                    const grupoMarcador = obtenerGrupoReporte(claveGrupo, tipoReporte, esDelito);
                    grupoMarcador.addLayer(marker);
                    marcadoresBusqueda.push({
                        marker,
                        grupo: grupoMarcador,
                        texto: normalizarBusqueda(`${rep.descripcion || ''} ${rep.nombre_usuario || ''} ${titulo}`)
                    });

                    const cantidadCalificaciones = parseInt(rep.cantidad_calificaciones, 10) || 0;
                    const cantidadComentarios = parseInt(rep.cantidad_comentarios, 10) || 0;
                    const resumenCalificacion = cantidadCalificaciones > 0
                        ? `⭐ ${escaparHtml(rep.promedio_calificacion)} (${cantidadCalificaciones})`
                        : '⭐ Sin calificaciones';

                    marker.bindPopup(`
                        <div style="min-width: 190px; font-family: sans-serif;">
                            <span style="font-size: 11px; font-weight: bold; text-transform: uppercase; color: ${esDelito ? '#e74c3c' : '#27ae60'};">
                                ${escaparHtml(rep.categoria)}
                            </span>
                            <h4 style="margin: 4px 0 6px 0; font-size: 14px;">${escaparHtml(titulo)}</h4>
                            <p style="margin: 0 0 6px 0; font-size: 12px; color: #333;">${escaparHtml(rep.descripcion)}</p>
                            <small style="color: #666; font-size: 11px;">📍 ${escaparHtml(rep.direccion || 'Sin dirección')}</small><br>
                            <small style="color: #666; font-size: 11px;">👤 Por: <b>${escaparHtml(rep.nombre_usuario)}</b></small>
                            <div class="popup-reporte-resumen">
                                <span>${resumenCalificacion}</span>
                                <span>💬 ${cantidadComentarios}</span>
                            </div>
                            ${rep.credibilidad !== null && rep.credibilidad !== undefined ? `
                            <div class="cred-badge cred-${escaparHtml(rep.credibilidad_nivel)}" title="Índice de credibilidad del reporte (0 a 100)">
                                <i class="fa-solid fa-shield-halved"></i> Credibilidad ${escaparHtml(textoNivelCredibilidad(rep.credibilidad_nivel))} · ${parseInt(rep.credibilidad, 10)}
                            </div>` : ''}
                            <button type="button" class="btn-ver-reporte" data-reporte-id="${parseInt(rep.id_reporte, 10)}">
                                Ver comentarios · Calificar
                            </button>
                        </div>
                    `);
                });

                if (calorActivo) {
                    generarMapaCalor(window.reportesMapa);
                }

                aplicarBusqueda(buscadorMapa?.value || '');
            }
        })
        .catch(err => console.error("Error al cargar reportes:", err));
}

const marcadoresBusqueda = [];
const buscadorMapa = document.getElementById('buscadorMapa');
const btnLimpiarBusqueda = document.getElementById('btnLimpiarBusqueda');
const resultadoBusqueda = document.getElementById('resultadoBusqueda');
let temporizadorBusqueda = null;

function normalizarBusqueda(texto) {
    return String(texto || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function aplicarBusqueda(consulta) {
    const palabras = normalizarBusqueda(consulta).split(' ').filter(Boolean);
    const coincidencias = [];

    marcadoresBusqueda.forEach(item => {
        const coincide = palabras.every(p => item.texto.includes(p));

        if (coincide) {
            coincidencias.push(item.marker);
            if (!item.grupo.hasLayer(item.marker)) item.grupo.addLayer(item.marker);
        } else if (item.grupo.hasLayer(item.marker)) {
            item.grupo.removeLayer(item.marker);
        }
    });

    btnLimpiarBusqueda?.classList.toggle('d-none', palabras.length === 0);

    if (resultadoBusqueda) {
        if (palabras.length === 0) {
            resultadoBusqueda.classList.add('d-none');
        } else {
            resultadoBusqueda.classList.remove('d-none');
            resultadoBusqueda.classList.toggle('sin-resultados', coincidencias.length === 0);
            resultadoBusqueda.textContent = coincidencias.length === 0
                ? 'Sin resultados'
                : `${coincidencias.length} ${coincidencias.length === 1 ? 'resultado' : 'resultados'}`;
            resultadoBusqueda.title = coincidencias.length === 0
                ? 'No hay reportes que coincidan con la búsqueda.'
                : 'Presioná Enter para ver los resultados en el mapa.';
        }
    }

    return coincidencias;
}

buscadorMapa?.addEventListener('input', () => {
    clearTimeout(temporizadorBusqueda);
    temporizadorBusqueda = setTimeout(() => aplicarBusqueda(buscadorMapa.value), 150);
});

buscadorMapa?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(temporizadorBusqueda);
        const coincidencias = aplicarBusqueda(buscadorMapa.value);
        if (coincidencias.length === 1) {
            map.setView(coincidencias[0].getLatLng(), 18);
        } else if (coincidencias.length > 1) {
            map.fitBounds(L.latLngBounds(coincidencias.map(m => m.getLatLng())), { padding: [60, 60], maxZoom: 17 });
        }
    } else if (e.key === 'Escape' && buscadorMapa.value) {
        buscadorMapa.value = '';
        aplicarBusqueda('');
    }
});

btnLimpiarBusqueda?.addEventListener('click', () => {
    buscadorMapa.value = '';
    aplicarBusqueda('');
    buscadorMapa.focus();
});

cargarReportesEnMapa();

function generarMapaCalor(reportes) {
    const delitos = reportes.filter(rep => {
        const lat = parseFloat(rep.latitud);
        const lng = parseFloat(rep.longitud);

        return (
            rep.categoria === 'delito' &&
            !isNaN(lat) &&
            !isNaN(lng)
        );
    });

    zonasBaseCalor = [];
    const delitosAsignados = new Set();

    delitos.forEach(delito => {
        const lat = parseFloat(delito.latitud);
        const lng = parseFloat(delito.longitud);
        const idDelito = delito.id_reporte ?? delito;

        if (delitosAsignados.has(idDelito)) {
            return;
        }

        const delitosCercanos = delitos.filter(otroDelito => {
            const idOtroDelito = otroDelito.id_reporte ?? otroDelito;
            if (delitosAsignados.has(idOtroDelito)) {
                return false;
            }

            const otraLat = parseFloat(otroDelito.latitud);
            const otraLng = parseFloat(otroDelito.longitud);
            return map.distance([lat, lng], [otraLat, otraLng]) <= RADIO_CALOR;
        });

        delitosCercanos.forEach(rep => {
            delitosAsignados.add(rep.id_reporte ?? rep);
        });

        const sumaCoordenadas = delitosCercanos.reduce((suma, rep) => {
            suma.lat += parseFloat(rep.latitud);
            suma.lng += parseFloat(rep.longitud);
            return suma;
        }, { lat: 0, lng: 0 });

        zonasBaseCalor.push({
            centro: [
                sumaCoordenadas.lat / delitosCercanos.length,
                sumaCoordenadas.lng / delitosCercanos.length
            ],
            radio: RADIO_CALOR,
            delitos: delitosCercanos
        });
    });

    dibujarZonasCalor();
}

function mezclarColores(colorInicial, colorFinal, progreso) {
    const inicio = parseInt(colorInicial.slice(1), 16);
    const fin = parseInt(colorFinal.slice(1), 16);
    const canales = [16, 8, 0].map(desplazamiento => {
        const valorInicial = (inicio >> desplazamiento) & 255;
        const valorFinal = (fin >> desplazamiento) & 255;
        return Math.round(valorInicial + (valorFinal - valorInicial) * progreso)
            .toString(16)
            .padStart(2, '0');
    });

    return `#${canales.join('')}`;
}

function animarCirculosCalor(destinos) {
    if (animacionCalor !== null) {
        cancelAnimationFrame(animacionCalor);
    }

    const secuenciaActual = ++secuenciaAnimacionCalor;
    const anteriores = circulosCalor.slice();
    const circulosUsados = new Set();
    const animaciones = [];
    const circulosDestino = [];

    destinos.forEach(destino => {
        let circuloCercano = null;
        let distanciaMinima = Infinity;

        anteriores.forEach(circulo => {
            if (circulosUsados.has(circulo)) {
                return;
            }

            const distancia = map.distance(circulo.getLatLng(), destino.centro);
            if (distancia < distanciaMinima) {
                distanciaMinima = distancia;
                circuloCercano = circulo;
            }
        });

        const circulo = circuloCercano || L.circle(destino.centro, {
            radius: 0,
            stroke: false,
            fillColor: destino.color,
            fillOpacity: 0
        }).addTo(map);

        if (circuloCercano) {
            circulosUsados.add(circulo);
        }

        circulo.bindPopup(destino.popup);
        circulosDestino.push(circulo);
        animaciones.push({
            circulo,
            latlngInicial: circulo.getLatLng(),
            radioInicial: circulo.getRadius(),
            opacidadInicial: circulo.options.fillOpacity ?? 0.35,
            colorInicial: circulo.options.fillColor || destino.color,
            destino
        });
    });

    const circulosSalientes = anteriores.filter(circulo => !circulosUsados.has(circulo));
    circulosSalientes.forEach(circulo => {
        animaciones.push({
            circulo,
            latlngInicial: circulo.getLatLng(),
            radioInicial: circulo.getRadius(),
            opacidadInicial: circulo.options.fillOpacity ?? 0.35,
            colorInicial: circulo.options.fillColor || '#F4FC47',
            destino: null
        });
    });

    circulosCalor = [...circulosDestino, ...circulosSalientes];
    const inicio = performance.now();

    function avanzar(ahora) {
        if (secuenciaActual !== secuenciaAnimacionCalor) {
            return;
        }

        const progreso = Math.min((ahora - inicio) / DURACION_TRANSICION_CALOR, 1);
        const progresoSuave = 1 - Math.pow(1 - progreso, 3);

        animaciones.forEach(animacion => {
            const { circulo, latlngInicial, radioInicial, opacidadInicial, colorInicial, destino } = animacion;

            if (!destino) {
                circulo.setRadius(radioInicial * (1 - progresoSuave));
                circulo.setStyle({ fillOpacity: opacidadInicial * (1 - progresoSuave) });
                return;
            }

            circulo.setLatLng([
                latlngInicial.lat + (destino.centro[0] - latlngInicial.lat) * progresoSuave,
                latlngInicial.lng + (destino.centro[1] - latlngInicial.lng) * progresoSuave
            ]);
            circulo.setRadius(radioInicial + (destino.radio - radioInicial) * progresoSuave);
            circulo.setStyle({
                fillColor: mezclarColores(colorInicial, destino.color, progresoSuave),
                fillOpacity: opacidadInicial + (0.35 - opacidadInicial) * progresoSuave
            });
        });

        if (progreso < 1) {
            animacionCalor = requestAnimationFrame(avanzar);
            return;
        }

        circulosSalientes.forEach(circulo => map.removeLayer(circulo));
        circulosDestino.forEach((circulo, indice) => {
            const destino = destinos[indice];
            circulo.setLatLng(destino.centro);
            circulo.setRadius(destino.radio);
            circulo.setStyle({ fillColor: destino.color, fillOpacity: 0.35 });
        });
        circulosCalor = circulosDestino;
        animacionCalor = null;
    }

    animacionCalor = requestAnimationFrame(avanzar);
}

function dibujarZonasCalor() {
    const centrosEnPantalla = zonasBaseCalor.map(zona => map.latLngToContainerPoint(zona.centro));
    const zonasVisitadas = new Set();
    const destinos = [];

    zonasBaseCalor.forEach((zonaInicial, indiceInicial) => {
        if (zonasVisitadas.has(indiceInicial)) {
            return;
        }

        const indicesGrupo = [indiceInicial];
        zonasVisitadas.add(indiceInicial);

        for (let indiceCola = 0; indiceCola < indicesGrupo.length; indiceCola++) {
            const indiceActual = indicesGrupo[indiceCola];

            zonasBaseCalor.forEach((zonaCandidata, indiceCandidato) => {
                if (zonasVisitadas.has(indiceCandidato)) {
                    return;
                }

                if (centrosEnPantalla[indiceActual].distanceTo(centrosEnPantalla[indiceCandidato]) <= DISTANCIA_UNION_CALOR) {
                    zonasVisitadas.add(indiceCandidato);
                    indicesGrupo.push(indiceCandidato);
                }
            });
        }

        const grupo = indicesGrupo.map(indice => zonasBaseCalor[indice]);
        const cantidadDelitos = grupo.reduce((total, zona) => total + zona.delitos.length, 0);
        const sumaCoordenadas = grupo.reduce((suma, zona) => {
            suma.lat += zona.centro[0] * zona.delitos.length;
            suma.lng += zona.centro[1] * zona.delitos.length;
            return suma;
        }, { lat: 0, lng: 0 });
        const centro = [
            sumaCoordenadas.lat / cantidadDelitos,
            sumaCoordenadas.lng / cantidadDelitos
        ];
        const radio = Math.max(...grupo.map(zona => map.distance(centro, zona.centro) + zona.radio));
        const colorZona = cantidadDelitos <= 3
            ? '#F4FC47'
            : cantidadDelitos <= 7
                ? '#FF980A'
                : '#A81C08';

        const popup = `
            <div style="min-width: 190px; font-family: sans-serif;">
                <span style="font-size: 11px; font-weight: bold; text-transform: uppercase; color: ${colorZona};">
                    🔥 Zona de riesgo
                </span>
                <h4 style="margin: 4px 0 6px 0; font-size: 14px;">${cantidadDelitos} delitos</h4>
                <p style="margin: 0; font-size: 12px; color: #333;">
                    ${grupo.length > 1 ? 'Zonas unificadas' : 'Delitos registrados'} dentro de un radio de <b>${Math.round(radio)} metros</b>.
                </p>
            </div>
        `;

        destinos.push({ centro, radio, color: colorZona, popup });
    });

    animarCirculosCalor(destinos);
}

map.on('zoomend', function() {
    if (calorActivo) {
        dibujarZonasCalor();
    }
});
const btnCalor = document.getElementById('btnCalor');

btnCalor.addEventListener('click', function () {

    calorActivo = !calorActivo;
    map.getContainer().classList.toggle('mapa-calor-activo', calorActivo);

    if (calorActivo) {

        btnCalor.classList.remove('btn-light');
        btnCalor.classList.add('btn-danger');

        btnCalor.innerHTML = `
            <img src="assets/Img/fuego.png"
                 alt="Calor"
                 class="img-fuego">
            Ocultar calor
        `;

        if (window.reportesMapa) {
            generarMapaCalor(window.reportesMapa);
        }

    } else {

        btnCalor.classList.remove('btn-danger');
        btnCalor.classList.add('btn-light');

        btnCalor.innerHTML = `
            <img src="assets/Img/fuego.png"
                 alt="Calor"
                 class="img-fuego">
            Calor
        `;

        // Eliminar círculos
        if (animacionCalor !== null) {
            cancelAnimationFrame(animacionCalor);
            animacionCalor = null;
        }
        secuenciaAnimacionCalor++;
        circulosCalor.forEach(circulo => {
            map.removeLayer(circulo);
        });

        circulosCalor = [];
        zonasBaseCalor = [];
    }
});

// ==========================================
// CONTROL DE CAPAS Y FILTROS POR SUBTIPO
// ==========================================

const btnFiltroTodo = document.getElementById('btnFiltroTodo');
const btnFiltroRobos = document.getElementById('btnFiltroRobos');
const btnFiltroComunidad = document.getElementById('btnFiltroComunidad');
const panelRobos = document.getElementById('subfiltros-robos');
const panelComunidad = document.getElementById('subfiltros-comunidad');

function actualizarEstilosFiltro(botonActivo) {
    [btnFiltroTodo, btnFiltroRobos, btnFiltroComunidad].forEach(btn => {
        btn.classList.remove('btn-dark');
        btn.classList.add('btn-white', 'bg-white', 'border');
    });
    botonActivo.classList.add('btn-dark');
    botonActivo.classList.remove('btn-white', 'bg-white', 'border');
}

// Muestra u oculta las capas en el mapa segun la categoria
function filtrarCategoriasPrincipales(categoria) {
    gruposReportes.forEach((grupo, clave) => {
        const [cat] = clave.split(':');

        if (categoria === 'todo') {
            if (!map.hasLayer(grupo)) map.addLayer(grupo);
        } else if (cat === categoria) {
            if (!map.hasLayer(grupo)) map.addLayer(grupo);
        } else {
            if (map.hasLayer(grupo)) map.removeLayer(grupo);
        }
    });
}
// boton "Todo" 
btnFiltroTodo.addEventListener('click', () => {
    actualizarEstilosFiltro(btnFiltroTodo);
    panelRobos.classList.add('d-none');
    panelComunidad.classList.add('d-none');
    filtrarCategoriasPrincipales('todo');
});

// boton "Robos"
btnFiltroRobos.addEventListener('click', () => {
    actualizarEstilosFiltro(btnFiltroRobos);
    panelComunidad.classList.add('d-none');
    panelRobos.classList.toggle('d-none');
    filtrarCategoriasPrincipales('delito');
});

// boton "Comunidad"
btnFiltroComunidad.addEventListener('click', () => {
    actualizarEstilosFiltro(btnFiltroComunidad);
    panelRobos.classList.add('d-none');
    panelComunidad.classList.toggle('d-none');
    filtrarCategoriasPrincipales('comunitario');
});

// Filtrado de las subclases
document.querySelectorAll('.filtro-subtipo').forEach(checkbox => {
    checkbox.addEventListener('change', (e) => {
        const cat = e.target.getAttribute('data-cat').trim();
        const subtipo = e.target.value.trim();
        const clave = `${cat}:${subtipo}`;

        if (gruposReportes.has(clave)) {
            const grupo = gruposReportes.get(clave);
            if (e.target.checked) {
                if (!map.hasLayer(grupo)) map.addLayer(grupo);
            } else {
                if (map.hasLayer(grupo)) map.removeLayer(grupo);
            }
        }
    });
});




