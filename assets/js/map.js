// assets/js/map.js

const map = L.map('map').setView([-34.665864, -58.664922], 14);

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

// Marcador temporal de selección
let seleccionMarker = null;
window.coordenadasSeleccionadas = null;

// Clic en el mapa para marcar ubicación
map.on('click', function(e) {
    window.coordenadasSeleccionadas = {
        lat: e.latlng.lat,
        lng: e.latlng.lng
    };

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
                response.data.forEach(rep => {
                    const esDelito = rep.categoria === 'delito';
                    const icono = esDelito ? ladronIcon : comunidadIcon;
                    const titulo = esDelito 
                        ? (rep.tipo_robo || 'Delito') 
                        : (rep.tipo_incidente || 'Incidente Comunitario');

                    const marker = L.marker([parseFloat(rep.latitud), parseFloat(rep.longitud)], { icon: icono }).addTo(map);

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
            }
        })
        .catch(err => console.error("Error al cargar reportes:", err));
}

cargarReportesEnMapa();