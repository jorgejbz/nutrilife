{{-- =========================================================
     PLANES ALIMENTICIOS — HOJAS DE ESTILO
     Tema del panel (claro/oscuro) + estilos propios del módulo
     (public/css/diet-plans.css, todo bajo .dp).
     ========================================================= --}}
@include('partials.theme-styles', ['section' => 'admin'])

@php $dpCss = public_path('css/diet-plans.css'); @endphp
<link rel="stylesheet" href="{{ asset('css/diet-plans.css') }}{{ is_file($dpCss) ? '?v='.filemtime($dpCss) : '' }}">
