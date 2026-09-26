{{-- =========================================================
     PATIENTS INDEX — MÉTRICAS
     Total, activos e inactivos
     ========================================================= --}}

    {{-- MÉTRICAS --}}
    <div class="stats-grid">

        <div class="liquid-stat">

            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>

            <div>
                <span>Total pacientes</span>

                <h3>
                    {{ $patients->count() }}
                </h3>
            </div>

        </div>


        <div class="liquid-stat">

            <div class="stat-icon active-icon">
                <i class="fas fa-user-check"></i>
            </div>

            <div>
                <span>Pacientes activos</span>

                <h3>
                    {{ $patients->where('active', true)->count() }}
                </h3>
            </div>

        </div>


        <div class="liquid-stat">

            <div class="stat-icon inactive-icon">
                <i class="fas fa-user-clock"></i>
            </div>

            <div>
                <span>Inactivos</span>

                <h3>
                    {{ $patients->where('active', false)->count() }}
                </h3>
            </div>

        </div>

    </div>
