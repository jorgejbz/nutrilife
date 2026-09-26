{{-- =========================================================
     PATIENTS INDEX — BUSCADOR
     Filtro en vivo (ver partials/js patients-index.js)
     ========================================================= --}}

    {{-- BUSCADOR --}}
    <div class="glass-toolbar">

        <div class="search-glass">

            <i class="fas fa-search"></i>

            <input
                type="text"
                id="patientSearch"
                placeholder="Buscar paciente..."
                autocomplete="off"
            >

        </div>


        <div class="toolbar-right">

            <span class="patient-count">

                <i class="fas fa-user-friends mr-1"></i>

                {{ $patients->count() }}

                {{ $patients->count() === 1 ? 'paciente' : 'pacientes' }}

            </span>

        </div>

    </div>
