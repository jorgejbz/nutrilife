{{-- =========================================================
     PATIENTS SHOW — GRID SUPERIOR
     Tarjetas de información (contacto, objetivo, etc.)
     ========================================================= --}}

    {{-- =====================================================
         GRID SUPERIOR
    ====================================================== --}}

    <div class="top-grid">


        {{-- COLUMNA IZQUIERDA --}}
        <div class="left-column">


            {{-- CONTACTO --}}
            <div class="glass-card">


                <div class="card-heading">

                    <div>

                        <span>
                            CONTACTO
                        </span>

                        <h4>
                            Información de contacto
                        </h4>

                    </div>


                    <div class="heading-icon">

                        <i class="far fa-address-card"></i>

                    </div>

                </div>


                <div class="contact-list">


                    <div class="contact-item">

                        <div class="contact-icon">

                            <i class="fas fa-phone-alt"></i>

                        </div>

                        <div>

                            <span>
                                Teléfono
                            </span>

                            <strong>
                                {{ $paciente->phone ?? 'Sin teléfono' }}
                            </strong>

                        </div>

                    </div>


                    <div class="contact-item">

                        <div class="contact-icon">

                            <i class="far fa-envelope"></i>

                        </div>

                        <div>

                            <span>
                                Correo electrónico
                            </span>

                            <strong>
                                {{ $paciente->email ?? 'Sin correo' }}
                            </strong>

                        </div>

                    </div>


                    {{-- Clínicas: quién atiende al paciente (patients.user_id) --}}
                    @if ($inClinic && $responsible)

                        <div class="contact-item contact-item--responsible">

                            <div class="contact-icon">
                                <i class="fas fa-user-doctor" aria-hidden="true"></i>
                            </div>

                            <div>

                                <span>
                                    Nutriólogo responsable
                                </span>

                                <strong>
                                    {{ $responsible->name }}
                                </strong>

                                <small class="responsible-note">
                                    {{ $responsibleIsMain ? 'Cuenta principal de la clínica' : 'Subperfil de la clínica' }}{{ $canTreat ? ' · tú' : '' }}
                                </small>

                            </div>

                        </div>

                    @endif


                </div>

            </div>



            {{-- OBJETIVO --}}
            <div class="goal-glass">


                <div class="goal-icon">

                    <i class="fas fa-bullseye"></i>

                </div>


                <span class="goal-label">
                    OBJETIVO NUTRICIONAL
                </span>


                <h4>
                    Meta del paciente
                </h4>


                <p>

                    {{ $paciente->goal
                        ?? 'No se ha registrado un objetivo nutricional.' }}

                </p>

            </div>



            {{-- INFO PRIVADA --}}
            <div class="privacy-glass">

                <div>

                    <i class="fas fa-shield-alt"></i>

                </div>

                <p>

                    <strong>
                        Expediente privado
                    </strong>

                    La información de este paciente está vinculada
                    a tu cuenta de NutriLife.

                </p>

            </div>


        </div>



        {{-- COLUMNA DERECHA --}}
        <div class="right-column">


            {{-- INFORMACIÓN GENERAL --}}
            <div class="glass-card">


                <div class="card-heading">

                    <div>

                        <span>
                            INFORMACIÓN GENERAL
                        </span>

                        <h4>
                            Datos del paciente
                        </h4>

                    </div>


                    <div class="heading-icon">

                        <i class="far fa-user"></i>

                    </div>

                </div>



                <div class="general-info-grid">


                    <div class="general-info-item">

                        <div class="info-icon">

                            <i class="far fa-calendar-alt"></i>

                        </div>

                        <div>

                            <span>
                                Fecha de nacimiento
                            </span>

                            <strong>

                                {{ $paciente->birth_date
                                    ? $paciente->birth_date->format('d/m/Y')
                                    : 'No registrada'
                                }}

                            </strong>

                        </div>

                    </div>



                    <div class="general-info-item">

                        <div class="info-icon">

                            <i class="fas fa-birthday-cake"></i>

                        </div>

                        <div>

                            <span>
                                Edad
                            </span>

                            <strong>

                                {{ $paciente->birth_date
                                    ? $paciente->birth_date->age . ' años'
                                    : '—'
                                }}

                            </strong>

                        </div>

                    </div>



                    <div class="general-info-item">

                        <div class="info-icon">

                            <i class="fas fa-venus-mars"></i>

                        </div>

                        <div>

                            <span>
                                Sexo
                            </span>

                            <strong>

                                {{ $paciente->sex ?? 'No registrado' }}

                            </strong>

                        </div>

                    </div>



                    <div class="general-info-item">

                        <div class="info-icon">

                            <i class="fas fa-briefcase"></i>

                        </div>

                        <div>

                            <span>
                                Ocupación
                            </span>

                            <strong>

                                {{ $paciente->occupation ?? 'No registrada' }}

                            </strong>

                        </div>

                    </div>



                    <div class="general-info-item">

                        <div class="info-icon">

                            <i class="fas fa-user-plus"></i>

                        </div>

                        <div>

                            <span>
                                Fecha de registro
                            </span>

                            <strong>

                                {{ $paciente->created_at?->format('d/m/Y') ?? 'Sin fecha' }}

                            </strong>

                        </div>

                    </div>



                    <div class="general-info-item">

                        <div class="info-icon">

                            <i class="fas fa-clipboard-check"></i>

                        </div>

                        <div>

                            <span>
                                Evaluaciones
                            </span>

                            <strong>

                                {{ $evaluations->count() }}

                            </strong>

                        </div>

                    </div>


                </div>

            </div>


        </div>

    </div>



