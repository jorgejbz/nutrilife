

@extends('adminlte::page')

@section('title', 'Nuevo plan alimenticio | NutriLife')

{{-- =============================================*

     ENCABEZADO

============================================= --}}

@section('content_header')

<div class="consultations-header">

    <div>

        <span class="consultation-eyebrow">

            <i class="fas fa-leaf mr-1"></i>

            NUTRILIFE

        </span>

        <h1>Nuevo plan alimenticio</h1>

        <p>

            Diseña un plan nutricional personalizado

            y establece los objetivos de tu paciente.

        </p>

    </div>

    <a href="{{ route('diet-plans.index') }}"

       class="nl-back-button">

        <i class="fas fa-arrow-left"></i>

        Volver a planes

    </a>

</div>

@stop

{{-- =============================================*

     CONTENIDO

============================================= --}}

@section('content')

<div class="nl-create-page">

    {{-- INTRODUCCIÓN --}}

    <div class="nl-intro">

        <div class="nl-intro-icon">

            <i class="fas fa-clipboard-list"></i>

        </div>

        <div>

            <span>CONFIGURACIÓN NUTRICIONAL</span>

            <h2>Crear plan alimenticio</h2>

            <p>

                Completa la información para registrar

                un nuevo plan.

            </p>

        </div>

    </div>

    {{-- ERRORES DE VALIDACIÓN --}}

    @if($errors->any())

        <div class="nl-validation-alert">

            <i class="fas fa-exclamation-circle"></i>

            <div>

                <strong>Revisa la información</strong>

                <p>

                    Algunos campos necesitan corregirse

                    antes de crear el plan.

                </p>

            </div>

        </div>

    @endif

    <form

        action="{{ route('diet-plans.store') }}"

        method="POST"

        class="nl-form"

    >

        @csrf

        {{-- =====================================*

             01 - PACIENTE

         ===================================== --}}

        <section class="nl-section">

            <div class="nl-section-heading">

                <div class="nl-step-icon">

                    <i class="fas fa-user"></i>

                </div>

                <div class="nl-section-copy">

                    <span>PASO 01 · PACIENTE</span>

                    <h3>Seleccionar paciente</h3>

                    <p>

                        Elige a quién se asignará

                        este plan alimenticio.

                    </p>

                </div>

                <span class="nl-step-number">01</span>

            </div>

            <div class="nl-section-body">

                <div class="nl-field">

                    <label for="patient_id">

                        <i class="far fa-user"></i>

                        Paciente

                        <span class="nl-required">*</span>

                    </label>

                    <div class="nl-select-wrapper">

                        <select

                            name="patient_id"

                            id="patient_id"

                            class="nl-input @error('patient_id') is-invalid @enderror"

                            required

                        >

                            <option value="">

                                Seleccionar paciente...

                            </option>

                            @foreach($patients as $patient)

                                <option

                                    value="{{ $patient->id }}"

                                    @selected(old('patient_id') == $patient->id)

                                >

                                    {{ $patient->first_name }}

                                    {{ $patient->last_name }}

                                </option>

                            @endforeach

                        </select>

                        <i class="fas fa-chevron-down"></i>

                    </div>

                    @error('patient_id')

                        <span class="nl-error">

                            {{ $message }}

                        </span>

                    @enderror

                    <small>

                        Selecciona un paciente previamente registrado.

                    </small>

                </div>

            </div>

        </section>

        {{-- =====================================*

             02 - INFORMACIÓN DEL PLAN

         ===================================== --}}

        <section class="nl-section">

            <div class="nl-section-heading">

                <div class="nl-step-icon">

                    <i class="fas fa-file-medical"></i>

                </div>

                <div class="nl-section-copy">

                    <span>PASO 02 · INFORMACIÓN</span>

                    <h3>Detalles del plan</h3>

                    <p>

                        Define el nombre y el periodo

                        de seguimiento nutricional.

                    </p>

                </div>

                <span class="nl-step-number">02</span>

            </div>

            <div class="nl-section-body">

                <div class="nl-field">

                    <label for="name">

                        <i class="fas fa-pen"></i>

                        Nombre del plan

                        <span class="nl-required">*</span>

                    </label>

                    <input

                        type="text"

                        name="name"

                        id="name"

                        class="nl-input @error('name') is-invalid @enderror"

                        value="{{ old('name') }}"

                        placeholder="Ej. Plan de déficit calórico - 1800 kcal"

                        required

                    >

                    @error('name')

                        <span class="nl-error">

                            {{ $message }}

                        </span>

                    @enderror

                </div>

                <div class="nl-field-grid">

                    <div class="nl-field">

                        <label for="starts_at">

                            <i class="far fa-calendar-alt"></i>

                            Fecha de inicio

                            <span class="nl-required">*</span>

                        </label>

                        <input

                            type="date"

                            name="starts_at"

                            id="starts_at"

                            class="nl-input @error('starts_at') is-invalid @enderror"

                            value="{{ old('starts_at', now()->format('Y-m-d')) }}"

                            required

                        >

                        @error('starts_at')

                            <span class="nl-error">

                                {{ $message }}

                            </span>

                        @enderror

                    </div>

                    <div class="nl-field">

                        <label for="ends_at">

                            <i class="far fa-calendar-check"></i>

                            Fecha de finalización

                        </label>

                        <input

                            type="date"

                            name="ends_at"

                            id="ends_at"

                            class="nl-input @error('ends_at') is-invalid @enderror"

                            value="{{ old('ends_at') }}"

                        >

                        @error('ends_at')

                            <span class="nl-error">

                                {{ $message }}

                            </span>

                        @enderror

                        <small>

                            Opcional

                        </small>

                    </div>

                </div>

            </div>

        </section>

        {{-- =====================================*

             03 - OBJETIVOS NUTRICIONALES

         ===================================== --}}

        <section class="nl-section">

            <div class="nl-section-heading">

                <div class="nl-step-icon">

                    <i class="fas fa-chart-pie"></i>

                </div>

                <div class="nl-section-copy">

                    <span>PASO 03 · NUTRICIÓN</span>

                    <h3>Objetivos nutricionales</h3>

                    <p>

                        Establece las calorías y los

                        macronutrientes diarios.

                    </p>

                </div>

                <span class="nl-step-number">03</span>

            </div>

            <div class="nl-section-body">

                <div class="nl-macro-grid">

                    {{-- CALORÍAS --}}

                    <div class="nl-macro-card kcal">

                        <div class="nl-macro-top">

                            <div class="nl-macro-icon">

                                <i class="fas fa-fire"></i>

                            </div>

                            <span>ENERGÍA</span>

                        </div>

                        <label for="target_kcal">

                            Calorías

                        </label>

                        <div class="nl-macro-input">

                            <input

                                type="number"

                                name="target_kcal"

                                id="target_kcal"

                                value="{{ old('target_kcal') }}"

                                placeholder="2000"

                                step="0.01"

                                min="0"

                                required

                            >

                            <span>kcal</span>

                        </div>

                        @error('target_kcal')

                            <span class="nl-error">

                                {{ $message }}

                            </span>

                        @enderror

                    </div>

                    {{-- PROTEÍNAS --}}

                    <div class="nl-macro-card protein">

                        <div class="nl-macro-top">

                            <div class="nl-macro-icon">

                                <i class="fas fa-dumbbell"></i>

                            </div>

                            <span>MACRONUTRIENTE</span>

                        </div>

                        <label for="target_protein_g">

                            Proteínas

                        </label>

                        <div class="nl-macro-input">

                            <input

                                type="number"

                                name="target_protein_g"

                                id="target_protein_g"

                                value="{{ old('target_protein_g') }}"

                                placeholder="160"

                                step="0.01"

                                min="0"

                                required

                            >

                            <span>g</span>

                        </div>

                        @error('target_protein_g')

                            <span class="nl-error">

                                {{ $message }}

                            </span>

                        @enderror

                    </div>

                    {{-- CARBOHIDRATOS --}}

                    <div class="nl-macro-card carbs">

                        <div class="nl-macro-top">

                            <div class="nl-macro-icon">

                                <i class="fas fa-bread-slice"></i>

                            </div>

                            <span>MACRONUTRIENTE</span>

                        </div>

                        <label for="target_carbs_g">

                            Carbohidratos

                        </label>

                        <div class="nl-macro-input">

                            <input

                                type="number"

                                name="target_carbs_g"

                                id="target_carbs_g"

                                value="{{ old('target_carbs_g') }}"

                                placeholder="220"

                                step="0.01"

                                min="0"

                                required

                            >

                            <span>g</span>

                        </div>

                        @error('target_carbs_g')

                            <span class="nl-error">

                                {{ $message }}

                            </span>

                        @enderror

                    </div>

                    {{-- GRASAS --}}

                    <div class="nl-macro-card fats">

                        <div class="nl-macro-top">

                            <div class="nl-macro-icon">

                                <i class="fas fa-tint"></i>

                            </div>

                            <span>MACRONUTRIENTE</span>

                        </div>

                        <label for="target_lipids_g">

                            Grasas

                        </label>

                        <div class="nl-macro-input">

                            <input

                                type="number"

                                name="target_lipids_g"

                                id="target_lipids_g"

                                value="{{ old('target_lipids_g') }}"

                                placeholder="65"

                                step="0.01"

                                min="0"

                                required

                            >

                            <span>g</span>

                        </div>

                        @error('target_lipids_g')

                            <span class="nl-error">

                                {{ $message }}

                            </span>

                        @enderror

                    </div>

                </div>

                <div class="nl-info-note">

                    <i class="fas fa-info-circle"></i>

                    <p>

                        Los valores registrados serán utilizados

                        como objetivos nutricionales del plan.

                    </p>

                </div>

            </div>

        </section>

        {{-- =====================================*

             ACCIONES

         ===================================== --}}

        <div class="nl-form-footer">

            <div class="nl-footer-info">

                <i class="fas fa-shield-alt"></i>

                <span>

                    Completa los campos obligatorios

                    para registrar el plan.

                </span>

            </div>

            <div class="nl-footer-buttons">

                <a

                    href="{{ route('diet-plans.index') }}"

                    class="nl-cancel-button"

                >

                    Cancelar

                </a>

                <button

                    type="submit"

                    class="nl-save-button"

                >

                    <i class="fas fa-check"></i>

                    Crear plan

                    <i class="fas fa-arrow-right"></i>

                </button>

            </div>

        </div>

    </form>

