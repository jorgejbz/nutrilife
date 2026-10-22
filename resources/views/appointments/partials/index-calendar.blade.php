{{-- =========================================================
     APPOINTMENTS INDEX — CALENDARIO
     Calendario interactivo + panel de hoy/próximas citas (ver resources/js/pages/appointments-index.js)
     ========================================================= --}}

    <div class="calendar-layout">


        <div class="calendar-glass-card">


            <div class="calendar-card-header">

                <div>

                    <span class="calendar-eyebrow">

                        CALENDARIO MENSUAL

                    </span>

                    <h3 id="calendarTitle">

                        Calendario

                    </h3>

                </div>


                <div class="calendar-controls">

                    <button

                        type="button"

                        class="calendar-control"

                        id="prevMonth"

                    >

                        <i class="fas fa-chevron-left"></i>

                    </button>


                    <button

                        type="button"

                        class="today-button"

                        id="todayButton"

                    >

                        Hoy

                    </button>


                    <button

                        type="button"

                        class="calendar-control"

                        id="nextMonth"

                    >

                        <i class="fas fa-chevron-right"></i>

                    </button>

                </div>

            </div>


            @if ($isClinic && $nutritionists->count() > 1)
                <div class="calendar-nutritionist-filter nl-input">
                    <i class="fas fa-user-doctor" aria-hidden="true"></i>
                    <label for="calendarNutritionist" class="visually-hidden">Nutriólogo</label>
                    <select id="calendarNutritionist">
                        <option value="">Todo el equipo</option>
                        @foreach ($nutritionists as $nutritionist)
                            <option value="{{ $nutritionist->id }}">{{ $nutritionist->name }}{{ $nutritionist->isClinicMember() ? '' : ' (cuenta principal)' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif


            <div class="calendar-week">

                <div>LUN</div>

                <div>MAR</div>

                <div>MIÉ</div>

                <div>JUE</div>

                <div>VIE</div>

                <div>SÁB</div>

                <div>DOM</div>

            </div>


            <div

                class="calendar-grid"

                id="calendarGrid"

                aria-label="Días del mes: elige uno para ver sus citas"

            >

            </div>


            <div class="calendar-legend">

                <div>

                    <span class="legend-dot appointment-color"></span>

                    Con cita

                </div>


                <div>

                    <span class="legend-dot today-color"></span>

                    Hoy

                </div>


                <div>

                    <span class="legend-dot multiple-color"></span>

                    Varias citas

                </div>

            </div>

        </div>


        <div class="side-panel">


            <div class="today-glass">

                <span class="today-label">

                    HOY

                </span>

                <div class="today-date">

                    <div

                        class="today-number"

                        id="todayNumber"

                    >

                    </div>

                    <div>

                        <h4 id="todayDay"></h4>

                        <p id="todayMonth"></p>

                    </div>

                </div>


                <div class="today-message">

                    <i class="fas fa-seedling"></i>

                    <span>

                        Un buen seguimiento comienza

                        con una agenda organizada.

                    </span>

                </div>

            </div>


            <div class="upcoming-glass">

                <div class="upcoming-title">

                    <div>

                        <span>

                            AGENDA

                        </span>

                        <h4>

                            Próximas citas

                        </h4>

                    </div>


                    <div class="mini-calendar-icon">

                        <i class="far fa-calendar"></i>

                    </div>

                </div>


                @if($upcomingAppointments->isEmpty())

                    <div class="empty-upcoming">

                        <div>

                            <i class="far fa-calendar-check"></i>

                        </div>

                        <h5>

                            Agenda libre

                        </h5>

                        <p>

                            No tienes próximas citas programadas.

                        </p>

                    </div>


                @else


                    <div class="upcoming-list">


                        @foreach($upcomingAppointments as $appointment)

                            <div class="upcoming-item">


                                <div class="appointment-time">

                                    <span>

                                        {{ $appointment->appointment_at->format('H:i') }}

                                    </span>

                                    <small>

                                        {{ $appointment->appointment_at->format('d/m') }}

                                    </small>

                                </div>


                                <div class="upcoming-info">

                                    <h5>

                                        {{ $appointment->patient->first_name }}

                                        {{ $appointment->patient->last_name }}

                                    </h5>


                                    <p>

                                        {{ $appointment->reason ?? 'Consulta nutricional' }}

                                    </p>

                                    @if ($isClinic)
                                        <p class="upcoming-nutritionist">
                                            <i class="fas fa-user-doctor" aria-hidden="true"></i>
                                            {{ $appointment->user?->name }}
                                        </p>
                                    @endif


                                    @if($appointment->status === 'Confirmada')

                                        <span class="mini-status confirmed">

                                            Confirmada

                                        </span>

                                    @elseif($appointment->status === 'Cancelada')

                                        <span class="mini-status cancelled">

                                            Cancelada

                                        </span>

                                    @elseif($appointment->status === 'Completada')

                                        <span class="mini-status completed">

                                            Completada

                                        </span>

                                    @else

                                        <span class="mini-status pending">

                                            Pendiente

                                        </span>

                                    @endif

                                </div>


                            </div>

                        @endforeach


                    </div>


                @endif


                <a

                    href="{{ route('citas.create') }}"

                    class="new-appointment-glass"

                >

                    <i class="fas fa-plus"></i>

                    Programar cita

                </a>

            </div>

        </div>

    </div>


    <div

        class="selected-day-panel"

        id="selectedDayPanel"

    >

        <div class="selected-header">

            <div>

                <span>

                    DETALLE DEL DÍA

                </span>

                <h4 id="selectedDayTitle">

                    Selecciona un día

                </h4>

                <small class="selected-day-count" id="selectedDayCount"></small>

            </div>


            <div class="selected-icon">

                <i class="far fa-calendar-check" aria-hidden="true"></i>

            </div>

        </div>


        {{-- Lo llena appointments-index.js al elegir un día del calendario --}}
        <div

            class="selected-appointments"

            id="selectedAppointments"

            aria-live="polite"

        >

            <div class="select-day-message">

                <i class="far fa-hand-pointer"></i>

                <p>

                    Selecciona una fecha del calendario

                    para consultar sus citas.

                </p>

            </div>

        </div>

    </div>
