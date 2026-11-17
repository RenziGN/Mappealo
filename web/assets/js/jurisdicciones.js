(function () {
    if (typeof map === 'undefined') return;

    const URL_DATOS = 'assets/data/jurisdicciones.json';
    const capa = L.layerGroup();
    let datos = null;
    let poligonos = [];

    async function cargar() {
        if (datos) return datos;

        const respuesta = await fetch(URL_DATOS);
        if (!respuesta.ok) throw new Error('No se pudo cargar ' + URL_DATOS);
        datos = await respuesta.json();

        datos.zonas.forEach(zona => {
            const poligono = L.polygon(zona.limite, {
                color: zona.color,
                weight: 2.5,
                dashArray: '8 6',
                fillColor: zona.color,
                fillOpacity: 0.1,
                interactive: false
            });

            poligono.bindTooltip(`<strong>${zona.nombre}</strong><span>${zona.partido}</span>`, {
                permanent: true,
                direction: 'center',
                className: 'etiqueta-jurisdiccion',
                interactive: false
            });

            poligono.addTo(capa);
            poligonos.push(poligono);
        });

        return datos;
    }

    async function mostrar(activar) {
        if (!activar) {
            map.removeLayer(capa);
            return;
        }

        try {
            await cargar();
        } catch (e) {
            console.error(e);
            alert('No se pudieron cargar las jurisdicciones. Intentá nuevamente.');
            const interruptor = document.getElementById('toggleDrawerJurisdiccion');
            if (interruptor) interruptor.checked = false;
            return;
        }

        capa.addTo(map);

        const limites = L.latLngBounds([]);
        poligonos.forEach(p => limites.extend(p.getBounds()));
        if (limites.isValid()) {
            map.fitBounds(limites, { padding: [30, 30] });
        }
    }

    window.toggleCapaJurisdicciones = mostrar;
})();