</div>

@stop

{{-- =============================================*

     ESTILOS

============================================= --}}

@section('css')

@include('partials.theme-styles', ['section' => 'admin'])

<style>

/* =============================================*

    VARIABLES DEL FORMULARIO

============================================= */

.nl-create-page {

    --nl-border: var(

        --border-color,

        rgba(130, 160, 135, .15)
                
    );

    --nl-surface: var(

        --card-bg,

        #ffffff

    );

    --nl-text: var(

        --text-primary,

        #25392b

    );

    --nl-muted: var(

        --text-secondary,

        #748579

    );

    --nl-accent: #79a77d;

    max-width: 1150px;

    margin: 0 auto;

    padding-bottom: 55px;

}

/* =============================================*

    VOLVER

============================================= */

.nl-back-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    padding: 11px 18px;

    border: 1px solid var(--nl-border,  rgba(130,160,135,.15));

    border-radius: 12px;

    background: rgba(135, 175, 140, .07);

    color: inherit !important;

    font-size: 12px;

    font-weight: 600;

    text-decoration: none !important;

    transition: .2s ease;

}

.nl-back-button:hover {

    background: rgba(135, 175, 140, .15);

    transform: translateY(-2px);

}

/* =============================================*

    INTRO

============================================= */

.nl-intro {

    display: flex;

    align-items: center;

    gap: 17px;

    margin-bottom: 20px;

    padding: 22px 25px;

    border: 1px solid var(--nl-border);

    border-radius: 18px;

    background: var(--nl-surface);

}

