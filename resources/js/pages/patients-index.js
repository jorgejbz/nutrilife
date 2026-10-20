/**
 * ==========================================================
 * PATIENTS INDEX
 * Filtro en vivo de las tarjetas de pacientes:
 *  - búsqueda por nombre, correo o teléfono (dataset.patient ya
 *    viene normalizado desde el blade),
 *  - estado: Todos / Activos / Inactivos (dataset.status).
 * Los dos filtros se combinan.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('patientSearch');
    const cards = document.querySelectorAll('.patient-glass-card');
    const noResults = document.getElementById('noResults');
    const statusButtons = document.querySelectorAll('[data-status-filter]');

    if (!searchInput) {
        return;
    }

    let status = '';

    function apply() {
        const search = searchInput.value.toLowerCase().trim();
        let visible = 0;

        cards.forEach(function (card) {
            const matches = card.dataset.patient.toLowerCase().includes(search)
                && (!status || card.dataset.status === status);

            card.style.display = matches ? '' : 'none';
            visible += matches ? 1 : 0;
        });

        if (noResults) {
            noResults.style.display = visible === 0 ? 'block' : 'none';
        }
    }

    searchInput.addEventListener('input', apply);

    statusButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            status = button.dataset.statusFilter;

            statusButtons.forEach(function (other) {
                const selected = other === button;
                other.classList.toggle('is-selected', selected);
                other.setAttribute('aria-pressed', String(selected));
            });

            apply();
        });
    });

});
