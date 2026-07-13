const map = L.map('map').setView([-34.665864, -58.664922], 15);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap'
}).addTo(map);

map.on('click', function(e) {
    var marker = L.marker((e.latlng),{
        icon:customIcon
    }).addTo(map);  // esto agrega el pin al mapa tomando la latitud y longitud que provee leafleat con el codigo de la linea 3
    marker.bindPopup("<b>Hello world!</b><br>I am a popup.").openPopup(); //esto simplemente agrega un popup de texto, en cuanto podamos crear el reporte deberíamos tomar esa variable y colocarla aca
});

const customIcon = L.icon({
    iconUrl: 'assets/Img/ladron-icon.png',
    iconSize: [40, 40],
    iconAnchor: [20, 40],
    popupAnchor: [0, -40]
}); //esto genera el icono personalizado del marcador