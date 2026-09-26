{{-- =========================================================
     APPOINTMENTS INDEX — MÉTRICAS
     Total, pendientes, confirmadas y completadas
     ========================================================= --}}

    <div class="calendar-stats">

        <div class="liquid-stat">

            <div class="stat-icon">

                <i class="far fa-calendar"></i>

            </div>

            <div>

                <span>Total citas</span>

                <h3>

                    {{ $appointments->count() }}

                </h3>

            </div>

        </div>


        <div class="liquid-stat">

            <div class="stat-icon pending-icon">

                <i class="far fa-clock"></i>

            </div>

            <div>

                <span>Pendientes</span>

                <h3>

                    {{ $appointments->where('status', 'Pendiente')->count() }}

                </h3>

            </div>

        </div>


        <div class="liquid-stat">

            <div class="stat-icon confirmed-icon">

                <i class="fas fa-check"></i>

            </div>

            <div>

                <span>Confirmadas</span>

                <h3>

                    {{ $appointments->where('status', 'Confirmada')->count() }}

                </h3>

            </div>

        </div>


        <div class="liquid-stat">

            <div class="stat-icon completed-icon">

                <i class="fas fa-check-double"></i>

            </div>

            <div>

                <span>Completadas</span>

                <h3>

                    {{ $appointments->where('status', 'Completada')->count() }}

                </h3>

            </div>

        </div>

    </div>


