document.addEventListener("DOMContentLoaded", () => {

    /* =====================================================
       ELEMENTOS PRINCIPALES
    ===================================================== */

    const popup = document.querySelector(".popup");
    const overlay = document.querySelector(".overlay");
    const cerrar = document.querySelector(".cerrar");
    const tabs = document.querySelectorAll(".tab");
    const tabContents = document.querySelectorAll(".tab-content");
    const delitoTab = document.querySelector(".delito");
    const comunitarioTab = document.querySelector(".comunitario");
    const btnReportar = document.querySelector(".report-box button");
    btnReportar?.addEventListener("click", () => {
        popup.style.display = "block";
        overlay.style.display = "block";
    });

    let currentTab = "delito";


    /* =====================================================
       CERRAR POPUP
    ===================================================== */

    cerrar?.addEventListener("click", () => {
        popup.style.display = "none";
        overlay.style.display = "none";
    });


    /* =====================================================
       CAMBIO DE TABS
    ===================================================== */

    tabs.forEach((tab, index) => {

        tab.addEventListener("click", () => {

            tabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");

            tabContents.forEach(content => {
                content.classList.add("hidden");
            });

            if (index === 0) {
                delitoTab.classList.remove("hidden");
                currentTab = "delito";
            }

            if (index === 1) {
                comunitarioTab.classList.remove("hidden");
                currentTab = "comunitario";
            }

        });

    });


    /* =====================================================
       TIPO DE ROBO
    ===================================================== */

    const tipoRobo = document.querySelector(
        ".delito select"
    );

    const vehicleSection =
        document.querySelector(".subsection");


    function actualizarCamposRobo() {

        if (!tipoRobo || !vehicleSection) return;

        const tipo = tipoRobo.value;

        const mostrarVehiculo =
            tipo === "Robo de vehículo" ||
            tipo === "Robo de bicicleta / moto" ||
            tipo === "Robo de autopartes";

        vehicleSection.style.display =
            mostrarVehiculo ? "block" : "none";
    }


    tipoRobo?.addEventListener(
        "change",
        actualizarCamposRobo
    );


    actualizarCamposRobo();


    /* =====================================================
       FECHA Y HORA
    ===================================================== */

    const liveCheckboxes =
        document.querySelectorAll(".live-toggle input");


    liveCheckboxes.forEach((checkbox, index) => {

        const container =
            checkbox.closest(".date-row");

        const dateInput =
            container?.querySelector('input[type="date"]');

        const timeInput =
            container?.querySelector('input[type="time"]');


        function actualizarFechaHora() {

            if (!checkbox.checked) {
                detenerLive();
                return;
            }

            actualizar();

            liveInterval = setInterval(
                actualizar,
                1000
            );
        }


        let liveInterval = null;


        function detenerLive() {

            if (liveInterval) {
                clearInterval(liveInterval);
                liveInterval = null;
            }
        }


        function actualizar() {

            const ahora = new Date();

            const fecha =
                ahora.toISOString()
                    .split("T")[0];

            const hora =
                ahora.toTimeString()
                    .substring(0, 5);

            if (dateInput)
                dateInput.value = fecha;

            if (timeInput)
                timeInput.value = hora;
        }


        checkbox.addEventListener(
            "change",
            actualizarFechaHora
        );

    });


    /* =====================================================
       RADIO DE UBICACIÓN
    ===================================================== */

    const radioCards =
        document.querySelectorAll(".radio-card");


    radioCards.forEach(card => {

        card.addEventListener("click", () => {

            radioCards.forEach(c =>
                c.classList.remove("active")
            );

            card.classList.add("active");

            const input =
                card.querySelector("input");

            if (input)
                input.checked = true;

        });

    });


    /* =====================================================
       PREGUNTAS SI / NO
    ===================================================== */

    const questionBlocks =
        document.querySelectorAll(".question");


    questionBlocks.forEach(question => {

        const buttons =
            question.querySelectorAll(".yes-no button");

        buttons.forEach(button => {

            button.addEventListener("click", () => {

                buttons.forEach(b =>
                    b.classList.remove("selected")
                );

                button.classList.add("selected");

            });

        });

    });


    /* =====================================================
       TIPOS DE INCIDENTE COMUNITARIO
    ===================================================== */

    const incidentCards =
        document.querySelectorAll(".incident-card");


    incidentCards.forEach(card => {

        card.addEventListener("click", () => {

            incidentCards.forEach(c =>
                c.classList.remove("active")
            );

            card.classList.add("active");

        });

    });


    /* =====================================================
       CONTADORES DE TEXTAREA
    ===================================================== */

    const textareas =
        document.querySelectorAll("textarea");


    textareas.forEach(textarea => {

        const footer =
            textarea.parentElement
                .querySelector(".textarea-footer");

        if (!footer) return;

        const counter =
            footer.querySelector("span:last-child");

        const maxLength = 500;

        textarea.maxLength = maxLength;


        function actualizarContador() {

            const length =
                textarea.value.length;

            counter.textContent =
                `${length} / ${maxLength}`;

        }


        textarea.addEventListener(
            "input",
            actualizarContador
        );

        actualizarContador();

    });


    /* =====================================================
       UPLOAD DE IMAGEN
    ===================================================== */

    const uploadButtons =
        document.querySelectorAll(".upload button");


    uploadButtons.forEach(button => {

        button.addEventListener("click", () => {

            const input =
                document.createElement("input");

            input.type = "file";

            input.accept =
                "image/png,image/jpeg,image/webp";


            input.addEventListener(
                "change",
                () => {

                    const file =
                        input.files[0];

                    if (!file) return;


                    const maxSize =
                        10 * 1024 * 1024;


                    if (file.size > maxSize) {

                        alert(
                            "La imagen no puede superar los 10 MB."
                        );

                        return;
                    }


                    const allowedTypes = [
                        "image/png",
                        "image/jpeg",
                        "image/webp"
                    ];


                    if (
                        !allowedTypes.includes(
                            file.type
                        )
                    ) {

                        alert(
                            "Formato de imagen no válido."
                        );

                        return;
                    }


                    const upload =
                        button.closest(".upload");


                    const title =
                        upload.querySelector("strong");


                    title.textContent =
                        file.name;


                    button.textContent =
                        "Cambiar imagen";


                    upload.dataset.file =
                        file.name;

                    upload._file = file;

                }
            );


            input.click();

        });

    });


    /* =====================================================
       SELECCIONAR UBICACIÓN
    ===================================================== */

    const mapButtons =
        document.querySelectorAll(".map-button");


    mapButtons.forEach(button => {

        button.addEventListener("click", () => {

            /*
             * Acá posteriormente podés abrir
             * un selector sobre MapLibre,
             * Leaflet o Google Maps.
             */

            alert(
                "Acá se abrirá el selector de ubicación del mapa."
            );

        });

    });


    /* =====================================================
       BOTÓN CANCELAR
    ===================================================== */

    const cancelarButtons = document.querySelectorAll(".cancelar");

      cancelarButtons.forEach(button => {
      button.addEventListener("click", () => {
        if (confirm("¿Querés cancelar el reporte? Se perderán los datos ingresados.")) {
            resetForm();
            popup.style.display = "none";
            overlay.style.display = "none";
        }
    });
   });


    /* =====================================================
       PUBLICAR
    ===================================================== */

const publicarButtons = document.querySelectorAll(".publicar");

publicarButtons.forEach(button => {
    button.addEventListener("click", async () => {
        const validacion = validarFormulario();

        if (!validacion.valido) {
            alert(validacion.mensaje);
            return;
        }

        const reporte = obtenerReporte();

        // Asignar las coordenadas seleccionadas en el mapa
        if (window.coordenadasSeleccionadas) {
            reporte.latitud = window.coordenadasSeleccionadas.lat;
            reporte.longitud = window.coordenadasSeleccionadas.lng;
        } else {
            alert("Por favor seleccioná una ubicación en el mapa antes de publicar.");
            return;
        }

        try {
            const response = await fetch("api/crear_reporte.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(reporte)
            });

            const data = await response.json();

            if (data.status === "success") {
                alert("¡Reporte publicado con éxito!");
                resetForm();
                window.location.reload();
            } else {
                alert("Error: " + (data.message || "No se pudo guardar el reporte"));
            }
        } catch (error) {
            console.error("Error al enviar reporte:", error);
            alert("Error de conexión al guardar el reporte.");
        }
    });
});

    /* =====================================================
       VALIDACIÓN
    ===================================================== */

    function validarFormulario() {

        if (currentTab === "delito") {

            const tipo =
                tipoRobo?.value;

            if (!tipo) {

                return {
                    valido: false,
                    mensaje:
                        "Seleccioná el tipo de robo."
                };

            }


            const descripcion =
                delitoTab
                    .querySelector("textarea")
                    ?.value
                    .trim();


            if (!descripcion ||
                descripcion.length < 20) {

                return {
                    valido: false,
                    mensaje:
                        "La descripción debe tener al menos 20 caracteres."
                };

            }


            const confirmacion =
                delitoTab
                    .querySelector(
                        ".confirmation input"
                    );


            if (
                !confirmacion?.checked
            ) {

                return {
                    valido: false,
                    mensaje:
                        "Debés confirmar que la información proporcionada es correcta."
                };

            }

        }


        if (currentTab === "comunitario") {

            const descripcion =
                comunitarioTab
                    .querySelector("textarea")
                    ?.value
                    .trim();


            if (!descripcion) {

                return {
                    valido: false,
                    mensaje:
                        "Agregá una descripción del incidente."
                };

            }


            const confirmacion =
                comunitarioTab
                    .querySelector(
                        ".confirmation input"
                    );


            if (
                !confirmacion?.checked
            ) {

                return {
                    valido: false,
                    mensaje:
                        "Debés confirmar que la información proporcionada es correcta."
                };

            }

        }


        return {
            valido: true
        };

    }


    /* =====================================================
       OBTENER REPORTE
    ===================================================== */

    function obtenerReporte() {

        if (currentTab === "delito") {

            return obtenerReporteDelito();

        }

        return obtenerReporteComunitario();

    }


    /* =====================================================
       REPORTE DELITO
    ===================================================== */

    function obtenerReporteDelito() {

        const container =
            delitoTab;


        const inputs =
            container.querySelectorAll(
                "input, select, textarea"
            );


        const getInput =
            (selector) =>
                container.querySelector(selector);


        const tipo =
            getInput("select")?.value;


        const fecha =
            getInput('input[type="date"]')?.value;


        const hora =
            getInput('input[type="time"]')?.value;


        const textarea =
            getInput("textarea");


        /* VEHÍCULO */

        const vehicleInputs =
            vehicleSection
                ?.querySelectorAll(
                    "input, select"
                );


        let vehiculo = null;


        if (
            vehicleSection &&
            vehicleSection.style.display !== "none"
        ) {

            vehiculo = {

                marca:
                    vehicleInputs?.[0]?.value || null,

                modelo:
                    vehicleInputs?.[1]?.value || null,

                color:
                    vehicleInputs?.[2]?.value || null,

                patente:
                    vehicleInputs?.[3]?.value || null

            };

        }


        /* GRAVEDAD */

        const questions =
            container.querySelectorAll(
                ".question"
            );


        const violencia =
            obtenerRespuesta(
                questions[0]
            );


        const arma =
            obtenerRespuesta(
                questions[1]
            );


        const multiples =
            obtenerRespuesta(
                questions[2]
            );


        /* UBICACIÓN */

        const locationInput =
            container.querySelector(
                '.field input[placeholder="Calle y altura"]'
            );


        const calles =
            container.querySelectorAll(
                '.grid-2 input'
            );


        /* RADIO */

        const radioActivo =
            container.querySelector(
                ".radio-card.active strong"
            );


        /* EVIDENCIA */

        const upload =
            container.querySelector(
                ".upload"
            );


        const archivo =
            upload?._file || null;


        return {

            tipo: "delito",

            categoria: "robo",

            tipo_robo: tipo,

            fecha: fecha,

            hora: hora,

            fecha_hora:
                `${fecha}T${hora}`,

            en_vivo:
                obtenerLiveStatus(container),


            ubicacion: {

                direccion_privada:
                    locationInput?.value || null,

                entre_calle:
                    calles?.[0]?.value || null,

                y_calle:
                    calles?.[1]?.value || null,

                radio_publico:
                    radioActivo?.textContent || null

            },


            vehiculo: vehiculo,


            gravedad: {

                violencia:
                    violencia,

                arma_fuego:
                    arma,

                multiples_delincuentes:
                    multiples

            },


            descripcion:
                textarea?.value.trim() || "",


            evidencia:
                archivo ? {

                    nombre:
                        archivo.name,

                    tipo:
                        archivo.type,

                    tamaño:
                        archivo.size

                } : null,


            contacto: null,


            metadata: {

                creado_en:
                    new Date().toISOString(),

                estado:
                    "pendiente",

                confiabilidad:
                    null

            }

        };

    }


    /* =====================================================
       REPORTE COMUNITARIO
    ===================================================== */

    function obtenerReporteComunitario() {

        const container =
            comunitarioTab;


        const tipo =
            container
                .querySelector(
                    ".incident-card.active strong"
                )
                ?.textContent
                .trim();


        const fecha =
            container.querySelector(
                'input[type="date"]'
            )?.value;


        const hora =
            container.querySelector(
                'input[type="time"]'
            )?.value;


        const descripcion =
            container
                .querySelector("textarea")
                ?.value
                .trim();


        const upload =
            container.querySelector(
                ".upload"
            );


        const archivo =
            upload?._file || null;


        return {

            tipo: "comunitario",

            incidente:
                tipo || null,


            fecha:
                fecha || null,

            hora:
                hora || null,

            fecha_hora:
                fecha && hora
                    ? `${fecha}T${hora}`
                    : null,


            en_vivo:
                obtenerLiveStatus(
                    container
                ),


            ubicacion: {

                latitud:
                    null,

                longitud:
                    null

            },


            descripcion:
                descripcion || "",


            evidencia:
                archivo ? {

                    nombre:
                        archivo.name,

                    tipo:
                        archivo.type,

                    tamaño:
                        archivo.size

                } : null,


            metadata: {

                creado_en:
                    new Date().toISOString(),

                estado:
                    "pendiente"

            }

        };

    }


    /* =====================================================
       OBTENER RESPUESTA SI / NO
    ===================================================== */

    function obtenerRespuesta(question) {

        if (!question)
            return null;


        const selected =
            question.querySelector(
                ".yes-no .selected"
            );


        if (!selected)
            return null;


        return (
            selected.textContent
                .trim()
                .toLowerCase()
                === "sí"
        );

    }


    /* =====================================================
       LIVE STATUS
    ===================================================== */

    function obtenerLiveStatus(container) {

        return Boolean(
            container.querySelector(
                ".live-toggle input:checked"
            )
        );

    }


    /* =====================================================
       RESET
    ===================================================== */

   function resetForm() {
    document.querySelectorAll(".popup input[type='text'], .popup input[type='date'], .popup input[type='time'], .popup textarea").forEach(el => {
        el.value = "";
    });

    document.querySelectorAll(".popup input[type='checkbox'], .popup input[type='radio']").forEach(el => {
        el.checked = false;
    });

    document.querySelectorAll(".popup .selected").forEach(el => el.classList.remove("selected"));
    document.querySelectorAll(".popup .radio-card.active").forEach(el => el.classList.remove("active"));
    
    // Restaurar textos de ubicación
    document.querySelectorAll('.location-box p').forEach(p => {
        p.textContent = "Seleccioná una ubicación en el mapa o ingresá una dirección.";
    });
}


});