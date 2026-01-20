/* ===== VALIDACIÓN EN VIVO DEL NOMBRE ===== */
document.addEventListener("DOMContentLoaded", function () {
    const campoNombre = document.getElementById("nombre");

    if (campoNombre) {
        campoNombre.addEventListener("input", function () {

            // Solo letras y espacios (incluye tildes y ñ)
            const regex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/;

            if (!regex.test(campoNombre.value)) {
                campoNombre.classList.add("input-error");
            } else {
                campoNombre.classList.remove("input-error");
            }
        });
    }
});

/* ===== VALIDACIÓN EN VIVO DE CONTRASEÑA FUERTE ===== */
document.addEventListener("DOMContentLoaded", function () {
    const pass = document.getElementById("password");

    if (pass) {
        pass.addEventListener("input", function () {
            const regexPass = /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{6,12}$/;

            if (!regexPass.test(pass.value)) {
                pass.classList.add("input-error");
            } else {
                pass.classList.remove("input-error");
            }
        });
    }
});
