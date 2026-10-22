@extends('adminlte::page')

{{-- =========================================================
     ALTA / EDICIÓN DE UN SUBPERFIL DE NUTRIÓLOGO
     $member = null → alta (ClinicTeamController@store)
     $member        → edición (ClinicTeamController@update)
     Rol y clínica los fija el servidor; no hay campos para ellos.
     ========================================================= --}}

@php
    $editing = $member !== null;
    $fields = [
        ['name', 'Nombre completo', 'text', 'far fa-user', 'Ej. Lic. Andrea Solís Rivera', 'name', true, null],
        ['email', 'Correo electrónico (inicio de sesión)', 'email', 'far fa-envelope', 'nutriologo@clinica.com', 'username', true, 'Con este correo iniciará sesión. Debe ser único en NutriLife.'],
        ['professional_license', 'Cédula profesional', 'text', 'fas fa-id-card', '7 u 8 dígitos', 'off', false, 'Opcional. Solo se valida el formato; no se consulta a la SEP.'],
        ['degree_number', 'Número de título profesional', 'text', 'fas fa-graduation-cap', 'Ej. LN-2019-0457', 'off', false, 'Opcional. Letras, números, guiones y diagonales.'],
    ];
@endphp

@section('title', ($editing ? 'Editar nutriólogo' : 'Agregar nutriólogo').' | NutriLife')

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · CLÍNICA
            </span>
            <h1>{{ $editing ? 'Editar a '.$member->name : 'Agregar nutriólogo' }}</h1>
            <p>
                {{ $editing
                    ? 'Actualiza los datos del subperfil. Deja la contraseña vacía para conservar la actual.'
                    : 'Crea el acceso de un nutriólogo de '.$clinic->name.'. Tendrá las funciones '.config('nutrilife.clinic.member_plan').' con la licencia de la clínica.' }}
            </p>
        </div>

        <div class="nl-page-actions">
            <a href="{{ $editing ? route('clinic.team.show', $member->id) : route('clinic.team.index') }}" class="nl-btn nl-btn--ghost">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Regresar
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    <section class="nl-profile-card">
        <form method="post"
              action="{{ $editing ? route('clinic.team.update', $member->id) : route('clinic.team.store') }}"
              class="nl-settings-form" novalidate>
            @csrf
            @if ($editing)
                @method('put')
            @endif

            <div class="nl-form-grid">
                @foreach ($fields as [$field, $label, $type, $icon, $placeholder, $autocomplete, $required, $hint])
                    <div class="nl-field">
                        <label for="{{ $field }}">
                            {{ $label }}
                            @if ($required) <span class="nl-req" aria-hidden="true">*</span> @endif
                        </label>
                        <div class="nl-input @error($field) has-error @enderror">
                            <i class="{{ $icon }}" aria-hidden="true"></i>
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}"
                                   value="{{ old($field, $member?->{$field}) }}"
                                   maxlength="255" placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}"
                                   @if ($required) required @endif
                                   @error($field) aria-invalid="true" aria-describedby="{{ $field }}_error" @enderror>
                        </div>
                        @error($field)
                            <p class="nl-field-error" id="{{ $field }}_error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</p>
                        @elseif ($hint)
                            <p class="nl-field-hint">{{ $hint }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="nl-field">
                    <label for="password">
                        {{ $editing ? 'Nueva contraseña' : 'Contraseña' }}
                        @unless ($editing) <span class="nl-req" aria-hidden="true">*</span> @endunless
                    </label>
                    <div class="nl-input @error('password') has-error @enderror">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input id="password" name="password" type="password" autocomplete="new-password"
                               placeholder="{{ $editing ? 'Dejar vacío para no cambiarla' : 'Mínimo 8 caracteres' }}"
                               @unless ($editing) required @endunless
                               @error('password') aria-invalid="true" aria-describedby="password_error" @enderror>
                    </div>
                    @error('password')
                        <p class="nl-field-error" id="password_error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</p>
                    @else
                        <p class="nl-field-hint">Compártela con el nutriólogo de forma segura; podrá cambiarla desde su perfil.</p>
                    @enderror
                </div>

                <div class="nl-field">
                    <label for="password_confirmation">Confirmar contraseña</label>
                    <div class="nl-input">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="nl-settings-actions">
                <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Guardando…">
                    <i class="fas fa-floppy-disk" aria-hidden="true"></i>
                    {{ $editing ? 'Guardar cambios' : 'Crear subperfil' }}
                </button>
                <a href="{{ $editing ? route('clinic.team.show', $member->id) : route('clinic.team.index') }}" class="nl-btn nl-btn--ghost">Cancelar</a>
            </div>
        </form>
    </section>

</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
