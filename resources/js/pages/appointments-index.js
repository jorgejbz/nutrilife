/**
 * ==========================================================
 * APPOINTMENTS INDEX — CALENDARIO
 * Calendario interactivo con navegación mes a mes y panel de
 * "citas del día". Lee los datos que la vista inyecta en
 * `window.appointmentsIndexData` — ver
 * resources/views/appointments/index.blade.php.
 * ==========================================================
 */

/**
 * Escapa texto antes de insertarlo con innerHTML (nombres de pacientes,
 * motivos, etc. los escribe el usuario y podrían contener HTML).
 */
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


document.addEventListener('DOMContentLoaded', function () {

    const data = window.appointmentsIndexData;
    if (!data) { return; }



    const appointments = data.appointments;


    const calendarGrid =

        document.getElementById('calendarGrid');

    const calendarTitle =

        document.getElementById('calendarTitle');

    const selectedDayTitle =

        document.getElementById('selectedDayTitle');

    const selectedAppointments =

        document.getElementById('selectedAppointments');

    const selectedDayPanel =

        document.getElementById('selectedDayPanel');

    const selectedDayCount =

        document.getElementById('selectedDayCount');

    // Solo existe para la cuenta principal de una clínica.
    const nutritionistSelect =

        document.getElementById('calendarNutritionist');

    let nutritionistFilter = '';


    /** 'AAAA-MM-DD' → Date local (sin desfase por zona horaria). */
    function parseDay(value) {
        const parts = String(value || '').split('-').map(Number);
        return parts.length === 3 && parts.every(Boolean)
            ? new Date(parts[0], parts[1] - 1, parts[2])
            : null;
    }

    // "Hoy" lo decide el servidor (zona horaria de NutriLife), no el reloj
    // del navegador: así una cita no aparece en el día equivocado.
    const today =

        parseDay(data.today) || new Date();


    // Día abierto en "Detalle del día" (?dia=… o hoy).
    let selectedDate =

        parseDay(data.selectedDay) || today;


    let currentDate =

        new Date(

            selectedDate.getFullYear(),

            selectedDate.getMonth(),

            1

        );


    const months = [

        'Enero',

        'Febrero',

        'Marzo',

        'Abril',

        'Mayo',

        'Junio',

        'Julio',

        'Agosto',

        'Septiembre',

        'Octubre',

        'Noviembre',

        'Diciembre'

    ];


    const days = [

        'Domingo',

        'Lunes',

        'Martes',

        'Miércoles',

        'Jueves',

        'Viernes',

        'Sábado'

    ];


    document.getElementById('todayNumber')

        .textContent =

        today.getDate();


    document.getElementById('todayDay')

        .textContent =

        days[today.getDay()];


    document.getElementById('todayMonth')

        .textContent =

        months[today.getMonth()] +

        ' ' +

        today.getFullYear();


    function dateKey(year, month, day) {

        return (

            year +

            '-' +

            String(month + 1).padStart(2,'0') +

            '-' +

            String(day).padStart(2,'0')

        );

    }


    function getAppointmentsForDay(

        year,

        month,

        day

    ) {

        const key =

            dateKey(

                year,

                month,

                day

            );


        return appointments.filter(function (appointment) {

            // Clínica: filtro "Nutriólogo" de la cabecera del calendario
            if (nutritionistFilter && String(appointment.nutritionist_id) !== nutritionistFilter) {
                return false;
            }

            return appointment.date

                .substring(0,10) === key;

        });

    }


    function renderCalendar() {


        calendarGrid.innerHTML = '';


        const year =

            currentDate.getFullYear();

        const month =

            currentDate.getMonth();


        calendarTitle.textContent =

            months[month] + ' ' + year;


        const firstDay =

            new Date(

                year,

                month,

                1

            );


        const lastDay =

            new Date(

                year,

                month + 1,

                0

            );


        const daysInMonth =

            lastDay.getDate();


        let startingDay =

            firstDay.getDay();

        startingDay =

            startingDay === 0

                ? 6

                : startingDay - 1;


        const previousMonthLastDay =

            new Date(

                year,

                month,

                0

            ).getDate();


        for (

            let i = startingDay - 1;

            i >= 0;

            i--

        ) {

            const day =

                previousMonthLastDay - i;


            createDayElement(

                day,

                true,

                year,

                month - 1

            );

        }


        for (

            let day = 1;

            day <= daysInMonth;

            day++

        ) {

            createDayElement(

                day,

                false,

                year,

                month

            );

        }


        const cells =

            calendarGrid.children.length;


        const remaining =

            42 - cells;


        for (

            let day = 1;

            day <= remaining;

            day++

        ) {

            createDayElement(

                day,

                true,

                year,

                month + 1

            );

        }


        // Si el día elegido no está en esta cuadrícula (se cambió de mes
        // con las flechas), el primer día del mes recibe el foco de teclado.
        if (!calendarGrid.querySelector('[tabindex="0"]')) {
            const first = calendarGrid.querySelector('.calendar-day:not(.other-month)');
            if (first) { first.setAttribute('tabindex', '0'); }
        }


        showDayAppointments(

            selectedDate,

            getAppointmentsForDay(

                selectedDate.getFullYear(),

                selectedDate.getMonth(),

                selectedDate.getDate()

            )

        );

    }


    function createDayElement(

        day,

        otherMonth,

        year,

        month

    ) {


        const realDate =

            new Date(

                year,

                month,

                day

            );


        const realYear =

            realDate.getFullYear();

        const realMonth =

            realDate.getMonth();

        const realDay =

            realDate.getDate();


        const dayAppointments =

            getAppointmentsForDay(

                realYear,

                realMonth,

                realDay

            );


        const element =

            document.createElement('div');


        element.className =

            'calendar-day';


        if (otherMonth) {

            element.classList.add(

                'other-month'

            );

        }


        const isToday =

            realDay === today.getDate() &&

            realMonth === today.getMonth() &&

            realYear === today.getFullYear();


        if (isToday) {

            element.classList.add('today');

        }


        if (dayAppointments.length > 0) {

            element.classList.add(

                'has-event'

            );

        }


        let html =

            '<div class="day-number">' +

            realDay +

            '</div>';


        dayAppointments

            .slice(0,2)

            .forEach(function (appointment) {


                const time =

                    appointment.date

                        .substring(11,16);


                html +=

                    '<div class="calendar-event">' +

                        time +

                        ' · ' +

                        escapeHtml(appointment.patient) +

                    '</div>';

            });


        if (dayAppointments.length > 2) {

            html +=

                '<span class="more-events">' +

                    '+' +

                    (dayAppointments.length - 2) +

                    ' más' +

                '</span>';

        }


        element.innerHTML = html;


        // Cada día es un botón accesible (clic, Enter o Espacio; flechas
        // para moverse entre días).
        const isSelected = sameDay(realDate, selectedDate);

        element.dataset.date = dateKey(realYear, realMonth, realDay);
        element.setAttribute('role', 'button');
        element.setAttribute('tabindex', isSelected ? '0' : '-1');
        element.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        element.setAttribute(
            'aria-label',
            days[realDate.getDay()] + ' ' + realDay + ' de ' + months[realMonth] + ' de ' + realYear +
            ' · ' + (dayAppointments.length === 0
                ? 'sin citas'
                : dayAppointments.length + (dayAppointments.length === 1 ? ' cita' : ' citas'))
        );

        if (isSelected) {
            element.classList.add('is-selected');
        }

        element.addEventListener('click', function () {
            selectDay(realDate, true);
        });

        element.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                selectDay(realDate, true);
                // renderCalendar() rehízo la cuadrícula: devolver el foco.
                const current = calendarGrid.querySelector('[data-date="' + element.dataset.date + '"]');
                if (current) { current.focus(); }
                return;
            }

            const moves = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };

            if (moves[event.key]) {
                event.preventDefault();
                const target = new Date(realYear, realMonth, realDay + moves[event.key]);
                selectDay(target, false);
                const cell = calendarGrid.querySelector(
                    '[data-date="' + dateKey(target.getFullYear(), target.getMonth(), target.getDate()) + '"]'
                );
                if (cell) { cell.focus(); }
            }
        });


        calendarGrid.appendChild(

            element

        );

    }


    function sameDay(a, b) {
        return a.getFullYear() === b.getFullYear() &&
            a.getMonth() === b.getMonth() &&
            a.getDate() === b.getDate();
    }


    /**
     * Marca el día, cambia de mes si hace falta y actualiza el panel.
     * Con `reveal` (clic del usuario) desplaza la vista al panel cuando
     * quedó fuera de pantalla (tablet / celular).
     */
    function selectDay(date, reveal) {

        selectedDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());

        if (
            selectedDate.getMonth() !== currentDate.getMonth() ||
            selectedDate.getFullYear() !== currentDate.getFullYear()
        ) {
            currentDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        }

        renderCalendar();

        if (reveal && selectedDayPanel) {
            const rect = selectedDayPanel.getBoundingClientRect();

            if (rect.top > window.innerHeight - 80 || rect.bottom < 0) {
                selectedDayPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }


    function statusClass(status) {
        return {
            'Pendiente': 'pending',
            'Confirmada': 'confirmed',
            'Completada': 'completed',
            'Cancelada': 'cancelled'
        }[status] || 'pending';
    }


    function showDayAppointments(date, dayAppointments) {

        selectedDayTitle.textContent =
            days[date.getDay()] +
            ' ' +
            date.getDate() +
            ' de ' +
            months[date.getMonth()] +
            ' de ' +
            date.getFullYear();

        if (selectedDayCount) {
            selectedDayCount.textContent = dayAppointments.length === 0
                ? 'Sin citas'
                : dayAppointments.length + (dayAppointments.length === 1 ? ' cita' : ' citas');
        }

        if (dayAppointments.length === 0) {
            selectedAppointments.innerHTML = `
                <div class="select-day-message">
                    <i class="far fa-calendar" aria-hidden="true"></i>
                    <p>No hay citas programadas para este día.</p>
                    <a href="${escapeHtml(data.createUrl)}" class="appointment-action edit">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        Programar cita
                    </a>
                </div>
            `;
            return;
        }

        const csrfToken = data.csrfToken;
        let html = '';

        dayAppointments.forEach(function (appointment) {
            const time = appointment.date.substring(11, 16);

            let cancelButton = '';

            let confirmButton = '';

            if (
                appointment.confirm_url &&
                appointment.status === 'Pendiente'
            ) {
                confirmButton = `
                    <form
                        method="POST"
                        action="${appointment.confirm_url}"
                        class="appointment-action-form"
                        data-confirm="La cita quedará marcada como «Confirmada»."
                        data-confirm-title="¿Confirmar esta cita?"
                        data-confirm-accept="Sí, confirmar cita"
                        data-confirm-cancel="Volver"
                        data-confirm-icon="fa-check"
                    >
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="PATCH">

                        <button
                            type="submit"
                            class="appointment-action confirm"
                        >
                            <i class="fas fa-check"></i>
                            Confirmar
                        </button>
                    </form>
                `;
            }

            if (
                appointment.cancel_url &&
                appointment.status !== 'Cancelada' &&
                appointment.status !== 'Completada'
            ) {
                cancelButton = `
                    <form
                        method="POST"
                        action="${appointment.cancel_url}"
                        class="appointment-action-form"
                        data-confirm="La cita quedará marcada como «Cancelada»."
                        data-confirm-title="¿Cancelar esta cita?"
                        data-confirm-accept="Sí, cancelar cita"
                        data-confirm-cancel="Volver"
                        data-confirm-variant="warning"
                        data-confirm-icon="fa-ban"
                    >
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="PATCH">

                        <button
                            type="submit"
                            class="appointment-action cancel"
                        >
                            <i class="fas fa-ban"></i>
                            Cancelar
                        </button>
                    </form>
                `;
            }

            html += `
                <article class="selected-appointment">
                    <span class="selected-appointment-time">${time}</span>

                    <h5>
                        ${appointment.patient_url
                            ? `<a href="${escapeHtml(appointment.patient_url)}" class="selected-appointment-patient">${escapeHtml(appointment.patient)}</a>`
                            : escapeHtml(appointment.patient)}
                    </h5>
                    <p>${escapeHtml(appointment.reason)}</p>

                    ${appointment.nutritionist ? `
                    <p class="selected-appointment-nutritionist">
                        <i class="fas fa-user-doctor" aria-hidden="true"></i>
                        Con ${escapeHtml(appointment.nutritionist)}${appointment.is_own ? ' (tú)' : ''}
                    </p>` : ''}

                    ${appointment.notes ? `
                    <p class="selected-appointment-notes">
                        <i class="far fa-note-sticky" aria-hidden="true"></i>
                        ${escapeHtml(appointment.notes)}
                    </p>` : ''}

                    <span class="selected-status mini-status ${statusClass(appointment.status)}">
                        ${escapeHtml(appointment.status)}
                    </span>

                    <div class="appointment-actions">
                        ${appointment.patient_url ? `
                        <a
                            href="${escapeHtml(appointment.patient_url)}"
                            class="appointment-action edit"
                        >
                            <i class="fas fa-user" aria-hidden="true"></i>
                            Ver paciente
                        </a>` : ''}

                        ${appointment.consultation_url ? `
                        <a
                            href="${escapeHtml(appointment.consultation_url)}"
                            class="appointment-action edit"
                        >
                            <i class="fas fa-notes-medical" aria-hidden="true"></i>
                            Registrar consulta
                        </a>` : ''}

                        ${confirmButton}

                        ${cancelButton}

                        ${appointment.delete_url ? `<form
                            method="POST"
                            action="${appointment.delete_url}"
                            class="appointment-action-form"
                            data-confirm="Esta acción no se puede deshacer."
                            data-confirm-title="¿Eliminar esta cita?"
                            data-confirm-accept="Sí, eliminar"
                        >
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="DELETE">

                            <button
                                type="submit"
                                class="appointment-action delete"
                            >
                                <i class="fas fa-trash"></i>
                                Eliminar
                            </button>
                        </form>` : ''}
                    </div>

                    ${appointment.is_own === false ? `
                    <small class="selected-appointment-owner-note">
                        Solo ${escapeHtml(appointment.nutritionist)} puede cancelar o eliminar esta cita.
                    </small>` : ''}
                </article>
            `;
        });

        selectedAppointments.innerHTML = html;
    }


    document

        .getElementById('prevMonth')

        .addEventListener(

            'click',

            function () {


                currentDate =

                    new Date(

                        currentDate.getFullYear(),

                        currentDate.getMonth() - 1,

                        1

                    );


                renderCalendar();

            }

        );


    document

        .getElementById('nextMonth')

        .addEventListener(

            'click',

            function () {


                currentDate =

                    new Date(

                        currentDate.getFullYear(),

                        currentDate.getMonth() + 1,

                        1

                    );


                renderCalendar();

            }

        );


    document

        .getElementById('todayButton')

        .addEventListener(

            'click',

            function () {


                selectDay(today, false);

            }

        );


    if (nutritionistSelect) {
        nutritionistSelect.addEventListener('change', function () {
            nutritionistFilter = nutritionistSelect.value;
            renderCalendar();
        });
    }


    renderCalendar();

});

