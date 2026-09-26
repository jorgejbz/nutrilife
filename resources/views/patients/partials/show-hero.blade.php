{{-- =========================================================
     PATIENTS SHOW — HERO
     Encabezado con datos generales del paciente
     ========================================================= --}}

    {{-- =====================================================
         HERO DEL PACIENTE
    ====================================================== --}}

    <div class="patient-hero-glass">


        <div class="patient-hero-main">


            <div class="hero-avatar">

                {{ strtoupper(substr($paciente->first_name, 0, 1)) }}
                {{ strtoupper(substr($paciente->last_name, 0, 1)) }}

            </div>


            <div class="hero-patient-info">

                <span class="hero-eyebrow">
                    EXPEDIENTE DEL PACIENTE
                </span>


                <h2>

                    {{ $paciente->first_name }}
                    {{ $paciente->last_name }}

                </h2>


                <p>

                    <i class="fas fa-briefcase"></i>

                    {{ $paciente->occupation ?? 'Sin ocupación registrada' }}

                </p>


                <div class="hero-badges">

                    @if($paciente->active)

                        <span class="status-badge active">

                            <span></span>

                            Paciente activo

                        </span>

                    @else

                        <span class="status-badge inactive">

                            <span></span>

                            Paciente inactivo

                        </span>

                    @endif


                    @if($paciente->sex)

                        <span class="soft-badge">

                            <i class="fas fa-venus-mars"></i>

                            {{ $paciente->sex }}

                        </span>

                    @endif


                    @if($paciente->birth_date)

                        <span class="soft-badge">

                            <i class="far fa-calendar"></i>

                            {{ $paciente->birth_date->age }} años

                        </span>

                    @endif

                </div>

            </div>

        </div>



        <div class="hero-summary">


            <div class="summary-mini">

                <span>
                    EVALUACIONES
                </span>

                <strong>
                    {{ $evaluations->count() }}
                </strong>

            </div>


            <div class="summary-mini">

                <span>
                    REGISTRO
                </span>

                <strong>
                    {{ $paciente->created_at->format('d/m/Y') }}
                </strong>

            </div>


            <div class="hero-decoration">

                <i class="fas fa-seedling"></i>

            </div>

        </div>


    </div>



