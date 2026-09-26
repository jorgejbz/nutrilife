@extends('adminlte::page')

{{-- =========================================================
     PERFIL
     Antes usaba el layout de Breeze (layouts/app, Tailwind, sin
     tema ni marca). Ahora vive dentro del panel NutriLife; los
     formularios de Breeze se conservan tal cual y se estilizan
     en public/css/admin.css, sección "4. PERFIL".
     ========================================================= --}}

@section('title', 'Mi perfil | NutriLife')


@section('content_header')

    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf mr-1"></i>
                NUTRILIFE · CUENTA
            </span>

            <h1>Mi perfil</h1>

            <p>
                Actualiza tus datos de acceso y la seguridad de tu cuenta.
            </p>
        </div>
    </div>

@stop


@section('content')

    <div class="nl-profile">

        <div class="nl-profile-card">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="nl-profile-card">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="nl-profile-card">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>

    </div>

@stop


@section('css')

    @include('partials.theme-styles', ['section' => 'admin'])

@stop


@section('js')

    {{-- Alpine.js: lo usan el modal de "Eliminar cuenta" y los avisos "Guardado." --}}
    @vite(['resources/js/app.js'])

@stop
