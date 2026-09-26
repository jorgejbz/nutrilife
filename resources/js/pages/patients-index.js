/**
 * ==========================================================
 * PATIENTS INDEX
 * Filtro en vivo de la tarjetas de pacientes por nombre,
 * correo o teléfono (dataset.patient ya viene normalizado
 * desde el blade).
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('patientSearch');
    const cards = document.querySelectorAll('.patient-glass-card');
    const noResults = document.getElementById('noResults');

    if (!searchInput) {
        return;
    }

    searchInput.addEventListener('input', function () {
        const search = this.value.toLowerCase().trim();
        let visible = 0;

        cards.forEach(function (card) {
            const patient = card.dataset.patient.toLowerCase();

            if (patient.includes(search)) {
                card.style.display = '';
                visible++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResults) {
            noResults.style.display = visible === 0 ? 'block' : 'none';
        }
    });

});
