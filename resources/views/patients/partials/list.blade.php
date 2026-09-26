{{-- =========================================================
     PATIENTS INDEX — LISTADO
     Estado vacío, grid de tarjetas y mensaje "sin resultados"
     ========================================================= --}}

    @if($patients->isEmpty())


        {{-- ESTADO VACÍO --}}
        <div class="empty-liquid">

            <div class="empty-visual">

                <div class="empty-glass-circle">

                    <i class="fas fa-users"></i>

                </div>

                <span class="floating-leaf leaf-a">
                    <i class="fas fa-leaf"></i>
                </span>

                <span class="floating-leaf leaf-b">
                    <i class="fas fa-seedling"></i>
                </span>

            </div>


            <h3>
                Aún no tienes pacientes
            </h3>

            <p>
                Registra tu primer paciente para comenzar
                a llevar su seguimiento nutricional.
            </p>


            <a href="{{ route('pacientes.create') }}"
               class="btn-liquid-primary">

                <i class="fas fa-plus"></i>
                Agregar paciente

            </a>

        </div>


    @else


        {{-- GRID DE PACIENTES --}}
        <div class="patients-grid" id="patientsGrid">


            @foreach($patients as $patient)

                <div class="patient-glass-card"
                     data-patient="
                        {{ strtolower(
                            $patient->first_name . ' ' .
                            $patient->last_name . ' ' .
                            ($patient->email ?? '') . ' ' .
                            ($patient->phone ?? '')
                        ) }}
                     ">


                    {{-- BRILLO SUPERIOR --}}
                    <div class="glass-shine"></div>


                    {{-- ENCABEZADO TARJETA --}}
                    <div class="patient-card-top">

                        <div class="patient-avatar">

                            {{ strtoupper(substr($patient->first_name, 0, 1)) }}

                        </div>


                        <div class="patient-status">

                            @if($patient->active)

                                <span class="status-active">

                                    <span class="status-dot"></span>

                                    Activo

                                </span>

                            @else

                                <span class="status-inactive">

                                    <span class="status-dot"></span>

                                    Inactivo

                                </span>

                            @endif

                        </div>

                    </div>



                    {{-- DATOS PRINCIPALES --}}
                    <div class="patient-main-info">

                        <span class="patient-label">
                            Paciente
                        </span>

                        <h3>

                            {{ $patient->first_name }}
                            {{ $patient->last_name }}

                        </h3>


                        <div class="patient-contact">

                            @if($patient->email)

                                <div>

                                    <i class="far fa-envelope"></i>

                                    <span>
                                        {{ $patient->email }}
                                    </span>

                                </div>

                            @endif


                            @if($patient->phone)

                                <div>

                                    <i class="fas fa-phone-alt"></i>

                                    <span>
                                        {{ $patient->phone }}
                                    </span>

                                </div>

                            @endif

                        </div>

                    </div>



                    {{-- OBJETIVO --}}
                    <div class="patient-goal">

                        <div class="goal-icon">

                            <i class="fas fa-bullseye"></i>

                        </div>

                        <div>

                            <span>
                                Objetivo
                            </span>

                            <p>
                                {{ $patient->goal ?? 'Sin objetivo registrado' }}
                            </p>

                        </div>

                    </div>



                    {{-- ACCIONES --}}
                    <div class="patient-actions">

                        <a href="{{ route('pacientes.show', $patient) }}"
                           class="glass-action primary-action">

                            <i class="far fa-eye"></i>

                            <span>
                                Ver paciente
                            </span>

                        </a>


                        <a href="{{ route('pacientes.edit', $patient) }}"
                           class="glass-action edit-action"
                           title="Editar paciente">

                            <i class="far fa-edit"></i>

                        </a>

                    </div>


                </div>

            @endforeach


        </div>



        {{-- MENSAJE DE BÚSQUEDA --}}
        <div class="no-results" id="noResults">

            <div>
                <i class="fas fa-search"></i>
            </div>

            <h4>
                No encontramos pacientes
            </h4>

            <p>
                Prueba con otro nombre, correo o teléfono.
            </p>

        </div>


    @endif
