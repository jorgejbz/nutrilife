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


    const today =

        new Date();


    let currentDate =

        new Date(

            today.getFullYear(),

            today.getMonth(),

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


        element.addEventListener(

            'click',

            function () {

                showDayAppointments(

                    realDate,

                    dayAppointments

                );

            }

        );


        calendarGrid.appendChild(

            element

        );

    }


    function showDayAppointments(date, dayAppointments) {

        selectedDayTitle.textContent =
            date.getDate() +
            ' de ' +
            months[date.getMonth()] +
            ' de ' +
            date.getFullYear();

        if (dayAppointments.length === 0) {
            selectedAppointments.innerHTML = `
                <div class="select-day-message">
                    <i class="far fa-calendar"></i>
                    <p>No hay citas programadas para este día.</p>
                </div>
            `;
            return;
        }

        const csrfToken = data.csrfToken;
        let html = '';

        dayAppointments.forEach(function (appointment) {
            const time = appointment.date.substring(11, 16);

            let cancelButton = '';

            if (
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
                <div class="selected-appointment">
                    <span class="selected-appointment-time">${time}</span>

                    <h5>${escapeHtml(appointment.patient)}</h5>
                    <p>${escapeHtml(appointment.reason)}</p>

                    <span class="selected-status">
                        ${escapeHtml(appointment.status)}
                    </span>

                    <div class="appointment-actions">
                        ${appointment.edit_url ? `
                        <a
                            href="${escapeHtml(appointment.edit_url)}"
                            class="appointment-action edit"
                        >
                            <i class="fas fa-pen"></i>
                            Editar
                        </a>` : ''}

                        ${cancelButton}

                        <form
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
                        </form>
                    </div>
                </div>
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


                currentDate =

                    new Date(

                        today.getFullYear(),

                        today.getMonth(),

                        1

                    );


                renderCalendar();

            }

        );


    renderCalendar();

});

