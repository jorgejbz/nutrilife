{{-- =========================================================
     QUICK ACTIONS
     Acciones rápidas del dashboard
     ========================================================= --}}

<section class="dashboard-quick-actions">

    <div class="dashboard-section-heading">

        <div>
            <span class="dashboard-section-heading__eyebrow">
                Accesos rápidos
            </span>

            <h2 class="dashboard-section-heading__title">
                ¿Qué deseas hacer?
            </h2>
        </div>

        <i class="fas fa-bolt"></i>

    </div>


    <div class="dashboard-quick-actions__grid">

        {{-- Nuevo paciente --}}
        @if(Route::has('pacientes.create'))
            <a
                href="{{ route('pacientes.create') }}"
                class="quick-action-card"
            >
                <span class="quick-action-card__icon">
                    <i class="fas fa-user-plus"></i>
                </span>

                <span class="quick-action-card__content">
                    <strong>Nuevo paciente</strong>
                    <small>Registrar un nuevo paciente</small>
                </span>

                <i class="fas fa-arrow-right quick-action-card__arrow"></i>
            </a>
        @endif


        {{-- Nueva cita --}}
        @if(Route::has('citas.create'))
            <a
                href="{{ route('citas.create') }}"
                class="quick-action-card"
            >
                <span class="quick-action-card__icon">
                    <i class="fas fa-calendar-plus"></i>
                </span>

                <span class="quick-action-card__content">
                    <strong>Nueva cita</strong>
                    <small>Programar una consulta</small>
                </span>

                <i class="fas fa-arrow-right quick-action-card__arrow"></i>
            </a>
        @endif


        {{-- Pacientes --}}
        @if(Route::has('pacientes.index'))
            <a
                href="{{ route('pacientes.index') }}"
                class="quick-action-card"
            >
                <span class="quick-action-card__icon">
                    <i class="fas fa-users"></i>
                </span>

                <span class="quick-action-card__content">
                    <strong>Pacientes</strong>
                    <small>Consultar pacientes registrados</small>
                </span>

                <i class="fas fa-arrow-right quick-action-card__arrow"></i>
            </a>
        @endif


        {{-- Citas --}}
        @if(Route::has('citas.index'))
            <a
                href="{{ route('citas.index') }}"
                class="quick-action-card"
            >
                <span class="quick-action-card__icon">
                    <i class="fas fa-calendar-alt"></i>
                </span>

                <span class="quick-action-card__content">
                    <strong>Agenda</strong>
                    <small>Consultar citas programadas</small>
                </span>

                <i class="fas fa-arrow-right quick-action-card__arrow"></i>
            </a>
        @endif

    </div>

</section>