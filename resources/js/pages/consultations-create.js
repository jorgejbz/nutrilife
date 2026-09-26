/**
 * ==========================================================
 * CONSULTATIONS CREATE
 * Contadores de caracteres para observaciones y recomendaciones.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    function bindCounter(fieldId, counterId) {
        const field = document.getElementById(fieldId);
        const counter = document.getElementById(counterId);

        if (!field || !counter) {
            return;
        }

        const update = () => { counter.textContent = field.value.length; };
        field.addEventListener('input', update);
        update();
    }

    bindCounter('observationsField', 'observationsCount');
    bindCounter('recommendationsField', 'recommendationsCount');

});