.nl-intro-icon {

    width: 54px;

    height: 54px;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

    border-radius: 15px;

    background: rgba(127, 176, 129, .12);

    color: #78a57b;

    font-size: 20px;

}

.nl-intro span {

    display: block;

    margin-bottom: 5px;

    color: #81a98a;

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 1.5px;

}

.nl-intro h2 {

    margin: 0 0 5px;

    color: var(--nl-text);

    font-size: 19px;

    font-weight: 700;

}

.nl-intro p {

    margin: 0;

    color: var(--nl-muted);

    font-size: 12px;

}

/* =============================================*

    FORMULARIO

============================================= */

.nl-form {

    display: flex;

    flex-direction: column;

    gap: 17px;

}

.nl-section {

    overflow: hidden;

    border: 1px solid var(--nl-border);

    border-radius: 19px;

    background: var(--nl-surface);

    transition: border-color .2s ease;

}

.nl-section:hover {

    border-color: rgba(127, 176, 129, .28);

}

/* =============================================*

    ENCABEZADO DE SECCIÓN

============================================= */

.nl-section-heading {

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 22px 27px;

    border-bottom: 1px solid var(--nl-border);

    background: rgba(129, 174, 132, .035);

}

.nl-step-icon {

    width: 46px;

    height: 46px;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

    border-radius: 13px;

    background: rgba(126, 174, 129, .11);

    color: #7eac82;

    font-size: 17px;

}

