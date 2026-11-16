const popup = document.querySelector(".popup");
const overlay = document.querySelector(".overlay");

document.addEventListener("DOMContentLoaded", () => {

    /* =====================================================
       ELEMENTOS PRINCIPALES
    ===================================================== */

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

    const tipoRobo = {
        get value() {
            return document.querySelector(".delito .incident-card.active")?.dataset.valor || "";
        }
    };


    /* =====================================================
       FECHA Y HORA
    ===================================================== */

    function fechaDesdeIso(iso) {
        const partes = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || "");
        return partes ? `${partes[3]}/${partes[2]}/${partes[1]}` : "";
    }

    function isoDesdeFecha(texto) {
        const partes = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec((texto || "").trim());
        if (!partes) return null;

        const dia = Number(partes[1]);
        const mes = Number(partes[2]);
        const anio = Number(partes[3]);
        const fecha = new Date(anio, mes - 1, dia);

        if (fecha.getFullYear() !== anio || fecha.getMonth() !== mes - 1 || fecha.getDate() !== dia) {
            return null;
        }

        return `${partes[3]}-${partes[2]}-${partes[1]}`;
    }

    document.querySelectorAll(".campo-fecha").forEach(campo => {

        const texto = campo.querySelector(".fecha-texto");
        const oculta = campo.querySelector(".fecha-oculta");
        const boton = campo.querySelector(".btn-calendario");

        texto.addEventListener("input", () => {
            const numeros = texto.value.replace(/\D/g, "").slice(0, 8);
            let formateada = numeros.slice(0, 2);
            if (numeros.length > 2) formateada += "/" + numeros.slice(2, 4);
            if (numeros.length > 4) formateada += "/" + numeros.slice(4, 8);
            texto.value = formateada;
            texto.classList.toggle("fecha-invalida", formateada.length === 10 && !isoDesdeFecha(formateada));
        });

        boton.addEventListener("click", () => {
            if (texto.readOnly) return;
            oculta.value = isoDesdeFecha(texto.value) || "";
            if (typeof oculta.showPicker === "function") {
                try {
                    oculta.showPicker();
                    return;
                } catch (e) { }
            }
            oculta.focus();
            oculta.click();
        });

        oculta.addEventListener("change", () => {
            if (!oculta.value) return;
            texto.value = fechaDesdeIso(oculta.value);
            texto.classList.remove("fecha-invalida");
        });

    });

    const liveCheckboxes =
        document.querySelectorAll(".live-toggle input");


    liveCheckboxes.forEach((checkbox, index) => {

        const container =
            checkbox.closest(".date-row");

        const dateInput =
            container?.querySelector(".fecha-texto");

        const botonCalendario =
            container?.querySelector(".btn-calendario");

        const timeInput =
            container?.querySelector('input[type="time"]');


        function actualizarFechaHora() {

            detenerLive();

            const activo = checkbox.checked;

            [dateInput, timeInput].forEach(input => {
                if (!input) return;
                input.readOnly = activo;
                input.classList.toggle("en-vivo", activo);
            });

            if (botonCalendario) botonCalendario.disabled = activo;
            if (dateInput) dateInput.classList.remove("fecha-invalida");

            if (!activo) return;

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
            const dosDigitos = n => String(n).padStart(2, "0");

            const fecha =
                `${dosDigitos(ahora.getDate())}/${dosDigitos(ahora.getMonth() + 1)}/${ahora.getFullYear()}`;

            const hora =
                `${dosDigitos(ahora.getHours())}:${dosDigitos(ahora.getMinutes())}`;

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

            card.closest(".incident-types")
                .querySelectorAll(".incident-card")
                .forEach(c => c.classList.remove("active"));

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
       SELECCIONAR UBICACIÓN
    ===================================================== */

    document.querySelectorAll(".btn-mi-ubicacion").forEach(button => {
        button.addEventListener("click", (e) => {
            e.preventDefault();
            window.usarUbicacionActualEnReporte?.();
        });
    });

    document.querySelectorAll(".btn-elegir-en-mapa").forEach(button => {
        button.addEventListener("click", (e) => {
            e.preventDefault();
            window.iniciarSeleccionEnMapa?.();
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


            if (!isoDesdeFecha(delitoTab.querySelector(".fecha-texto")?.value)) {

                return {
                    valido: false,
                    mensaje:
                        "Ingresá una fecha válida con el formato día/mes/año (por ejemplo, 16/11/2026)."
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


            const fechaComunitario =
                comunitarioTab.querySelector(".fecha-texto")?.value.trim();

            if (fechaComunitario && !isoDesdeFecha(fechaComunitario)) {

                return {
                    valido: false,
                    mensaje:
                        "Ingresá una fecha válida con el formato día/mes/año (por ejemplo, 16/11/2026)."
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


        const fecha =
            isoDesdeFecha(container.querySelector(".fecha-texto")?.value);


        const hora =
            container.querySelector('input[type="time"]')?.value;


        const questions =
            container.querySelectorAll(
                ".question"
            );


        return {

            tipo: "delito",

            categoria: "robo",

            tipo_robo: tipoRobo.value,

            fecha_hora:
                `${fecha}T${hora}`,

            gravedad: {

                violencia:
                    obtenerRespuesta(questions[0]),

                arma_fuego:
                    obtenerRespuesta(questions[1]),

                multiples_delincuentes:
                    obtenerRespuesta(questions[2])

            },

            descripcion:
                container.querySelector("textarea")?.value.trim() || ""

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
            isoDesdeFecha(container.querySelector(".fecha-texto")?.value);


        const hora =
            container.querySelector(
                'input[type="time"]'
            )?.value;


        const descripcion =
            container
                .querySelector("textarea")
                ?.value
                .trim();


        return {

            tipo: "comunitario",

            incidente:
                tipo || null,

            fecha_hora:
                fecha && hora
                    ? `${fecha}T${hora}`
                    : null,

            descripcion:
                descripcion || ""

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
       RESET
    ===================================================== */

   function resetForm() {
    document.querySelectorAll(".popup input[type='text'], .popup input[type='date'], .popup input[type='time'], .popup textarea").forEach(el => {
        el.value = "";
    });

    document.querySelectorAll(".popup input[type='checkbox'], .popup input[type='radio']").forEach(el => {
        el.checked = false;
    });

    document.querySelectorAll(".popup .fecha-invalida").forEach(el => el.classList.remove("fecha-invalida"));

    document.querySelectorAll(".popup .live-toggle input").forEach(el => {
        el.dispatchEvent(new Event("change"));
    });

    document.querySelectorAll(".popup .tipos-robo .incident-card.active").forEach(el => el.classList.remove("active"));

    document.querySelectorAll(".popup .selected").forEach(el => el.classList.remove("selected"));
    
    // Restaurar textos de ubicación
    document.querySelectorAll('.location-box p').forEach(p => {
        p.textContent = "Seleccioná una ubicación en el mapa o ingresá una dirección.";
    });
}


});