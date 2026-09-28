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

// Clic en el mapa para marcar ubicación
map.on('click', function(e) {
    window.coordenadasSeleccionadas = {
        lat: e.latlng.lat,
        lng: e.latlng.lng
    };

    popup.style.display = "block";
        overlay.style.display = "block";
    
    if (seleccionMarker) {
        seleccionMarker.setLatLng(e.latlng);
    } else {
        seleccionMarker = L.marker(e.latlng).addTo(map);
    }

    // Actualizar el texto del formulario para que el usuario vea que se fijó la ubicación
    const cajasUbicacion = document.querySelectorAll('.location-box p');
    cajasUbicacion.forEach(p => {
        p.innerHTML = `<span style="color: #27ae60; font-weight: bold;">✓ Coordenadas fijadas:</span> Lat: ${e.latlng.lat.toFixed(4)}, Lng: ${e.latlng.lng.toFixed(4)}`;
    });
});

// Cargar reportes existentes desde la base de datos
function cargarReportesEnMapa() {
    fetch('api/get_reportes.php')
        .then(res => res.json())
        .then(response => {
            if (response.status === 'success') {
                window.reportesMapa = Array.isArray(response.data) ? response.data : [];

                response.data.forEach(rep => {
                    const esDelito = rep.categoria === 'delito';
                    const icono = esDelito ? ladronIcon : comunidadIcon;
                    const titulo = esDelito 
                        ? (rep.tipo_robo || 'Delito') 
                        : (rep.tipo_incidente || 'Incidente Comunitario');
                    const tipoReporte = esDelito
                        ? titulo
                        : (rep.categoria === 'comunitario' ? titulo : (rep.categoria || 'Otro'));
                    const claveGrupo = `${rep.categoria || 'otro'}:${tipoReporte}`;

                    const marker = L.marker([parseFloat(rep.latitud), parseFloat(rep.longitud)], { icon: icono });
                    obtenerGrupoReporte(claveGrupo, tipoReporte, esDelito).addLayer(marker);

                    marker.bindPopup(`
                        <div style="min-width: 170px; font-family: sans-serif;">
                            <span style="font-size: 11px; font-weight: bold; text-transform: uppercase; color: ${esDelito ? '#e74c3c' : '#27ae60'};">
                                ${rep.categoria}
                            </span>
                            <h4 style="margin: 4px 0 6px 0; font-size: 14px;">${titulo}</h4>
                            <p style="margin: 0 0 6px 0; font-size: 12px; color: #333;">${rep.descripcion}</p>
                            <small style="color: #666; font-size: 11px;">📍 ${rep.direccion || 'Sin dirección'}</small><br>
                            <small style="color: #666; font-size: 11px;">👤 Por: <b>${rep.nombre_usuario}</b></small>
                        </div>
                    `);
                });

                if (calorActivo) {
                    generarMapaCalor(window.reportesMapa);
                }
            }
        })
        .catch(err => console.error("Error al cargar reportes:", err));
}

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