.nl-section-copy {

    flex: 1;

    min-width: 0;

}

.nl-section-copy > span {

    display: block;

    margin-bottom: 4px;

    color: #83aa89;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 1.2px;

}

.nl-section-copy h3 {

    margin: 0 0 4px;

    color: var(--nl-text);

    font-size: 17px;

    font-weight: 700;

}

.nl-section-copy p {

    margin: 0;

    color: var(--nl-muted);

    font-size: 11px;

    line-height: 1.6;

}

.nl-step-number {

    color: rgba(128, 170, 135, .18);

    font-size: 32px;

    font-weight: 800;

}

.nl-section-body {

    padding: 27px;

}

/* =============================================*

    CAMPOS

============================================= */

.nl-field {

    margin-bottom: 19px;

}

.nl-field:last-child {

    margin-bottom: 0;

}

.nl-field label {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 10px;

    color: var(--nl-text);

    font-size: 12px;

    font-weight: 600;

}

.nl-field label i {

    color: #88ad8d;

    font-size: 12px;

}

.nl-required {

    color: #e29b83;

}

.nl-input {

    width: 100%;

    min-height: 48px;

    padding: 0 15px;

    border: 1px solid var(--nl-border);

    border-radius: 11px;

    outline: none;

    background: var(--nl-surface);

    color: var(--nl-text);

    font-size: 13px;

    transition: .2s ease;

}

.nl-input::placeholder {

    color: var(--nl-muted);

    opacity: .75;

}

.nl-input:hover {

    border-color: rgba(133, 180, 137, .35);

}

.nl-input:focus {

    border-color: #88b78c;

    box-shadow:

        0 0 0 3px

        rgba(127, 177, 133, .11);

}

.nl-input.is-invalid {

    border-color: #d98383;

}

.nl-field small {

    display: block;

    margin-top: 8px;

    color: var(--nl-muted);

    font-size: 10px;

}

.nl-error {

    display: block;

    margin-top: 7px;

    color: #db8080;

    font-size: 11px;

}

/* SELECT */

.nl-select-wrapper {

    position: relative;

}

.nl-select-wrapper select {

    appearance: none;

    padding-right: 42px;

    cursor: pointer;

}

.nl-select-wrapper > i {

    position: absolute;

    right: 17px;

    top: 50%;

    transform: translateY(-50%);

    pointer-events: none;

    color: #8dad91;

    font-size: 11px;

}

.nl-input option {

    background: var(--nl-surface);

    color: var(--nl-text);

}

/* FECHAS */

