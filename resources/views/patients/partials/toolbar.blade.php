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

            {{-- Filtro por estado (patients.active); se combina con la búsqueda --}}
            <div class="patient-status-filter" role="group" aria-label="Filtrar por estado">
                <button type="button" class="diet-chip is-selected" data-status-filter="" aria-pressed="true">
                    Todos <span>{{ $patients->count() }}</span>
                </button>
                <button type="button" class="diet-chip" data-status-filter="activos" aria-pressed="false">
                    <i class="fas fa-check" aria-hidden="true"></i>
                    Activos <span>{{ $patients->where('active', true)->count() }}</span>
                </button>
                <button type="button" class="diet-chip" data-status-filter="inactivos" aria-pressed="false">
                    <i class="fas fa-pause" aria-hidden="true"></i>
                    Inactivos <span>{{ $patients->where('active', false)->count() }}</span>
                </button>
            </div>

            <span class="patient-count">

                <i class="fas fa-user-friends me-1"></i>

                {{ $patients->count() }}

                {{ $patients->count() === 1 ? 'paciente' : 'pacientes' }}

            </span>

        </div>

    </div>