.nl-field-grid {

    display: grid;

    grid-template-columns: repeat(2, minmax(0, 1fr));

    gap: 20px;

}

.nl-field-grid .nl-field {

    margin-bottom: 0;

}

.nl-input[type="date"] {

    color-scheme: normal;

}

/* =============================================*

    MACRONUTRIENTES

============================================= */

.nl-macro-grid {

    display: grid;

    grid-template-columns: repeat(4, minmax(0, 1fr));

    gap: 14px;

}

.nl-macro-card {

    min-width: 0;

    padding: 19px;

    border: 1px solid var(--nl-border);

    border-radius: 16px;

    background: rgba(128, 170, 135, .035);

    transition: all .2s ease;

}

.nl-macro-card:hover {

    border-color: rgba(130, 180, 135, .32);

    transform: translateY(-2px);

}

.nl-macro-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 8px;

    margin-bottom: 19px;

}

.nl-macro-icon {

    width: 40px;

    height: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: rgba(135, 180, 138, .12);

    color: #83ad88;

    font-size: 15px;

}

.nl-macro-top > span {

    color: var(--nl-muted);

    font-size: 8px;

    font-weight: 700;

    letter-spacing: .8px;

    text-align: right;

}

.nl-macro-card > label {

    display: block;

    margin-bottom: 12px;

    color: var(--nl-text);

    font-size: 13px;

    font-weight: 650;

}

.nl-macro-input {

    display: flex;

    align-items: center;

    gap: 6px;

    min-height: 46px;

    padding: 0 11px;

    border: 1px solid var(--nl-border);

    border-radius: 11px;

    background: var(--nl-surface);

    transition: .2s ease;

}

.nl-macro-input:focus-within {

    border-color: #8dbb90;

    box-shadow: 0 0 0 3px rgba(125, 175, 130, .1);

}

.nl-macro-input input {

    width: 100%;

    min-width: 0;

    padding: 9px 0;

    border: none;

    outline: none;

    background: transparent;

    color: var(--nl-text);

    font-size: 17px;

    font-weight: 700;

}

.nl-macro-input input::placeholder {

    color: var(--nl-muted);

    opacity: .55;

}

.nl-macro-input > span {

    color: var(--nl-muted);

    font-size: 11px;

    font-weight: 600;

}

/* COLORES DE MACROS */

.nl-macro-card.kcal .nl-macro-icon {

    background: rgba(221, 165, 94, .12);

    color: #d5a257;

}

.nl-macro-card.protein .nl-macro-icon {

    background: rgba(104, 177, 133, .13);

    color: #77b98e;

}

.nl-macro-card.carbs .nl-macro-icon {

    background: rgba(197, 194, 93, .12);

    color: #b4b45c;

}

.nl-macro-card.fats .nl-macro-icon {

    background: rgba(101, 161, 190, .12);

    color: #7eb2cc;

}

/* =============================================*

    NOTA INFORMATIVA

============================================= */

.nl-info-note {

    display: flex;

    align-items: flex-start;

    gap: 11px;

    margin-top: 19px;

    padding: 14px 17px;

    border: 1px solid rgba(125, 176, 134, .13);

    border-radius: 12px;

    background: rgba(129, 177, 134, .055);

}

.nl-info-note i {

    margin-top: 2px;

    color: #82af88;

    font-size: 14px;

}

.nl-info-note p {

    margin: 0;

    color: var(--nl-muted);

    font-size: 11px;

    line-height: 1.7;

}

/* =============================================*

    FOOTER Y ACCIONES

============================================= */

.nl-form-footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding: 21px 25px;

    border: 1px solid var(--nl-border);

    border-radius: 17px;

    background: var(--nl-surface);

}

.nl-footer-info {

    display: flex;

    align-items: center;

    gap: 9px;

    color: var(--nl-muted);

    font-size: 11px;

}

.nl-footer-info i {

    color: #83af89;

    font-size: 15px;

}

.nl-footer-buttons {

    display: flex;

    align-items: center;

    gap: 11px;

}

.nl-cancel-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 44px;

    padding: 0 19px;

    border: 1px solid var(--nl-border);

    border-radius: 11px;

    color: var(--nl-muted) !important;

    font-size: 12px;

    font-weight: 600;

    text-decoration: none !important;

    transition: .2s ease;

}

.nl-cancel-button:hover {

    background: rgba(130, 170, 135, .09);

    color: var(--nl-text) !important;

}

.nl-save-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 11px;

    min-height: 44px;

    padding: 0 23px;

    border: none;

    border-radius: 11px;

    background: linear-gradient(

        135deg,

        #a8d0a2,

        #d8e77d

    );

    color: #17321e;

    font-size: 12px;

    font-weight: 750;

    cursor: pointer;

    transition: .2s ease;

}

.nl-save-button:hover {

    transform: translateY(-2px);

    box-shadow:

        0 10px 22px

        rgba(119, 169, 111, .17);

}

/* =============================================*

    VALIDACIÓN

============================================= */

.nl-validation-alert {

    display: flex;

    align-items: flex-start;

    gap: 12px;

    margin-bottom: 17px;

    padding: 16px 20px;

    border: 1px solid rgba(210, 100, 100, .2);

    border-radius: 13px;

    background: rgba(210, 100, 100, .07);

    color: #d88a8a;

}

.nl-validation-alert > i {

    font-size: 19px;

}

.nl-validation-alert strong {

    display: block;

    font-size: 12px;

}

.nl-validation-alert p {

    margin: 4px 0 0;

    font-size: 11px;

}

/* =============================================*

    MODO OSCURO

============================================= */

body.dark-mode .nl-create-page,

.dark-mode .nl-create-page,

[data-theme="dark"] .nl-create-page {

    --nl-border: rgba(215, 238, 217, .09);

    --nl-surface: #14231a;

    --nl-text: #e8f2e9;

    --nl-muted: #9aafa0;

}

body.dark-mode .nl-section-heading,

.dark-mode .nl-section-heading,

[data-theme="dark"] .nl-section-heading {

    background: rgba(160, 210, 164, .025);

}

body.dark-mode .nl-input[type="date"],

.dark-mode .nl-input[type="date"],

[data-theme="dark"] .nl-input[type="date"] {

    color-scheme: dark;

}

/* =============================================*

    RESPONSIVE

============================================= */

@media (max-width: 1100px) {

    .nl-macro-grid {

        grid-template-columns: repeat(2, minmax(0, 1fr));

    }

}

@media (max-width: 800px) {

    .nl-form-footer {

        align-items: stretch;

        flex-direction: column;

    }

    .nl-footer-buttons {

        justify-content: flex-end;

    }

}

@media (max-width: 600px) {

    .nl-intro {

        padding: 18px;

    }

    .nl-intro h2 {

        font-size: 17px;

    }

    .nl-section-heading {

        padding: 19px;

    }

    .nl-section-body {

        padding: 19px;

    }

    .nl-step-number {

        display: none;

    }

    .nl-field-grid {

        grid-template-columns: 1fr;

        gap: 18px;

    }

    .nl-macro-grid {

        grid-template-columns: 1fr 1fr;

        gap: 10px;

    }

    .nl-macro-card {

        padding: 13px;

    }

    .nl-macro-top > span {

        font-size: 7px;

    }

    .nl-form-footer {

        padding: 19px;

    }

    .nl-footer-buttons {

        width: 100%;

    }

    .nl-cancel-button,

    .nl-save-button {

        flex: 1;

        padding: 0 10px;

    }

    .nl-footer-info {

        font-size: 10px;

    }

}

@media (max-width: 380px) {

    .nl-macro-grid {

        grid-template-columns: 1fr;

    }

    .nl-footer-buttons {

        flex-direction: column;

    }

    .nl-cancel-button,

    .nl-save-button {

        width: 100%;

    }

}

/* ==========================================*

    NUTRILIFE - COLORES MÁS CLAROS

    Sin modificar el diseño ni funcionalidades

========================================== */

/* Tarjetas y secciones */

.nl-create-page {

    --nl-border: rgba(157, 198, 163, .25);

}

.nl-intro,

.nl-section,

.nl-form-footer {

    border-color: var(--nl-border);

}

/* Encabezados de secciones */

.nl-section-heading {

    background: rgba(155, 205, 161, .09);

}

/* Campos del formulario */

.nl-input,

.nl-macro-input {

    border-color: rgba(151, 194, 145, .30);

    background: rgba(151, 194, 145, .075);

}

.nl-input:hover,

.nl-macro-input:hover {

    border-color: rgba(166, 214, 169, .55);

}

.nl-input:focus,

.nl-macro-input:focus-within {

    border-color: #a9d9aa;

    box-shadow: 0 0 0 3px rgba(169, 217, 170, .15);

}

/* Tarjetas de macronutrientes */

.nl-macro-card {

    background: rgba(155, 200, 160, .09);

    border-color: rgba(155, 200, 160, .25);

}

.nl-macro-card:hover {

    background: rgba(155, 200, 160, .14);

    border-color: rgba(175, 220, 175, .45);

}

/* Iconos más visibles */

.nl-step-icon,

.nl-intro-icon,

.nl-macro-icon {

    background: rgba(165, 211, 164, .20);

    color: #a8d6a5;

}

/* Botón volver */

.nl-back-button {

    background: rgba(165, 211, 164, .17);

    border-color: rgba(165, 211, 164, .35);

    color: #c5e8c2 !important;

}

.nl-back-button:hover {

    background: rgba(165, 211, 164, .28);

    color: #e5f8e2 !important;

}

/* Botón cancelar */

.nl-cancel-button {

    background: rgba(165, 211, 164, .15);

    border-color: rgba(165, 211, 164, .32);

    color: #c7e7c5 !important;

}

.nl-cancel-button:hover {

    background: rgba(165, 211, 164, .25);

    color: #e8f8e6 !important;

}

/* Botón crear plan: verde más luminoso */

.nl-save-button {

    background: linear-gradient(

        135deg,

        #bce8b5,

        #e3f18d

    );

    color: #17351d;

    box-shadow: 0 5px 18px rgba(171, 217, 153, .18);

}

.nl-save-button:hover {

    background: linear-gradient(

        135deg,

        #cff4c7,

        #eff8a9

    );

    color: #102917;

    box-shadow: 0 9px 25px rgba(171, 217, 153, .28);

}

/* Mejor contraste del texto secundario */

.nl-section-copy p,

.nl-intro p,

.nl-field small,

.nl-info-note p,

.nl-footer-info {

    opacity: 1;

}

/* Ajustes específicos del modo claro */

body:not(.dark-mode) .nl-back-button,

body:not(.dark-mode) .nl-cancel-button {

    color: #315b39 !important;

}

body:not(.dark-mode) .nl-step-icon,

body:not(.dark-mode) .nl-intro-icon,

body:not(.dark-mode) .nl-macro-icon {

    color: #487c50;

}


/* ======================================================
   PALETA NUTRILIFE · IGUAL A PACIENTES
   Claro: superficies cálidas; oscuro: verdes bosque.
   Sin modificar rutas, validaciones o campos.
====================================================== */
.nl-create-page {
    --nl-surface: #ffffff;
    --nl-soft-surface: #f3f8f1;
    --nl-input-surface: #fbfdf9;
    --nl-border: #d9e7d8;
    --nl-text: #203c2b;
    --nl-muted: #6c8572;
    --nl-accent: #459b54;
}
.nl-create-page .nl-intro,
.nl-create-page .nl-section,
.nl-create-page .nl-form-footer {
    background: var(--nl-surface);
    border-color: var(--nl-border);
    box-shadow: 0 12px 30px rgba(20, 54, 31, .045);
}
.nl-create-page .nl-section-heading,
.nl-create-page .nl-macro-card,
.nl-create-page .nl-info-note {
    background: var(--nl-soft-surface);
    border-color: var(--nl-border);
}
.nl-create-page .nl-input,
.nl-create-page .nl-macro-input {
    background: var(--nl-input-surface);
    border-color: var(--nl-border);
    color: var(--nl-text);
}
.nl-create-page .nl-macro-input input {color:var(--nl-text);}
.nl-create-page .nl-input option {background:var(--nl-input-surface);color:var(--nl-text);}
.nl-create-page .nl-back-button,
.nl-create-page .nl-cancel-button {
    background: #e6f3e5;
    border-color: #c6dec7;
    color: #285934 !important;
}
.nl-create-page .nl-intro-icon,
.nl-create-page .nl-step-icon,
.nl-create-page .nl-macro-icon {
    background: #dfefdd;
    color: #449956;
}
.nl-create-page .nl-intro span,
.nl-create-page .nl-section-copy > span {color: #459b54;}
.nl-create-page .nl-save-button {
    background: linear-gradient(135deg,#439a51,#60aa62);
    color: white;
    box-shadow: 0 8px 20px rgba(30,98,48,.13);
}
.nl-create-page .nl-save-button:hover {background:linear-gradient(135deg,#347f42,#4c9b55);color:white;}
/* MODO OSCURO: mismos tonos bosque que la sección Pacientes */
body.dark-mode .nl-create-page,
html.dark-mode .nl-create-page,
[data-theme="dark"] .nl-create-page,
[data-bs-theme="dark"] .nl-create-page {
    --nl-surface: #213d2d;
    --nl-soft-surface: #294a36;
    --nl-input-surface: #2c4b38;
    --nl-border: #42654d;
    --nl-text: #eff8f0;
    --nl-muted: #a7beac;
    --nl-accent: #98d6a5;
}
body.dark-mode .nl-create-page .nl-intro,
body.dark-mode .nl-create-page .nl-section,
body.dark-mode .nl-create-page .nl-form-footer,
html.dark-mode .nl-create-page .nl-intro,
html.dark-mode .nl-create-page .nl-section,
html.dark-mode .nl-create-page .nl-form-footer,
[data-theme="dark"] .nl-create-page .nl-intro,
[data-theme="dark"] .nl-create-page .nl-section,
[data-theme="dark"] .nl-create-page .nl-form-footer {
    box-shadow: 0 12px 32px rgba(4,20,10,.10);
}
body.dark-mode .nl-create-page .nl-back-button,
body.dark-mode .nl-create-page .nl-cancel-button,
html.dark-mode .nl-create-page .nl-back-button,
html.dark-mode .nl-create-page .nl-cancel-button,
[data-theme="dark"] .nl-create-page .nl-back-button,
[data-theme="dark"] .nl-create-page .nl-cancel-button {
    background: #355c42;
    border-color: #52785b;
    color: #d8f1df !important;
}
body.dark-mode .nl-create-page .nl-intro-icon,
body.dark-mode .nl-create-page .nl-step-icon,
body.dark-mode .nl-create-page .nl-macro-icon,
html.dark-mode .nl-create-page .nl-intro-icon,
html.dark-mode .nl-create-page .nl-step-icon,
html.dark-mode .nl-create-page .nl-macro-icon,
[data-theme="dark"] .nl-create-page .nl-intro-icon,
[data-theme="dark"] .nl-create-page .nl-step-icon,
[data-theme="dark"] .nl-create-page .nl-macro-icon {
    background: #385f46;
    color: #a1deaf;
}
body.dark-mode .nl-create-page .nl-intro span,
body.dark-mode .nl-create-page .nl-section-copy > span,
html.dark-mode .nl-create-page .nl-intro span,
html.dark-mode .nl-create-page .nl-section-copy > span,
[data-theme="dark"] .nl-create-page .nl-intro span,
[data-theme="dark"] .nl-create-page .nl-section-copy > span {color:#a1d8ae;}
body.dark-mode .nl-create-page .nl-input[type="date"],
html.dark-mode .nl-create-page .nl-input[type="date"],
[data-theme="dark"] .nl-create-page .nl-input[type="date"] {color-scheme:dark;}
</style>

@stop

@section('js')

@stop
